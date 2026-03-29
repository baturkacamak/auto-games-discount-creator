<?php

namespace AutoGamesDiscountCreator\Core\Settings;

class MarketTargetRepository
{
	public function getDefaultTarget(): array
	{
		$settings = (new SettingsRepository())->getAll();
		$market_key = (string) ($settings['data_model']['default_market_target_key'] ?? 'tr-tr');

		return $this->findByKey($market_key) ?? $this->getFallbackTarget($market_key);
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
				'month_names' => [1 => 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'],
			],
			'en' => [
				'discount_title' => '%1$s %2$s %3$s Game Deals',
				'free_title' => 'Free Game // %1$s // %2$s %3$s %4$s',
				'discount_intro' => 'There are %d games worth grabbing today.',
				'featured_label' => 'Featured Pick',
				'featured_reason_label' => 'Why it stands out',
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
				'month_names' => [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
			],
			'ro' => [
				'discount_title' => '%1$s %2$s %3$s Reduceri la Jocuri',
				'free_title' => 'Joc Gratuit // %1$s // %2$s %3$s %4$s',
				'discount_intro' => 'Astăzi există %d jocuri care merită cumpărate.',
				'free_intro' => 'Joc gratuit: %s',
				'price_label' => 'Preț',
				'regular_price_label' => 'Preț normal',
				'discount_label' => 'Reducere',
				'store_label' => 'Magazin',
				'cta_label' => 'Deschide pagina',
				'free_price_label' => 'GRATUIT',
				'month_names' => [1 => 'Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie', 'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'],
			],
			'es' => [
				'discount_title' => 'Ofertas de Juegos %1$s %2$s %3$s',
				'free_title' => 'Juego Gratis // %1$s // %2$s %3$s %4$s',
				'discount_intro' => 'Hoy hay %d juegos que merecen la pena.',
				'free_intro' => 'Juego gratis: %s',
				'price_label' => 'Precio',
				'regular_price_label' => 'Precio normal',
				'discount_label' => 'Descuento',
				'store_label' => 'Tienda',
				'cta_label' => 'Abrir oferta',
				'free_price_label' => 'GRATIS',
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
}
