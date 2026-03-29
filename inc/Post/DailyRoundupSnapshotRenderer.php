<?php

namespace AutoGamesDiscountCreator\Post;

class DailyRoundupSnapshotRenderer
{
	public function render(array $snapshot, array $copySet): string
	{
		$games = array_values(
			array_filter(
				(array) ($snapshot['games'] ?? []),
				static fn($game): bool => is_array($game) && !empty($game['name']) && !empty($game['url'])
			)
		);

		if ($games === []) {
			return '';
		}

		$intro = sprintf($copySet['discount_intro'] ?? 'There are %d games worth grabbing today.', count($games));
		$priceLabel = $copySet['price_label'] ?? 'Price';
		$regularPriceLabel = $copySet['regular_price_label'] ?? 'Regular Price';
		$discountLabel = $copySet['discount_label'] ?? 'Discount';
		$storeLabel = $copySet['store_label'] ?? 'Store';
		$ctaLabel = $copySet['cta_label'] ?? 'Open Store Page';
		$featuredLabel = $copySet['featured_label'] ?? 'Featured Pick';
		$featuredReasonLabel = $copySet['featured_reason_label'] ?? 'Why it stands out';
		$featuredDiscountPhrase = $copySet['featured_discount_phrase'] ?? 'discount';
		$featuredScorePhrase = $copySet['featured_score_phrase'] ?? 'strong review profile';
		$featuredPricePhrase = $copySet['featured_price_phrase'] ?? 'today at';
		$metaScoreLabel = $copySet['meta_score_label'] ?? 'Metacritic';
		$userScoreLabel = $copySet['user_score_label'] ?? 'User Score';
		$opencriticLabel = $copySet['opencritic_score_label'] ?? 'OpenCritic';
		$steamRatingLabel = $copySet['steam_rating_label'] ?? 'Steam Rating';
		$featuredGame = $this->selectFeaturedGame($games);

		$html = '<section class="agdc-roundup">';
		$html .= '<div class="steam-content-body agdc-roundup__intro"><p>' . esc_html($intro) . '</p></div>';
		if (is_array($featuredGame)) {
			$html .= $this->renderFeaturedGame(
				$featuredGame,
				$featuredLabel,
				$featuredReasonLabel,
				$priceLabel,
				$regularPriceLabel,
				$discountLabel,
				$storeLabel,
				$ctaLabel,
				$featuredDiscountPhrase,
				$featuredScorePhrase,
				$featuredPricePhrase,
				$metaScoreLabel,
				$userScoreLabel,
				$opencriticLabel,
				$steamRatingLabel
			);
		}
		$html .= '<div class="steam-cards agdc-roundup__cards"><div class="ui cards">';

		foreach ($games as $game) {
			$isFeatured = is_array($featuredGame)
				&& (int) ($featuredGame['offer_id'] ?? 0) === (int) ($game['offer_id'] ?? 0)
				&& (string) ($featuredGame['name'] ?? '') === (string) ($game['name'] ?? '');

			if ($isFeatured) {
				continue;
			}

			$html .= '<article class="ui card agdc-roundup-card">';

			if (!empty($game['resolved_image_url'])) {
				$html .= '<div class="image agdc-roundup-card__image"><a href="' . esc_url((string) $game['url']) . '" target="_blank" rel="noopener">';
				$html .= '<img src="' . esc_url((string) $game['resolved_image_url']) . '" alt="' . esc_attr((string) $game['name']) . '">';
				$html .= '</a></div>';
			}

			$html .= '<div class="content agdc-roundup-card__content">';
			$html .= '<a class="header agdc-roundup-card__title" href="' . esc_url((string) $game['url']) . '" target="_blank" rel="noopener">' . esc_html((string) $game['name']) . '</a>';
			$html .= '<div class="description agdc-roundup-card__meta">';
			$html .= '<div>' . esc_html($priceLabel) . ': <strong>' . esc_html($this->formatPrice($game['price'] ?? 0, (string) ($game['currency_code'] ?? 'USD'))) . '</strong></div>';
			if (!empty($game['regular_price'])) {
				$html .= '<div>' . esc_html($regularPriceLabel) . ': <strong>' . esc_html($this->formatPrice($game['regular_price'], (string) ($game['currency_code'] ?? 'USD'))) . '</strong></div>';
			}
			$html .= '<div>' . esc_html($discountLabel) . ': <strong>' . esc_html((string) ($game['cut'] ?? 0)) . '</strong>%</div>';
			$html .= '<div>' . esc_html($storeLabel) . ': <strong>' . esc_html($this->formatStoreKey((string) ($game['store_key'] ?? ''))) . '</strong></div>';
			foreach ($this->buildScoreRows($game, $metaScoreLabel, $userScoreLabel, $opencriticLabel, $steamRatingLabel) as $row) {
				$html .= '<div>' . esc_html($row['label']) . ': <strong>' . esc_html($row['value']) . '</strong></div>';
			}
			$html .= '</div></div>';
			$html .= '<div class="extra content agdc-roundup-card__footer"><a href="' . esc_url((string) $game['url']) . '" target="_blank" rel="noopener">' . esc_html($ctaLabel) . '</a></div>';
			$html .= '</article>';
		}

		$html .= '</div></div>';
		$html .= '</section>';

		return $html;
	}

