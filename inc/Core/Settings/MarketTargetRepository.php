<?php

namespace AutoGamesDiscountCreator\Core\Settings;

use AutoGamesDiscountCreator\Core\Integration\WpmlSupport;

class MarketTargetRepository
{
	public function getDefaultTarget(): array
	{
		$currentTarget = $this->getCurrentTarget();
		if ($currentTarget !== null) {
			return $currentTarget;
		}

		$settings = (new SettingsRepository())->getAll();
		$market_key = (string) ($settings['data_model']['default_market_target_key'] ?? 'tr-tr');

		return $this->findByKey($market_key) ?? $this->getFallbackTarget($market_key);
	}

	public function getCurrentTarget(): ?array
	{
		$wpmlLanguageCode = (new WpmlSupport())->getCurrentLanguageCode();
		if ($wpmlLanguageCode === '') {
			return null;
		}

		return $this->findByLanguageCode($wpmlLanguageCode);
	}

	public function getRolloutTargets(): array
	{
		$settings = (new SettingsRepository())->getAll();
		$keys = $settings['data_model']['rollout_market_target_keys'] ?? [];
		if (!is_array($keys) || $keys === []) {
			return [$this->getDefaultTarget()];
		}

		$targets = [];
		foreach ($keys as $key) {
			if (!is_string($key) || $key === '') {
				continue;
			}

			$target = $this->findByKey($key);
			if ($target !== null) {
				$targets[] = $target;
			}
		}

		return $targets !== [] ? $targets : [$this->getDefaultTarget()];
	}

