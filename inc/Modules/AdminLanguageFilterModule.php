<?php

namespace AutoGamesDiscountCreator\Modules;

use AutoGamesDiscountCreator\Core\Module\AbstractModule;
use AutoGamesDiscountCreator\Core\Settings\MarketTargetRepository;
use WP_Query;

class AdminLanguageFilterModule extends AbstractModule
{
	private const QUERY_FLAG = '_agdc_admin_lang_filter';

	public function setup()
	{
		$this->wpFunctions->addHook('pre_get_posts', 'applyLanguageFilter');
		$this->wpFunctions->addHook('posts_join', 'filterPostsJoin', 10, 2);
		$this->wpFunctions->addHook('posts_where', 'filterPostsWhere', 10, 2);
	}

	public function applyLanguageFilter(WP_Query $query): void
	{
		if (!$this->shouldFilterQuery($query)) {
			return;
		}

		$languageCode = $this->getRequestedLanguageCode();
		if ($languageCode === '') {
			return;
		}

		$query->set(self::QUERY_FLAG, $languageCode);
	}

	public function filterPostsJoin(string $join, WP_Query $query): string
	{
		$languageCode = $this->getQueryLanguageCode($query);
		if ($languageCode === '') {
			return $join;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'icl_translations';
		$elementType = $this->getElementTypeForQuery($query);
		if ($elementType === '') {
			return $join;
		}

		if (str_contains($join, 'agdc_admin_wpml')) {
			return $join;
		}

		return $join . $wpdb->prepare(
			" INNER JOIN {$table} agdc_admin_wpml ON {$wpdb->posts}.ID = agdc_admin_wpml.element_id AND agdc_admin_wpml.element_type = %s ",
			$elementType
		);
	}

	public function filterPostsWhere(string $where, WP_Query $query): string
	{
		$languageCode = $this->getQueryLanguageCode($query);
		if ($languageCode === '') {
			return $where;
		}

		global $wpdb;

		return $where . $wpdb->prepare(
			' AND agdc_admin_wpml.language_code = %s ',
			$languageCode
		);
	}

	private function shouldFilterQuery(WP_Query $query): bool
	{
		if (!is_admin() || !$query->is_main_query()) {
			return false;
		}

		global $pagenow;
		if ($pagenow !== 'edit.php') {
			return false;
		}

		$postType = $this->getPostTypeForQuery($query);

		return in_array($postType, ['post', 'agdc_roundup'], true);
	}

	private function getQueryLanguageCode(WP_Query $query): string
	{
		$languageCode = $query->get(self::QUERY_FLAG);

		return is_string($languageCode) ? $languageCode : '';
	}

	private function getRequestedLanguageCode(): string
	{
		$languageCode = isset($_GET['lang']) && is_string($_GET['lang']) ? sanitize_key(wp_unslash($_GET['lang'])) : '';
		if ($languageCode === '') {
			return '';
		}

		$target = (new MarketTargetRepository())->findByKey($languageCode);

		return is_array($target) ? $languageCode : '';
	}

	private function getPostTypeForQuery(WP_Query $query): string
	{
		$postType = $query->get('post_type');
		if (is_string($postType) && $postType !== '') {
			return $postType;
		}

		return isset($_GET['post_type']) && is_string($_GET['post_type'])
			? sanitize_key(wp_unslash($_GET['post_type']))
			: 'post';
	}

	private function getElementTypeForQuery(WP_Query $query): string
	{
		$postType = $this->getPostTypeForQuery($query);
		if ($postType === '') {
			return '';
		}

		return 'post_' . $postType;
	}
}
