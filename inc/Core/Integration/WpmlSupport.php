<?php

namespace AutoGamesDiscountCreator\Core\Integration;

class WpmlSupport
{
	public function isAvailable(): bool
	{
		return defined('ICL_SITEPRESS_VERSION')
			|| class_exists('SitePress')
			|| has_filter('wpml_element_language_details')
			|| has_action('wpml_set_element_language_details');
	}

	public function assignPostLanguage(int $postId, string $postType, string $languageCode): void
	{
		if ($postId <= 0 || $postType === '' || $languageCode === '' || !$this->isAvailable()) {
			return;
		}

		$resolvedLanguageCode = $this->resolveLanguageCode($languageCode);
		if ($resolvedLanguageCode === '') {
			return;
		}

		$elementType = $this->resolveElementType($postType);
		$languageDetails = $this->getElementLanguageDetails($postId, $elementType);

		do_action(
			'wpml_set_element_language_details',
			[
				'element_id' => $postId,
				'element_type' => $elementType,
				'trid' => is_object($languageDetails) && isset($languageDetails->trid) ? (int) $languageDetails->trid : false,
				'language_code' => $resolvedLanguageCode,
				'source_language_code' => null,
			]
		);

		$assignedDetails = $this->getElementLanguageDetails($postId, $elementType);
		$assignedLanguageCode = is_object($assignedDetails) && isset($assignedDetails->language_code)
			? strtolower((string) $assignedDetails->language_code)
			: '';

		if ($assignedLanguageCode !== $resolvedLanguageCode) {
			$this->persistElementLanguageDetails(
				$postId,
				$elementType,
				is_object($languageDetails) && isset($languageDetails->trid) ? (int) $languageDetails->trid : 0,
				$resolvedLanguageCode,
				null
			);
		}
	}

	public function linkPostTranslation(int $sourcePostId, int $translatedPostId, string $postType, string $languageCode): void
	{
		if ($sourcePostId <= 0 || $translatedPostId <= 0 || $postType === '' || $languageCode === '' || !$this->isAvailable()) {
			return;
		}

		$resolvedLanguageCode = $this->resolveLanguageCode($languageCode);
		if ($resolvedLanguageCode === '') {
			return;
		}

		$elementType = $this->resolveElementType($postType);
		$sourceLanguageDetails = $this->getElementLanguageDetails($sourcePostId, $elementType);

		$sourceTrid = is_object($sourceLanguageDetails) && isset($sourceLanguageDetails->trid)
			? (int) $sourceLanguageDetails->trid
			: 0;
		$sourceLanguageCode = is_object($sourceLanguageDetails) && isset($sourceLanguageDetails->language_code)
			? strtolower((string) $sourceLanguageDetails->language_code)
			: '';

		if ($sourceTrid <= 0 || $sourceLanguageCode === '') {
			$this->assignPostLanguage($translatedPostId, $postType, $resolvedLanguageCode);
			return;
		}

		do_action(
			'wpml_set_element_language_details',
			[
				'element_id' => $translatedPostId,
				'element_type' => $elementType,
				'trid' => $sourceTrid,
				'language_code' => $resolvedLanguageCode,
				'source_language_code' => $sourceLanguageCode,
			]
		);

		$translatedDetails = $this->getElementLanguageDetails($translatedPostId, $elementType);
		$translatedTrid = is_object($translatedDetails) && isset($translatedDetails->trid)
			? (int) $translatedDetails->trid
			: 0;
		$translatedLanguageCode = is_object($translatedDetails) && isset($translatedDetails->language_code)
			? strtolower((string) $translatedDetails->language_code)
			: '';

		if ($translatedTrid !== $sourceTrid || $translatedLanguageCode !== $resolvedLanguageCode) {
			$this->persistElementLanguageDetails(
				$translatedPostId,
				$elementType,
				$sourceTrid,
				$resolvedLanguageCode,
				$sourceLanguageCode
			);
		}
	}

	public function getCurrentLanguageCode(): string
	{
		if (!$this->isAvailable()) {
			return '';
		}

		$currentLanguage = apply_filters('wpml_current_language', null);
		if (is_string($currentLanguage) && $currentLanguage !== '') {
			return strtolower(trim($currentLanguage));
		}

		if (defined('ICL_LANGUAGE_CODE') && is_string(ICL_LANGUAGE_CODE) && ICL_LANGUAGE_CODE !== '') {
			return strtolower(trim(ICL_LANGUAGE_CODE));
		}

		return '';
	}

	public function assignTermLanguage(int $termId, string $taxonomy, string $languageCode): void
	{
		if ($termId <= 0 || $taxonomy === '' || $languageCode === '' || !$this->isAvailable()) {
			return;
		}

		$resolvedLanguageCode = $this->resolveLanguageCode($languageCode);
		$termTaxonomyId = $this->resolveTermTaxonomyId($termId, $taxonomy);
		if ($resolvedLanguageCode === '' || $termTaxonomyId <= 0) {
			return;
		}

		$elementType = $this->resolveTaxonomyElementType($taxonomy);
		$languageDetails = apply_filters(
			'wpml_element_language_details',
			null,
			[
				'element_id' => $termTaxonomyId,
				'element_type' => $elementType,
			]
		);

		do_action(
			'wpml_set_element_language_details',
			[
				'element_id' => $termTaxonomyId,
				'element_type' => $elementType,
				'trid' => is_object($languageDetails) && isset($languageDetails->trid) ? (int) $languageDetails->trid : false,
				'language_code' => $resolvedLanguageCode,
				'source_language_code' => null,
			]
		);
	}

