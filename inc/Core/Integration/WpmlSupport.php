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
		$languageDetails = apply_filters(
			'wpml_element_language_details',
			null,
			[
				'element_id' => $postId,
				'element_type' => $elementType,
			]
		);

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
		$sourceLanguageDetails = apply_filters(
			'wpml_element_language_details',
			null,
			[
				'element_id' => $sourcePostId,
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

	private function resolveElementType(string $postType): string
	{
		$defaultElementType = 'post_' . $postType;
		$filtered = apply_filters('wpml_element_type', $defaultElementType);

		return is_string($filtered) && $filtered !== '' ? $filtered : $defaultElementType;
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
}
