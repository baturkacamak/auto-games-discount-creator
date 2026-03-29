<?php

namespace AutoGamesDiscountCreator\Modules;

use AutoGamesDiscountCreator\Core\Module\AbstractModule;
use AutoGamesDiscountCreator\Core\Settings\MarketTargetRepository;

class SeoModule extends AbstractModule
{
	public function setup()
	{
		$this->wpFunctions->addHook('pre_get_document_title', 'filterDocumentTitle', 20);
		$this->wpFunctions->addHook('document_title_parts', 'filterDocumentTitleParts', 20);
		$this->wpFunctions->addHook('wp_head', 'renderHeadMeta', 1);
		$this->wpFunctions->addHook('wp_head', 'renderSchema', 20);
		$this->wpFunctions->addHook('wp_robots', 'filterRobots');
		$this->wpFunctions->addHook('wp_sitemaps_taxonomies', 'filterSitemapTaxonomies');
		$this->wpFunctions->addHook('wp_sitemaps_add_provider', 'filterSitemapProviders', 10, 2);
	}

	public function filterDocumentTitle(string $title): string
	{
		$postId = $this->getSeoTargetPostId();
		if ($postId <= 0) {
			return $title;
		}

		$postTitle = get_the_title($postId);

		return is_string($postTitle) && $postTitle !== '' ? $postTitle : $title;
	}

