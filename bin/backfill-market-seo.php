<?php

use AutoGamesDiscountCreator\Core\Integration\WpmlSupport;
use AutoGamesDiscountCreator\Core\Settings\MarketTargetRepository;
use AutoGamesDiscountCreator\Core\Utility\LocalizedTaxonomyResolver;

if (!defined('ABSPATH')) {
	exit("Run this file with wp eval-file.\n");
}

$repo = new MarketTargetRepository();
$resolver = new LocalizedTaxonomyResolver();
$wpml = new WpmlSupport();

$targetsByKey = [];
$candidateKeys = ['tr-tr', 'en-gb', 'en-us', 'es-es', 'es-mx'];
foreach ($candidateKeys as $candidateKey) {
	$target = $repo->findByKey($candidateKey);
	if (!is_array($target) || empty($target['key'])) {
		continue;
	}

	$targetsByKey[(string) $target['key']] = $target;
}

$query = new WP_Query([
	'post_type' => ['agdc_roundup', 'post'],
	'post_status' => ['publish', 'draft', 'private'],
	'posts_per_page' => -1,
	'orderby' => 'ID',
	'order' => 'ASC',
	'meta_query' => [
		'relation' => 'OR',
		[
			'key' => '_agdc_content_kind',
			'value' => 'discount_roundup',
		],
		[
			'key' => '_agdc_content_kind',
			'value' => 'free_game',
		],
	],
]);

$updated = 0;

foreach ($query->posts as $post) {
	if (!$post instanceof WP_Post) {
		continue;
	}

	$contentKind = (string) get_post_meta($post->ID, '_agdc_content_kind', true);
	if (!in_array($contentKind, ['discount_roundup', 'free_game'], true)) {
		continue;
	}

	$marketKey = (string) get_post_meta($post->ID, '_agdc_market_key', true);
	if ($marketKey === '' && $wpml->isAvailable()) {
		$elementType = apply_filters('wpml_element_type', 'post_' . $post->post_type);
		$details = apply_filters('wpml_element_language_details', null, [
			'element_id' => $post->ID,
			'element_type' => $elementType,
		]);

		if (is_object($details) && !empty($details->language_code)) {
			$marketKey = (string) $details->language_code;
			update_post_meta($post->ID, '_agdc_market_key', $marketKey);
		}
	}

	if ($marketKey === '' || empty($targetsByKey[$marketKey])) {
		$marketKey = (string) ($repo->getDefaultTarget()['key'] ?? 'tr-tr');
		update_post_meta($post->ID, '_agdc_market_key', $marketKey);
	}

	$target = $targetsByKey[$marketKey] ?? $repo->getDefaultTarget();
	$copySet = $repo->getCopySet($target);

	update_post_meta($post->ID, '_agdc_language_code', (string) ($target['language_code'] ?? ''));
	update_post_meta($post->ID, '_agdc_site_section', (string) ($target['site_section'] ?? ''));

	$resolver->assignTermsToPost($post->ID, $target, $contentKind, $copySet);

	echo sprintf(
		"Updated post %d [%s] market=%s kind=%s\n",
		$post->ID,
		$post->post_type,
		$marketKey,
		$contentKind
	);
	$updated++;
}

echo sprintf("Done. Updated %d AGDC posts.\n", $updated);