	private function formatPrice($amount, string $currencyCode): string
	{
		$value = number_format((float) $amount, 2, '.', ',');
		return trim($value . ' ' . strtoupper($currencyCode));
	}

	private function formatStoreKey(string $storeKey): string
	{
		$storeKey = trim($storeKey);
		if ($storeKey === '') {
			return '';
		}

		return ucwords(str_replace(['_', '-'], ' ', $storeKey));
	}

	private function buildScoreRows(array $game, string $metaScoreLabel, string $userScoreLabel, string $opencriticLabel, string $steamRatingLabel): array
	{
		$rows = [];
		$scoreMap = [
			['key' => 'meta_score', 'count_key' => 'meta_review_count', 'label' => $metaScoreLabel],
			['key' => 'user_score', 'count_key' => 'user_review_count', 'label' => $userScoreLabel],
			['key' => 'opencritic_score', 'count_key' => 'opencritic_review_count', 'label' => $opencriticLabel],
			['key' => 'steam_rating', 'count_key' => 'steam_review_count', 'label' => $steamRatingLabel],
		];

		foreach ($scoreMap as $score) {
			$value = $game[$score['key']] ?? null;
			if ($value === null || $value === '' || !is_numeric($value)) {
				continue;
			}

			$rows[] = [
				'label' => $score['label'],
				'value' => $this->formatScoreWithCount(
					(float) $value,
					isset($game[$score['count_key']]) && is_numeric($game[$score['count_key']])
						? (int) $game[$score['count_key']]
						: null
				),
			];
		}

		return $rows;
	}

	private function formatScore(float $score): string
	{
		if (abs($score - round($score)) < 0.001) {
			return (string) (int) round($score);
		}

		return number_format($score, 1, '.', '');
	}

	private function formatScoreWithCount(float $score, ?int $count): string
	{
		$value = $this->formatScore($score);
		if ($count === null || $count <= 0) {
			return $value;
		}

		return $value . ' (' . $this->formatCompactCount($count) . ')';
	}

	private function formatCompactCount(int $count): string
	{
		if ($count >= 1000000) {
			return rtrim(rtrim(number_format($count / 1000000, 1, '.', ''), '0'), '.') . 'm';
		}

		if ($count >= 1000) {
			return rtrim(rtrim(number_format($count / 1000, 1, '.', ''), '0'), '.') . 'k';
		}

		return (string) $count;
	}

	private function selectFeaturedGame(array $games): ?array
	{
		if ($games === []) {
			return null;
		}

		$best = null;
		$bestScore = null;
		$fallbackBest = null;
		$fallbackBestScore = null;

		foreach ($games as $game) {
			$score = $this->calculateFeaturedScore($game);

			if ($fallbackBest === null || $score > $fallbackBestScore) {
				$fallbackBest = $game;
				$fallbackBestScore = $score;
			}

			if (!$this->passesFeaturedQualityGate($game)) {
				continue;
			}

			if ($best === null || $score > $bestScore) {
				$best = $game;
				$bestScore = $score;
			}
		}

		return $best ?? $fallbackBest;
	}

	private function calculateFeaturedScore(array $game): float
	{
		$discount = (float) ($game['cut'] ?? 0);
		$price = (float) ($game['price'] ?? 0);
		$meta = (float) ($game['meta_score'] ?? 0);
		$opencritic = (float) ($game['opencritic_score'] ?? 0);
		$steam = (float) ($game['steam_rating'] ?? 0);
		$user = (float) ($game['user_score'] ?? 0);

		$reviewSignal = ($meta * 0.40) + ($opencritic * 0.30) + ($steam * 0.20) + ($user * 0.10);
		$discountSignal = $discount * 0.85;
		$priceSignal = $price > 0 ? max(0.0, 18 - min(18, $price / 18)) : 18.0;

		return $reviewSignal + $discountSignal + $priceSignal;
	}