	public function filterDocumentTitleParts(array $parts): array
	{
		$postId = $this->getSeoTargetPostId();
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

	public function renderHeadMeta(): void
	{
		if (is_admin()) {
			return;
		}

		$postId = $this->getSeoTargetPostId();
		if ($postId <= 0) {
			return;
		}

		$post = get_post($postId);
		if (!is_object($post)) {
			return;
		}

		$meta = $this->buildMeta($post);
		if ($meta === null) {
			return;
		}

		if ($meta['description'] !== '') {
			echo '<meta name="description" content="' . esc_attr($meta['description']) . '">' . "\n";
			echo '<meta property="og:description" content="' . esc_attr($meta['description']) . '">' . "\n";
			echo '<meta name="twitter:description" content="' . esc_attr($meta['description']) . '">' . "\n";
		}

		if ($meta['canonical'] !== '') {
			echo '<link rel="canonical" href="' . esc_url($meta['canonical']) . '">' . "\n";
			echo '<meta property="og:url" content="' . esc_url($meta['canonical']) . '">' . "\n";
		}

		echo '<meta property="og:type" content="' . esc_attr($meta['og_type']) . '">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr($meta['title']) . '">' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr($meta['title']) . '">' . "\n";

		if ($meta['image'] !== '') {
			echo '<meta property="og:image" content="' . esc_url($meta['image']) . '">' . "\n";
			echo '<meta name="twitter:image" content="' . esc_url($meta['image']) . '">' . "\n";
		}

		foreach ($this->getAlternateLinks($post) as $alternate) {
			echo '<link rel="alternate" hreflang="' . esc_attr($alternate['hreflang']) . '" href="' . esc_url($alternate['url']) . '">' . "\n";
		}
	}

	public function renderSchema(): void
	{
		if (is_admin()) {
			return;
		}

		$postId = $this->getSeoTargetPostId();
		if ($postId <= 0) {
			return;
		}

		$post = get_post($postId);
		if (!is_object($post)) {
			return;
		}

		$schema = $this->buildSchemaGraph($post);
		if ($schema === []) {
			return;
		}

		echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
	}

	public function filterRobots(array $robots): array
	{
		if (is_admin()) {
			return $robots;
		}

		if (is_search() || is_404() || is_date() || is_tag() || (is_paged() && !is_singular())) {
			return [
				'noindex' => true,
				'follow' => true,
				'max-image-preview' => 'large',
			];
		}

		$robots['max-image-preview'] = 'large';

		return $robots;
	}

	public function filterSitemapTaxonomies(array $taxonomies): array
	{
		unset($taxonomies['post_tag']);

		return $taxonomies;
	}

	public function filterSitemapProviders($provider, string $name)
	{
		if ($name === 'users') {
			return false;
		}

		return $provider;
	}

	private function getSeoTargetPostId(): int
	{
		if (is_singular('agdc_roundup') || (is_singular('post') && get_post_meta(get_queried_object_id(), '_agdc_content_kind', true) === 'free_game')) {
			return (int) get_queried_object_id();
		}

		return 0;
	}

	private function buildSchemaGraph(\WP_Post $post): array
	{
		$meta = $this->buildMeta($post);
		if ($meta === null) {
			return [];
		}

		$marketKey = (string) get_post_meta($post->ID, '_agdc_market_key', true);
		$repo = new MarketTargetRepository();
		$marketTarget = $marketKey !== '' ? ($repo->findByKey($marketKey) ?: $repo->getDefaultTarget()) : $repo->getDefaultTarget();
		$locale = strtolower((string) (($marketTarget['language_code'] ?? 'en') . '-' . ($marketTarget['country_code'] ?? 'US')));

		$graph = [];
		$graph[] = [
			'@context' => 'https://schema.org',
			'@type' => 'WebSite',
			'name' => get_bloginfo('name'),
			'url' => home_url('/'),
			'description' => get_bloginfo('description'),
			'inLanguage' => $locale,
		];

		if ($post->post_type === 'agdc_roundup') {
			$snapshot = get_post_meta($post->ID, '_agdc_snapshot_payload', true);
			$games = is_array($snapshot['games'] ?? null) ? $snapshot['games'] : [];

			$graph[] = [
				'@context' => 'https://schema.org',
				'@type' => 'CollectionPage',
				'name' => $meta['title'],
				'url' => $meta['canonical'],
				'description' => $meta['description'],
				'inLanguage' => $locale,
			];

			if ($games !== []) {
				$itemList = [
					'@context' => 'https://schema.org',
					'@type' => 'ItemList',
					'name' => $meta['title'],
					'itemListElement' => [],
				];

				foreach ($games as $index => $game) {
					if (!is_array($game)) {
						continue;
					}

					$itemList['itemListElement'][] = [
						'@type' => 'ListItem',
						'position' => $index + 1,
						'url' => (string) ($game['url'] ?? $meta['canonical']),
						'name' => (string) ($game['name'] ?? ''),
					];
				}

				$graph[] = $itemList;
			}
		}

		if ($post->post_type === 'post' && get_post_meta($post->ID, '_agdc_content_kind', true) === 'free_game') {
			$graph[] = [
				'@context' => 'https://schema.org',
				'@type' => 'BlogPosting',
				'headline' => $meta['title'],
				'mainEntityOfPage' => $meta['canonical'],
				'datePublished' => get_post_time(DATE_W3C, true, $post),
				'dateModified' => get_post_modified_time(DATE_W3C, true, $post),
				'description' => $meta['description'],
				'inLanguage' => $locale,
				'author' => [
					'@type' => 'Person',
					'name' => get_the_author_meta('display_name', (int) $post->post_author),
				],
				'publisher' => [
					'@type' => 'Organization',
					'name' => get_bloginfo('name'),
				],
				'image' => $meta['image'] !== '' ? [$meta['image']] : [],
			];
		}

		$breadcrumb = $this->buildBreadcrumbSchema($post, $meta['canonical']);
		if ($breadcrumb !== null) {
			$graph[] = $breadcrumb;
		}

		return $graph;
	}

	private function buildMeta(\WP_Post $post): ?array
	{
		$title = get_the_title($post);
		$canonical = get_permalink($post);
		if (!is_string($title) || $title === '' || !is_string($canonical) || $canonical === '') {
			return null;
		}

		$marketKey = (string) get_post_meta($post->ID, '_agdc_market_key', true);
		$repo = new MarketTargetRepository();
		$marketTarget = $marketKey !== '' ? ($repo->findByKey($marketKey) ?: $repo->getDefaultTarget()) : $repo->getDefaultTarget();
		$copySet = $repo->getCopySet($marketTarget);

		if ($post->post_type === 'agdc_roundup') {
			$snapshot = get_post_meta($post->ID, '_agdc_snapshot_payload', true);
			$description = is_array($snapshot) ? $this->buildRoundupDescription($snapshot, $copySet, $marketTarget) : '';
			$image = is_array($snapshot) ? $this->getRoundupImage($snapshot) : '';

			return [
				'title' => $title,
				'description' => $description,
				'canonical' => $canonical,
				'image' => $image,
				'og_type' => 'article',
			];
		}

		if ($post->post_type === 'post' && get_post_meta($post->ID, '_agdc_content_kind', true) === 'free_game') {
			$description = $this->buildFreeGameDescription($post, $copySet, $marketTarget);
			$image = $this->getFreeGameImage($post);

			return [
				'title' => $title,
				'description' => $description,
				'canonical' => $canonical,
				'image' => $image,
				'og_type' => 'article',
			];
		}

		return null;
	}

	private function buildRoundupDescription(array $snapshot, array $copySet, array $marketTarget): string
	{
		$games = is_array($snapshot['games'] ?? null) ? $snapshot['games'] : [];
		$count = count($games);
		$currency = strtoupper((string) ($marketTarget['default_currency_code'] ?? ''));
		$language = strtolower((string) ($marketTarget['language_code'] ?? 'en'));

		if ($language === 'tr') {
			return trim(sprintf('%d oyunluk günlük indirim seçkisi. Fiyatlar %s üzerinden gösterilir.', $count, $currency ?: 'yerel para birimi'));
		}

		if ($language === 'es') {
			return trim(sprintf('Selección diaria con %d juegos. Los precios se muestran en %s.', $count, $currency ?: 'la moneda local'));
		}

		if ($language === 'ro') {
			return trim(sprintf('Selecție zilnică cu %d jocuri. Prețurile sunt afișate în %s.', $count, $currency ?: 'moneda locală'));
		}

		return trim(sprintf('Daily roundup with %d games. Prices are shown in %s.', $count, $currency ?: 'the local currency'));
	}

	private function buildFreeGameDescription(\WP_Post $post, array $copySet, array $marketTarget): string
	{
		$title = get_the_title($post);
		$currency = strtoupper((string) ($marketTarget['default_currency_code'] ?? ''));
		$language = strtolower((string) ($marketTarget['language_code'] ?? 'en'));

		if ($language === 'tr') {
			return trim(sprintf('%s için ücretsiz oyun fırsatı. Market fiyat gösterimi %s bazlıdır.', $title, $currency ?: 'yerel para birimi'));
		}

		if ($language === 'es') {
			return trim(sprintf('Oferta de juego gratis para %s. La referencia de mercado usa %s.', $title, $currency ?: 'la moneda local'));
		}

		if ($language === 'ro') {
			return trim(sprintf('Ofertă de joc gratuit pentru %s. Referința de piață folosește %s.', $title, $currency ?: 'moneda locală'));
		}

		return trim(sprintf('Free game deal for %s. Market pricing reference uses %s.', $title, $currency ?: 'the local currency'));
	}

	private function getRoundupImage(array $snapshot): string
	{
		$games = is_array($snapshot['games'] ?? null) ? $snapshot['games'] : [];
		foreach ($games as $game) {
			if (!is_array($game)) {
				continue;
			}

			$image = (string) ($game['resolved_image_url'] ?? '');
			if ($image !== '') {
				return $image;
			}
		}

		return '';
	}

	private function getFreeGameImage(\WP_Post $post): string
	{
		if (has_post_thumbnail($post)) {
			$image = get_the_post_thumbnail_url($post, 'full');
			if (is_string($image) && $image !== '') {
				return $image;
			}
		}

		return '';
	}

	private function getAlternateLinks(\WP_Post $post): array
	{
		$alternates = [];
		if (!has_filter('wpml_element_language_details')) {
			return $alternates;
		}

		$elementType = apply_filters('wpml_element_type', 'post_' . $post->post_type);
		$details = apply_filters('wpml_element_language_details', null, [
			'element_id' => $post->ID,
			'element_type' => $elementType,
		]);

		if (!is_object($details) || empty($details->trid)) {
			return $alternates;
		}

		$translations = apply_filters('wpml_get_element_translations', null, $details->trid, $elementType);
		if (!is_array($translations)) {
			return $alternates;
		}

		foreach ($translations as $translation) {
			if (!is_object($translation) || empty($translation->element_id) || empty($translation->language_code)) {
				continue;
			}

			$url = get_permalink((int) $translation->element_id);
			if (!is_string($url) || $url === '') {
				continue;
			}

			$alternates[] = [
				'hreflang' => strtolower((string) $translation->language_code),
				'url' => $url,
			];
		}

		return $alternates;
	}

	private function buildBreadcrumbSchema(\WP_Post $post, string $canonical): ?array
	{
		$items = [
			[
				'name' => get_bloginfo('name'),
				'url' => home_url('/'),
			],
		];

		if ($post->post_type === 'agdc_roundup') {
			$items[] = [
				'name' => get_the_title($post),
				'url' => $canonical,
			];
		} elseif ($post->post_type === 'post') {
			$categories = get_the_category($post->ID);
			if (!empty($categories) && isset($categories[0]) && $categories[0] instanceof \WP_Term) {
				$items[] = [
					'name' => $categories[0]->name,
					'url' => get_term_link($categories[0]),
				];
			}
			$items[] = [
				'name' => get_the_title($post),
				'url' => $canonical,
			];
		}

		if (count($items) < 2) {
			return null;
		}

		$list = [
			'@context' => 'https://schema.org',
			'@type' => 'BreadcrumbList',
			'itemListElement' => [],
		];

		foreach ($items as $index => $item) {
			$list['itemListElement'][] = [
				'@type' => 'ListItem',
				'position' => $index + 1,
				'name' => $item['name'],
				'item' => $item['url'],
			];
		}

		return $list;
	}
}
