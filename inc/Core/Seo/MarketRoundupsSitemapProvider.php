<?php

namespace AutoGamesDiscountCreator\Core\Seo;

use AutoGamesDiscountCreator\Core\Settings\MarketTargetRepository;
use WP_Post;
use WP_Query;
use WP_Sitemaps_Provider;

class MarketRoundupsSitemapProvider extends WP_Sitemaps_Provider
{
	public function __construct()
	{
		$this->name        = 'marketroundups';
		$this->object_type = 'posts';
	}

	public function get_object_subtypes(): array
	{
		$targets = (new MarketTargetRepository())->getRolloutTargets();
		$subtypes = [];

		foreach ($targets as $target) {
			$key = (string) ($target['market_key'] ?? '');
			if ($key === '') {
				continue;
			}

			$subtypes[$key] = $key;
		}

		return $subtypes;
	}

	public function get_url_list($page_num, $object_subtype = ''): array
	{
		$marketKey = is_string($object_subtype) ? $object_subtype : '';
		if ($marketKey === '') {
			return [];
		}

		$query = new WP_Query([
			'post_type'              => 'agdc_roundup',
			'post_status'            => 'publish',
			'posts_per_page'         => $this->getMaxUrls(),
			'paged'                  => max(1, (int) $page_num),
			'orderby'                => 'ID',
			'order'                  => 'DESC',
			'fields'                 => 'all',
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => [
				[
					'key'     => '_agdc_market_key',
					'value'   => $marketKey,
					'compare' => '=',
				],
			],
		]);

		$urls = [];
		foreach ($query->posts as $post) {
			if (!$post instanceof WP_Post) {
				continue;
			}

			$urls[] = [
				'loc'     => get_permalink($post),
				'lastmod' => mysql2date(DATE_W3C, $post->post_modified_gmt ?: $post->post_modified, false),
			];
		}

		wp_reset_postdata();

		return $urls;
	}

	public function get_max_num_pages($object_subtype = ''): int
	{
		$marketKey = is_string($object_subtype) ? $object_subtype : '';
		if ($marketKey === '') {
			return 0;
		}

		$query = new WP_Query([
			'post_type'              => 'agdc_roundup',
			'post_status'            => 'publish',
			'posts_per_page'         => $this->getMaxUrls(),
			'paged'                  => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => [
				[
					'key'     => '_agdc_market_key',
					'value'   => $marketKey,
					'compare' => '=',
				],
			],
		]);

		$pages = (int) ($query->max_num_pages ?? 0);
		wp_reset_postdata();

		return $pages;
	}

	private function getMaxUrls(): int
	{
		return function_exists('wp_sitemaps_get_max_urls')
			? (int) wp_sitemaps_get_max_urls($this->object_type)
			: 2000;
	}
}