	public function linkTermTranslation(int $sourceTermId, int $translatedTermId, string $taxonomy, string $languageCode): void
	{
		if ($sourceTermId <= 0 || $translatedTermId <= 0 || $taxonomy === '' || $languageCode === '' || !$this->isAvailable()) {
			return;
		}

		$resolvedLanguageCode = $this->resolveLanguageCode($languageCode);
		$sourceTermTaxonomyId = $this->resolveTermTaxonomyId($sourceTermId, $taxonomy);
		$translatedTermTaxonomyId = $this->resolveTermTaxonomyId($translatedTermId, $taxonomy);
		if ($resolvedLanguageCode === '' || $sourceTermTaxonomyId <= 0 || $translatedTermTaxonomyId <= 0) {
			return;
		}

		$elementType = $this->resolveTaxonomyElementType($taxonomy);
		$sourceLanguageDetails = apply_filters(
			'wpml_element_language_details',
			null,
			[
				'element_id' => $sourceTermTaxonomyId,
				'element_type' => $elementType,
			]
		);

		$sourceTrid = is_object($sourceLanguageDetails) && isset($sourceLanguageDetails->trid)
			? (int) $sourceLanguageDetails->trid
			: 0;
		$sourceLanguageCode = is_object($sourceLanguageDetails) && isset($sourceLanguageDetails->language_code)
			? strtolower((string) $sourceLanguageDetails->language_code)
			: '';

		if ($sourceTrid <= 0 || $sourceLanguageCode === '') {
			$this->assignTermLanguage($translatedTermId, $taxonomy, $resolvedLanguageCode);
			return;
		}

		do_action(
			'wpml_set_element_language_details',
			[
				'element_id' => $translatedTermTaxonomyId,
				'element_type' => $elementType,
				'trid' => $sourceTrid,
				'language_code' => $resolvedLanguageCode,
				'source_language_code' => $sourceLanguageCode,
			]
		);
	}

	private function resolveElementType(string $postType): string
	{
		$defaultElementType = 'post_' . $postType;
		$filtered = apply_filters('wpml_element_type', $defaultElementType);

		return is_string($filtered) && $filtered !== '' ? $filtered : $defaultElementType;
	}

	private function resolveTaxonomyElementType(string $taxonomy): string
	{
		$defaultElementType = 'tax_' . $taxonomy;
		$filtered = apply_filters('wpml_element_type', $defaultElementType);

		return is_string($filtered) && $filtered !== '' ? $filtered : $defaultElementType;
	}

	private function resolveTermTaxonomyId(int $termId, string $taxonomy): int
	{
		$term = get_term($termId, $taxonomy);
		if (!is_object($term) || is_wp_error($term) || !isset($term->term_taxonomy_id)) {
			return 0;
		}

		return (int) $term->term_taxonomy_id;
	}

	private function resolveLanguageCode(string $languageCode): string
	{
		$languageCode = strtolower(trim($languageCode));
		if ($languageCode === '') {
			return '';
		}

		$activeLanguages = apply_filters(
			'wpml_active_languages',
			null,
			[
				'skip_missing' => 0,
				'orderby' => 'code',
			]
		);

		if (!is_array($activeLanguages) || $activeLanguages === []) {
			return $languageCode;
		}

		if (isset($activeLanguages[$languageCode])) {
			return $languageCode;
		}

		foreach ($activeLanguages as $activeLanguage) {
			$code = is_array($activeLanguage) ? strtolower((string) ($activeLanguage['code'] ?? '')) : '';
			if ($code === $languageCode) {
				return $code;
			}
		}

		return '';
	}

	private function getElementLanguageDetails(int $elementId, string $elementType): ?object
	{
		$details = apply_filters(
			'wpml_element_language_details',
			null,
			[
				'element_id' => $elementId,
				'element_type' => $elementType,
			]
		);

		return is_object($details) ? $details : null;
	}

	private function persistElementLanguageDetails(
		int $elementId,
		string $elementType,
		int $trid,
		string $languageCode,
		?string $sourceLanguageCode
	): void {
		global $wpdb;

		if ($elementId <= 0 || $elementType === '' || $languageCode === '' || !isset($wpdb->prefix)) {
			return;
		}

		$table = $wpdb->prefix . 'icl_translations';
		$existingTranslationId = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT translation_id FROM {$table} WHERE element_id = %d AND element_type = %s LIMIT 1",
				$elementId,
				$elementType
			)
		);

		if ($trid <= 0) {
			$trid = (int) $wpdb->get_var("SELECT COALESCE(MAX(trid), 0) + 1 FROM {$table}");
		}

		$data = [
			'element_type' => $elementType,
			'trid' => $trid,
			'language_code' => $languageCode,
			'source_language_code' => $sourceLanguageCode,
		];

		if ($existingTranslationId > 0) {
			$wpdb->update(
				$table,
				$data,
				[
					'translation_id' => $existingTranslationId,
				]
			);
			return;
		}

		$data['element_id'] = $elementId;
		$wpdb->insert($table, $data);
	}
}
