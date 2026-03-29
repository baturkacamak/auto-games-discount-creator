<?php

namespace AutoGamesDiscountCreator\Modules;

use AutoGamesDiscountCreator\Core\Module\AbstractModule;
use AutoGamesDiscountCreator\Core\Settings\MarketTargetRepository;
use AutoGamesDiscountCreator\Core\Utility\UtilityFactory;
use AutoGamesDiscountCreator\Post\DailyRoundupSnapshotRenderer;

class RoundupModule extends AbstractModule
{
	public function setup()
	{
		$this->wpFunctions->addHook('init', 'registerRoundupPostType');
		$this->wpFunctions->addHook('the_content', 'renderRoundupContent', 20);
		$this->wpFunctions->addHook('request', 'mapRoundupRequest');
		$this->wpFunctions->addHook('post_type_link', 'filterRoundupPermalink', 10, 2);
		$this->wpFunctions->addHook('pre_get_document_title', 'filterRoundupDocumentTitle', 20);
		$this->wpFunctions->addHook('document_title_parts', 'filterRoundupDocumentTitleParts');
		$this->wpFunctions->addHook('wp_head', 'renderRoundupHeadMeta');
	}

	public function registerRoundupPostType(): void
	{
		register_post_type(
			'agdc_roundup',
			[
				'labels' => [
					'name' => 'AGDC Roundups',
					'singular_name' => 'AGDC Roundup',
				],
				'public' => true,
				'show_ui' => true,
				'show_in_rest' => true,
				'has_archive' => false,
				'rewrite' => ['slug' => '', 'with_front' => false],
				'supports' => ['title', 'editor', 'excerpt', 'author', 'custom-fields'],
				'taxonomies' => ['category', 'post_tag'],
				'publicly_queryable' => true,
				'exclude_from_search' => false,
			]
		);

		// Support WPML-style market-prefixed roundup URLs like /tr-tr/28-mart-2026-oyun-indirimleri/.
		add_rewrite_rule(
			'^([a-z]{2}-[a-z]{2})/([^/]+)/?$',
			'index.php?lang=$matches[1]&agdc_roundup=$matches[2]&post_type=agdc_roundup',
			'top'
		);

		// Legacy support for previous root-level roundup URLs.
		add_rewrite_rule(
			'^([a-z]{2}(?:-[a-z]{2})?-\d{4}-\d{2}-\d{2}-game-deals)/?$',
			'index.php?agdc_roundup=$matches[1]',
			'top'
		);
	}

	public function renderRoundupContent(string $content): string
	{
		if (is_admin() || !is_singular('agdc_roundup')) {
			return $content;
		}

		$postId = get_the_ID();
		if (!$postId) {
			return $content;
		}

		$snapshot = get_post_meta($postId, '_agdc_snapshot_payload', true);
		if (!is_array($snapshot) || empty($snapshot['games'])) {
			return $content;
		}

		$enrichedSnapshot = $this->enrichSnapshotImages($snapshot);
		if ($enrichedSnapshot !== $snapshot) {
			update_post_meta($postId, '_agdc_snapshot_payload', $enrichedSnapshot);
			$snapshot = $enrichedSnapshot;
		}

		$marketKey = (string) get_post_meta($postId, '_agdc_market_key', true);
		$repo = new MarketTargetRepository();
		$marketTarget = $marketKey !== '' ? ($repo->findByKey($marketKey) ?: $repo->getDefaultTarget()) : $repo->getDefaultTarget();
		$copySet = $repo->getCopySet($marketTarget);

		return (new DailyRoundupSnapshotRenderer())->render($snapshot, $copySet);
	}

