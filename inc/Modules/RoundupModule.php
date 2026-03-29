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

		// Support root-level roundup URLs like /tr-2026-03-28-game-deals/.
		add_rewrite_rule(
			'^([a-z]{2}(?:-[a-z]{2})?-\d{4}-\d{2}-\d{2}-game-deals)/?$',
			'index.php?agdc_roundup=$matches[1]',
			'top'
		);
	}

	public function renderRoundupContent(string $content): string
	{
		if (is_admin() || !is_singular('agdc_roundup') || !in_the_loop() || !is_main_query()) {
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

		return $queryVars;
	}

	public function filterRoundupPermalink(string $postLink, $post): string
	{
		if (!is_object($post) || ($post->post_type ?? '') !== 'agdc_roundup') {
			return $postLink;
		}

		return home_url(user_trailingslashit($post->post_name));
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
}
