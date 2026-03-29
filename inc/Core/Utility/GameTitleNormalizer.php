<?php

namespace AutoGamesDiscountCreator\Core\Utility;

class GameTitleNormalizer
{
	public function normalize(string $title): string
	{
		$title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5);

		$replacements = [
			"\xC2\x99" => '',
			"\x99" => '',
			'™' => '',
			'®' => '',
			'©' => '',
			"\u{00A0}" => ' ',
		];

		$title = strtr($title, $replacements);
		$title = preg_replace('/\s+/u', ' ', $title) ?? $title;

		return trim($title);
	}
}