	private function passesFeaturedQualityGate(array $game): bool
	{
		$meta = (float) ($game['meta_score'] ?? 0);
		$opencritic = (float) ($game['opencritic_score'] ?? 0);
		$steam = (float) ($game['steam_rating'] ?? 0);
		$user = (float) ($game['user_score'] ?? 0);

		return $meta >= 80
			|| $opencritic >= 80
			|| $steam >= 85
			|| $user >= 85;
	}

	private function renderFeaturedGame(
		array $game,
		string $featuredLabel,
		string $featuredReasonLabel,
		string $priceLabel,
		string $regularPriceLabel,
		string $discountLabel,
		string $storeLabel,
		string $ctaLabel,
		string $featuredDiscountPhrase,
		string $featuredScorePhrase,
		string $featuredPricePhrase,
		string $metaScoreLabel,
		string $userScoreLabel,
		string $opencriticLabel,
		string $steamRatingLabel
	): string {
		$html = '<article class="agdc-featured">';
		$html .= '<div class="agdc-featured__media">';
		if (!empty($game['resolved_image_url'])) {
			$html .= '<a href="' . esc_url((string) $game['url']) . '" target="_blank" rel="noopener">';
			$html .= '<img src="' . esc_url((string) $game['resolved_image_url']) . '" alt="' . esc_attr((string) $game['name']) . '">';
			$html .= '</a>';
		}
		$html .= '</div>';
		$html .= '<div class="agdc-featured__body">';
		$html .= '<div class="agdc-featured__eyebrow">' . esc_html($featuredLabel) . '</div>';
		$html .= '<a class="agdc-featured__title" href="' . esc_url((string) $game['url']) . '" target="_blank" rel="noopener">' . esc_html((string) ($game['name'] ?? '')) . '</a>';
		$html .= '<div class="agdc-featured__reason"><strong>' . esc_html($featuredReasonLabel) . ':</strong> ';
		$html .= esc_html($this->buildFeaturedReason($game, $featuredDiscountPhrase, $featuredScorePhrase, $featuredPricePhrase)) . '</div>';
		$html .= '<div class="agdc-featured__stats">';
		$html .= '<div>' . esc_html($priceLabel) . ': <strong>' . esc_html($this->formatPrice($game['price'] ?? 0, (string) ($game['currency_code'] ?? 'USD'))) . '</strong></div>';
		if (!empty($game['regular_price'])) {
			$html .= '<div>' . esc_html($regularPriceLabel) . ': <strong>' . esc_html($this->formatPrice($game['regular_price'], (string) ($game['currency_code'] ?? 'USD'))) . '</strong></div>';
		}
		$html .= '<div>' . esc_html($discountLabel) . ': <strong>' . esc_html((string) ($game['cut'] ?? 0)) . '</strong>%</div>';
		$html .= '<div>' . esc_html($storeLabel) . ': <strong>' . esc_html($this->formatStoreKey((string) ($game['store_key'] ?? ''))) . '</strong></div>';
		foreach ($this->buildScoreRows($game, $metaScoreLabel, $userScoreLabel, $opencriticLabel, $steamRatingLabel) as $row) {
			$html .= '<div>' . esc_html($row['label']) . ': <strong>' . esc_html($row['value']) . '</strong></div>';
		}
		$html .= '</div>';
		$html .= '<div class="agdc-featured__footer"><a href="' . esc_url((string) $game['url']) . '" target="_blank" rel="noopener">' . esc_html($ctaLabel) . '</a></div>';
		$html .= '</div>';
		$html .= '</article>';

		return $html;
	}

	private function buildFeaturedReason(
		array $game,
		string $featuredDiscountPhrase,
		string $featuredScorePhrase,
		string $featuredPricePhrase
	): string
	{
		$parts = [];

		if (!empty($game['cut'])) {
			$parts[] = '%' . $this->formatScore((float) $game['cut']) . ' ' . trim($featuredDiscountPhrase);
		}

		$bestReview = max(
			(float) ($game['opencritic_score'] ?? 0),
			(float) ($game['meta_score'] ?? 0),
			(float) ($game['steam_rating'] ?? 0),
			(float) ($game['user_score'] ?? 0)
		);
		if ($bestReview > 0) {
			$parts[] = trim($featuredScorePhrase) . ' (' . $this->formatScore($bestReview) . ')';
		}

		if (!empty($game['price'])) {
			$parts[] = trim($featuredPricePhrase) . ' ' . $this->formatPrice((float) $game['price'], (string) ($game['currency_code'] ?? 'USD'));
		}

		return implode(', ', $parts);
	}
}