	public function mapRoundupRequest(array $queryVars): array
	{
		if (is_admin() || !empty($queryVars['post_type'])) {
			return $queryVars;
		}

		$requestedSlug = '';
		if (!empty($queryVars['name']) && is_string($queryVars['name'])) {
			$requestedSlug = (string) $queryVars['name'];
		} elseif (!empty($queryVars['pagename']) && is_string($queryVars['pagename'])) {
			$requestedSlug = trim((string) $queryVars['pagename'], '/');
		}

		if ($requestedSlug === '' || str_contains($requestedSlug, '/')) {
			return $queryVars;
		}

		global $wpdb;
		$postId = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s AND post_status IN ('publish','draft','private') LIMIT 1",
				$requestedSlug,
				'agdc_roundup'
			)
		);

		if ($postId <= 0) {
			return $queryVars;
		}

		unset($queryVars['name']);
		unset($queryVars['pagename']);
		$queryVars['agdc_roundup'] = get_post_field('post_name', $postId);
		$queryVars['post_type'] = 'agdc_roundup';
		$marketKey = (string) get_post_meta($postId, '_agdc_market_key', true);
		if ($marketKey !== '') {
			$queryVars['lang'] = $marketKey;
			do_action('wpml_switch_language', $marketKey);
		}

		return $queryVars;
	}

	public function filterRoundupPermalink(string $postLink, $post): string
	{
		if (!is_object($post) || ($post->post_type ?? '') !== 'agdc_roundup') {
			return $postLink;
		}

		$marketKey = (string) get_post_meta($post->ID, '_agdc_market_key', true);
		if ($marketKey === '') {
			$marketKey = 'tr-tr';
		}

		return home_url(user_trailingslashit($marketKey . '/' . $post->post_name));
	}

	public function filterRoundupDocumentTitle(string $title): string
	{
		$postId = $this->getCurrentRoundupPostId();
		if ($postId <= 0) {
			return $title;
		}

		$postTitle = get_the_title($postId);
		if (!is_string($postTitle) || $postTitle === '') {
			return $title;
		}

		return $postTitle;
	}

	public function filterRoundupDocumentTitleParts(array $parts): array
	{
		$postId = $this->getCurrentRoundupPostId();
		if ($postId <= 0) {
			return $parts;
		}

		$postTitle = get_the_title($postId);
		if (!is_string($postTitle) || $postTitle === '') {
			return $parts;
		}

		$parts['title'] = $postTitle;
		$parts['site'] = '';
		$parts['tagline'] = '';

		return $parts;
	}

	public function renderRoundupHeadMeta(): void
	{
		$postId = $this->getCurrentRoundupPostId();
		if ($postId <= 0) {
			return;
		}

		$snapshot = get_post_meta($postId, '_agdc_snapshot_payload', true);
		if (!is_array($snapshot)) {
			return;
		}

		$marketKey = (string) get_post_meta($postId, '_agdc_market_key', true);
		$repo = new MarketTargetRepository();
		$marketTarget = $marketKey !== '' ? ($repo->findByKey($marketKey) ?: $repo->getDefaultTarget()) : $repo->getDefaultTarget();
		$copySet = $repo->getCopySet($marketTarget);
		$description = $this->buildRoundupDescription($snapshot, $copySet);
		$canonical = get_permalink($postId);

		if (is_string($description) && $description !== '') {
			echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
			echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
			echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
		}

		if (is_string($canonical) && $canonical !== '') {
			echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
			echo '<meta property="og:url" content="' . esc_url($canonical) . '">' . "\n";
		}

		echo '<meta property="og:type" content="article">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr(get_the_title($postId)) . '">' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr(get_the_title($postId)) . '">' . "\n";

		foreach ($this->getRoundupAlternateLinks($postId) as $alternate) {
			echo '<link rel="alternate" hreflang="' . esc_attr($alternate['hreflang']) . '" href="' . esc_url($alternate['url']) . '">' . "\n";
		}
	}

	private function enrichSnapshotImages(array $snapshot): array
	{
		$games = $snapshot['games'] ?? null;
		if (!is_array($games) || $games === []) {
			return $snapshot;
		}

		$resolver = (new UtilityFactory())->createOfferImageResolver();
		$changed = false;

		foreach ($games as $index => $game) {
			if (!is_array($game)) {
				continue;
			}

			if (!empty($game['resolved_image_url'])) {
				continue;
			}

			$imageUrl = $resolver->resolve([
				'url' => (string) ($game['url'] ?? ''),
				'store_key' => (string) ($game['store_key'] ?? ''),
				'thumbnail_url' => (string) ($game['raw_thumbnail_url'] ?? ''),
			]);

			if (!is_string($imageUrl) || $imageUrl === '') {
				continue;
			}

			$games[$index]['resolved_image_url'] = $imageUrl;
			$changed = true;
		}

		if (!$changed) {
			return $snapshot;
		}

		$snapshot['games'] = $games;

		return $snapshot;
	}

	private function buildRoundupDescription(array $snapshot, array $copySet): string
	{
		$games = is_array($snapshot['games'] ?? null) ? $snapshot['games'] : [];
		$count = count($games);
		if ($count === 0) {
			return '';
		}

		$currency = strtoupper((string) ($games[0]['currency_code'] ?? 'USD'));
		$language = strtolower((string) ($copySet['hreflang'] ?? 'en-us'));

		if (str_starts_with($language, 'tr')) {
			return sprintf('%d oyun indirimi. Fiyatlar %s bazında gösteriliyor.', $count, $currency);
		}

		if (str_starts_with($language, 'es')) {
			return sprintf('%d ofertas de juegos para hoy. Los precios se muestran en %s.', $count, $currency);
		}

		if (str_starts_with($language, 'ro')) {
			return sprintf('%d oferte de jocuri pentru astăzi. Prețurile sunt afișate în %s.', $count, $currency);
		}

		return sprintf('%d PC game deals for today. Prices are shown in %s.', $count, $currency);
	}

	private function getRoundupAlternateLinks(int $postId): array
	{
		global $wpdb;

		$iclTable = $wpdb->prefix . 'icl_translations';
		$current = $wpdb->get_row(
			$wpdb->prepare("SELECT trid FROM {$iclTable} WHERE element_id = %d LIMIT 1", $postId),
			ARRAY_A
		);

		if (!is_array($current) || empty($current['trid'])) {
			return [];
		}

		$translations = $wpdb->get_results(
			$wpdb->prepare("SELECT element_id, language_code FROM {$iclTable} WHERE trid = %d", (int) $current['trid']),
			ARRAY_A
		);

		$alternates = [];
		foreach ($translations as $translation) {
			$translatedPostId = (int) ($translation['element_id'] ?? 0);
			if ($translatedPostId <= 0) {
				continue;
			}

			$url = get_permalink($translatedPostId);
			$hreflang = strtolower((string) ($translation['language_code'] ?? ''));
			if (!is_string($url) || $url === '' || $hreflang === '') {
				continue;
			}

			$alternates[] = [
				'hreflang' => $hreflang,
				'url' => $url,
			];
		}

		return $alternates;
	}

	private function getCurrentRoundupPostId(): int
	{
		$postId = (int) get_queried_object_id();
		if ($postId > 0 && get_post_type($postId) === 'agdc_roundup') {
			return $postId;
		}

		global $post;
		if (is_object($post) && isset($post->ID) && get_post_type((int) $post->ID) === 'agdc_roundup') {
			return (int) $post->ID;
		}

		$slug = get_query_var('agdc_roundup');
		if (is_string($slug) && $slug !== '') {
			$roundup = get_page_by_path($slug, OBJECT, 'agdc_roundup');
			if (is_object($roundup) && isset($roundup->ID)) {
				return (int) $roundup->ID;
			}
		}

		return 0;
	}
}