	public function findByKey(string $marketKey): ?array
	{
		global $wpdb;

		$table = $wpdb->prefix . 'agdc_market_targets';
		$target = $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$table} WHERE market_key = %s LIMIT 1", $marketKey),
			ARRAY_A
		);

		if (!is_array($target)) {
			return null;
		}

		return $this->decorateTarget($target);
	}

	public function findByLanguageCode(string $languageCode): ?array
	{
		$languageCode = $this->normalizeLanguageCode($languageCode);
		if ($languageCode === '') {
			return null;
		}

		$exactTarget = $this->findByKey($languageCode);
		if ($exactTarget !== null) {
			return $exactTarget;
		}

		$parts = explode('-', $languageCode);
		if (count($parts) === 2) {
			$language = strtolower((string) ($parts[0] ?? ''));
			$country = strtoupper((string) ($parts[1] ?? ''));

			global $wpdb;
			$table = $wpdb->prefix . 'agdc_market_targets';
			$target = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE language_code = %s AND country_code = %s LIMIT 1",
					$language,
					$country
				),
				ARRAY_A
			);

			if (is_array($target)) {
				return $this->decorateTarget($target);
			}
		}

		return null;
	}

	public function getCopySet(array $target): array
	{
		$language = strtolower((string) ($target['language_code'] ?? 'en'));
		$country = strtoupper((string) ($target['country_code'] ?? 'US'));

		$copies = [
			'tr' => [
				'discount_title' => '%1$s %2$s %3$s Oyun İndirimleri',
				'free_title' => 'Ücretsiz Oyun // %1$s // %2$s %3$s %4$s',
				'discount_intro' => 'Bugün alınmaya değer toplam %d oyun var.',
				'featured_label' => 'Günün Oyunu',
				'featured_reason_label' => 'Neden öne çıktı',
				'featured_discount_phrase' => 'indirim',
				'featured_score_phrase' => 'güçlü puan ortalaması',
				'featured_price_phrase' => 'bugünkü fiyat',
				'free_intro' => 'Ücretsiz oyun %s',
				'price_label' => 'Fiyatı',
				'regular_price_label' => 'Eski Fiyat',
				'discount_label' => 'İndirim Oranı',
				'store_label' => 'Mağaza',
				'cta_label' => 'Mağazada Aç',
				'meta_score_label' => 'Metacritic',
				'user_score_label' => 'Metacritic User',
				'opencritic_score_label' => 'OpenCritic',
				'steam_rating_label' => 'Steam Puanı',
				'free_price_label' => 'ÜCRETSİZ',
				'daily_category_name' => 'Günlük İndirimler',
				'daily_category_slug_base' => 'gunluk-indirimler',
				'free_category_name' => 'Ücretsiz Oyunlar',
				'free_category_slug_base' => 'ucretsiz-oyunlar',
				'daily_tag_names' => ['oyun indirimleri', 'steam indirimleri', 'ucuz oyunlar', 'pc indirimleri', 'uciki'],
				'free_tag_names' => ['ücretsiz oyunlar', 'bedava oyunlar', 'epic games', 'gog fırsatları', 'uciki'],
				'month_names' => [1 => 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'],
			],
			'en' => [
				'discount_title' => '%1$s %2$s %3$s Game Deals',
				'free_title' => 'Free Game // %1$s // %2$s %3$s %4$s',
				'discount_intro' => 'There are %d games worth grabbing today.',
				'featured_label' => 'Featured Pick',
				'featured_reason_label' => 'Why it stands out',
				'featured_discount_phrase' => 'discount',
				'featured_score_phrase' => 'strong review profile',
				'featured_price_phrase' => 'today at',
				'free_intro' => 'Free game: %s',
				'price_label' => 'Price',
				'regular_price_label' => 'Regular Price',
				'discount_label' => 'Discount',
				'store_label' => 'Store',
				'cta_label' => 'Open Store Page',
				'meta_score_label' => 'Metacritic',
				'user_score_label' => 'Metacritic User',
				'opencritic_score_label' => 'OpenCritic',
				'steam_rating_label' => 'Steam Reviews',
				'free_price_label' => 'FREE',
				'daily_category_name' => 'Daily Deals',
				'daily_category_slug_base' => 'daily-deals',
				'free_category_name' => 'Free Games',
				'free_category_slug_base' => 'free-games',
				'daily_tag_names' => ['game deals', 'pc deals', 'discounted games', 'cheap games', 'uciki'],
				'free_tag_names' => ['free games', 'pc freebies', 'epic games', 'gog deals', 'uciki'],
				'month_names' => [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
			],
			'ro' => [
				'discount_title' => '%1$s %2$s %3$s Reduceri la Jocuri',
				'free_title' => 'Joc Gratuit // %1$s // %2$s %3$s %4$s',
				'discount_intro' => 'Astăzi există %d jocuri care merită cumpărate.',
				'featured_label' => 'Alegerea Zilei',
				'featured_reason_label' => 'De ce iese în evidență',
				'featured_discount_phrase' => 'reducere',
				'featured_score_phrase' => 'profil puternic de review-uri',
				'featured_price_phrase' => 'prețul de azi',
				'free_intro' => 'Joc gratuit: %s',
				'price_label' => 'Preț',
				'regular_price_label' => 'Preț normal',
				'discount_label' => 'Reducere',
				'store_label' => 'Magazin',
				'cta_label' => 'Deschide pagina',
				'meta_score_label' => 'Metacritic',
				'user_score_label' => 'Metacritic User',
				'opencritic_score_label' => 'OpenCritic',
				'steam_rating_label' => 'Steam Reviews',
				'free_price_label' => 'GRATUIT',
				'daily_category_name' => 'Reduceri Zilnice',
				'daily_category_slug_base' => 'reduceri-zilnice',
				'free_category_name' => 'Jocuri Gratuite',
				'free_category_slug_base' => 'jocuri-gratuite',
				'daily_tag_names' => ['reduceri jocuri', 'oferte jocuri', 'jocuri ieftine', 'oferte pc', 'uciki'],
				'free_tag_names' => ['jocuri gratuite', 'jocuri gratis', 'epic games', 'oferte gog', 'uciki'],
				'month_names' => [1 => 'Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie', 'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'],
			],
			'es' => [
				'discount_title' => 'Ofertas de Juegos %1$s %2$s %3$s',
				'free_title' => 'Juego Gratis // %1$s // %2$s %3$s %4$s',
				'discount_intro' => 'Hoy hay %d juegos que merecen la pena.',
				'featured_label' => 'Juego Destacado',
				'featured_reason_label' => 'Por qué destaca',
				'featured_discount_phrase' => 'de descuento',
				'featured_score_phrase' => 'perfil sólido de valoraciones',
				'featured_price_phrase' => 'precio de hoy',
				'free_intro' => 'Juego gratis: %s',
				'price_label' => 'Precio',
				'regular_price_label' => 'Precio normal',
				'discount_label' => 'Descuento',
				'store_label' => 'Tienda',
				'cta_label' => 'Abrir oferta',
				'meta_score_label' => 'Metacritic',
				'user_score_label' => 'Metacritic User',
				'opencritic_score_label' => 'OpenCritic',
				'steam_rating_label' => 'Steam Reviews',
				'free_price_label' => 'GRATIS',
				'daily_category_name' => 'Ofertas de Juegos',
				'daily_category_slug_base' => 'ofertas-de-juegos',
				'free_category_name' => 'Juegos Gratis',
				'free_category_slug_base' => 'juegos-gratis',
				'daily_tag_names' => ['ofertas de juegos', 'juegos baratos', 'descuentos pc', 'juegos con descuento', 'uciki'],
				'free_tag_names' => ['juegos gratis', 'juegos gratis pc', 'epic games', 'ofertas gog', 'uciki'],
				'month_names' => [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
			],
			'de' => [
				'discount_title' => 'Spieleangebote %1$s %2$s %3$s',
				'free_title' => 'Gratis-Spiel // %1$s // %2$s %3$s %4$s',
				'discount_intro' => 'Heute gibt es %d Spiele, die sich lohnen.',
				'free_intro' => 'Gratis-Spiel: %s',
				'price_label' => 'Preis',
				'regular_price_label' => 'Normalpreis',
				'discount_label' => 'Rabatt',
				'store_label' => 'Shop',
				'cta_label' => 'Zum Angebot',
				'free_price_label' => 'KOSTENLOS',
				'month_names' => [1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
			],
			'fr' => [
				'discount_title' => 'Promos Jeux %1$s %2$s %3$s',
				'free_title' => 'Jeu Gratuit // %1$s // %2$s %3$s %4$s',
				'discount_intro' => 'Il y a %d jeux intéressants aujourd’hui.',
				'free_intro' => 'Jeu gratuit : %s',
				'price_label' => 'Prix',
				'regular_price_label' => 'Prix normal',
				'discount_label' => 'Réduction',
				'store_label' => 'Boutique',
				'cta_label' => 'Voir l’offre',
				'free_price_label' => 'GRATUIT',
				'month_names' => [1 => 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
			],
		];

		$copy = $copies[$language] ?? $copies['en'];

		if ($language === 'en' && $country === 'US') {
			$copy['discount_title'] = '%2$s %1$s %3$s Game Deals';
			$copy['free_title'] = 'Free Game // %1$s // %3$s %2$s %4$s';
		}

		if ($language === 'es' && $country === 'MX') {
			$copy['discount_title'] = 'Ofertas de Juegos del %1$s de %2$s de %3$s';
			$copy['free_title'] = 'Juego Gratis // %1$s // %2$s de %3$s de %4$s';
		}

		$copy['hreflang'] = strtolower($language . '-' . $country);

		return $copy;
	}

	private function decorateTarget(array $target): array
	{
		$country = strtoupper((string) ($target['country_code'] ?? ''));
		$language = strtolower((string) ($target['language_code'] ?? 'en'));
		$site_section = (string) ($target['site_section'] ?? $target['market_key'] ?? $language);

		$target['country_code'] = $country;
		$target['language_code'] = $language;
		$target['site_section'] = $site_section;
		$target['locale'] = strtolower($language . '-' . $country);
		$target['seo_path_prefix'] = trim($site_section, '/');

		return $target;
	}

	private function getFallbackTarget(string $marketKey): array
	{
		$parts = explode('-', strtolower($marketKey));
		$country = strtoupper($parts[0] ?? 'US');
		$language = strtolower($parts[1] ?? 'en');

		return $this->decorateTarget(
			[
				'id' => 0,
				'market_key' => $marketKey,
				'country_code' => $country,
				'language_code' => $language,
				'default_currency_code' => 'USD',
				'site_section' => $language,
			]
		);
	}

	private function normalizeLanguageCode(string $languageCode): string
	{
		$languageCode = strtolower(trim($languageCode));
		if ($languageCode === '') {
			return '';
		}

		return str_replace('_', '-', $languageCode);
	}
}
