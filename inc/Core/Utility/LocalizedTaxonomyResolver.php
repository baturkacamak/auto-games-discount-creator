<?php

namespace AutoGamesDiscountCreator\Core\Utility;

use AutoGamesDiscountCreator\Core\Integration\WpmlSupport;

class LocalizedTaxonomyResolver
{
	private WpmlSupport $wpmlSupport;

	public function __construct()
	{
		$this->wpmlSupport = new WpmlSupport();
	}

	public function assignTermsToPost(int $postId, array $marketTarget, string $contentKind, array $copySet): void
	{
		if ($postId <= 0) {
			return;
		}

		$marketKey = (string) ($marketTarget['market_key'] ?? 'tr-tr');
		$languageCode = $marketKey;
		$categoryConfig = $this->getCategoryConfig($contentKind, $copySet);
		$categoryId = $this->ensureTerm(
			'category',
			$categoryConfig['name'],
			$categoryConfig['slug_base'],
			$marketKey,
			$languageCode,
			$categoryConfig['source_slug_base'] ?? null
		);

		if ($categoryId > 0) {
			wp_set_post_terms($postId, [$categoryId], 'category', false);
			$this->normalizeAssignedTermsForLanguage($postId, 'category', $languageCode);
		}

		$tagIds = [];
		foreach ($this->getTagConfigs($contentKind, $copySet) as $tagConfig) {
			$tagId = $this->ensureTerm(
				'post_tag',
				$tagConfig['name'],
				$tagConfig['slug_base'],
				$marketKey,
				$languageCode
			);
			if ($tagId > 0) {
				$tagIds[] = $tagId;
			}
		}

		if ($tagIds !== []) {
			wp_set_post_terms($postId, $tagIds, 'post_tag', false);
			$this->normalizeAssignedTermsForLanguage($postId, 'post_tag', $languageCode);
		}
	}

	private function getCategoryConfig(string $contentKind, array $copySet): array
	{
		if ($contentKind === 'free_game') {
			return [
				'name' => (string) ($copySet['free_category_name'] ?? 'Free Games'),
				'slug_base' => (string) ($copySet['free_category_slug_base'] ?? 'free-games'),
				'source_slug_base' => 'ucretsiz-oyunlar',
			];
		}

		return [
			'name' => (string) ($copySet['daily_category_name'] ?? 'Daily Deals'),
			'slug_base' => (string) ($copySet['daily_category_slug_base'] ?? 'daily-deals'),
			'source_slug_base' => 'gunluk-indirimler',
		];
	}

	private function getTagConfigs(string $contentKind, array $copySet): array
	{
		$tagNames = $contentKind === 'free_game'
			? (array) ($copySet['free_tag_names'] ?? [])
			: (array) ($copySet['daily_tag_names'] ?? []);

		$configs = [];
		foreach ($tagNames as $tagName) {
			if (!is_string($tagName) || trim($tagName) === '') {
				continue;
			}

			$configs[] = [
				'name' => trim($tagName),
				'slug_base' => sanitize_title(trim($tagName)),
			];
		}

		return $configs;
	}

	private function ensureTerm(string $taxonomy, string $name, string $slugBase, string $marketKey, string $languageCode, ?string $sourceSlugBase = null): int
	{
		$slug = sanitize_title($slugBase . '-' . $marketKey);
		$term = get_term_by('slug', $slug, $taxonomy);
		if (is_object($term) && isset($term->term_id)) {
			return (int) $term->term_id;
		}

		$inserted = wp_insert_term(
			$name,
			$taxonomy,
			[
				'slug' => $slug,
			]
		);

		if (is_wp_error($inserted) || !isset($inserted['term_id'])) {
			return 0;
		}

		$termId = (int) $inserted['term_id'];
		$this->wpmlSupport->assignTermLanguage($termId, $taxonomy, $languageCode);

		$sourceTerm = $this->findSourceTerm($taxonomy, $sourceSlugBase ?: $slugBase);
		if ($marketKey !== 'tr-tr' && $sourceTerm > 0) {
			$this->wpmlSupport->linkTermTranslation($sourceTerm, $termId, $taxonomy, $languageCode);
		}

		return $termId;
	}

	private function findSourceTerm(string $taxonomy, string $slugBase): int
	{
		$sourceSlug = sanitize_title($slugBase . '-tr-tr');
		$term = get_term_by('slug', $sourceSlug, $taxonomy);
		if (is_object($term) && isset($term->term_id)) {
			return (int) $term->term_id;
		}

		return 0;
	}

	private function normalizeAssignedTermsForLanguage(int $postId, string $taxonomy, string $languageCode): void
	{
		if ($postId <= 0 || $taxonomy === '' || $languageCode === '' || !$this->wpmlSupport->isAvailable()) {
			return;
		}

		$termIds = wp_get_post_terms($postId, $taxonomy, ['fields' => 'ids']);
		if (!is_array($termIds) || $termIds === []) {
			return;
		}

		$resolved = [];
		foreach ($termIds as $termId) {
			$termId = (int) $termId;
			if ($termId <= 0) {
				continue;
			}

			$translatedId = apply_filters('wpml_object_id', $termId, $taxonomy, false, $languageCode);
			$resolved[] = (int) ($translatedId ?: $termId);
		}

		$resolved = array_values(array_unique(array_filter($resolved)));
		if ($resolved !== []) {
			wp_set_post_terms($postId, $resolved, $taxonomy, false);
		}
	}
}
