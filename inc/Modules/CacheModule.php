<?php

namespace AutoGamesDiscountCreator\Modules;

use AutoGamesDiscountCreator\Core\Module\AbstractModule;
use WP_Post;

class CacheModule extends AbstractModule
{
	private bool $purgeQueued = false;

	/** @var array<string, bool> */
	private array $purgeUrls = [];

	/** @var array<int, bool> */
	private array $purgePostIds = [];

	public function setup()
	{
		$this->wpFunctions->addHook('transition_post_status', 'queuePurgeOnPostTransition', 10, 3);
		$this->wpFunctions->addHook('deleted_post', 'queuePurgeOnDelete', 10, 1);
		$this->wpFunctions->addHook('shutdown', 'flushQueuedPurge', 999, 0);
	}

	public function queuePurgeOnPostTransition(string $newStatus, string $oldStatus, WP_Post $post): void
	{
		if (($post->post_type ?? '') !== 'agdc_roundup') {
			return;
		}

		if ($newStatus === $oldStatus && $newStatus !== 'publish') {
			return;
		}

		$this->queueRoundupUrls((int) $post->ID);
	}

	public function queuePurgeOnDelete(int $postId): void
	{
		if (get_post_type($postId) !== 'agdc_roundup') {
			return;
		}

		$this->queueRoundupUrls($postId);
	}

	public function flushQueuedPurge(): void
	{
		if (!$this->purgeQueued) {
			return;
		}

		foreach (array_keys($this->purgePostIds) as $postId) {
			clean_post_cache((int) $postId);
		}

		wp_cache_flush();

		if (function_exists('delete_expired_transients')) {
			delete_expired_transients();
		}

		$this->purgeSuperCacheTree();
		$this->purgeQueued = false;
		$this->purgeUrls = [];
		$this->purgePostIds = [];
	}

	private function queueRoundupUrls(int $postId): void
	{
		$this->purgeQueued = true;
		$this->purgePostIds[$postId] = true;

		$permalink = get_permalink($postId);
		if (is_string($permalink) && $permalink !== '') {
			$this->purgeUrls[$permalink] = true;
		}

		$this->purgeUrls[home_url('/')] = true;

		$languages = apply_filters(
			'wpml_active_languages',
			null,
			[
				'skip_missing' => 0,
				'orderby' => 'code',
			]
		);

		if (!is_array($languages) || $languages === []) {
			return;
		}

		foreach ($languages as $language) {
			$code = is_array($language) ? trim((string) ($language['code'] ?? '')) : '';
			if ($code === '') {
				continue;
			}

			$this->purgeUrls[home_url('/' . $code . '/')] = true;
		}
	}

	private function purgeSuperCacheTree(): void
	{
		$cacheDir = WP_CONTENT_DIR . '/cache/supercache';
		$host = wp_parse_url(home_url('/'), PHP_URL_HOST);
		if (!is_string($host) || $host === '') {
			return;
		}

		$target = trailingslashit($cacheDir) . $host;
		if (!is_dir($target)) {
			return;
		}

		$this->deleteDirectory($target);
	}

	private function deleteDirectory(string $directory): void
	{
		$items = @scandir($directory);
		if (!is_array($items)) {
			return;
		}

		foreach ($items as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}

			$path = $directory . '/' . $item;
			if (is_dir($path)) {
				$this->deleteDirectory($path);
				@rmdir($path);
				continue;
			}

			@unlink($path);
		}

		@rmdir($directory);
	}
}
