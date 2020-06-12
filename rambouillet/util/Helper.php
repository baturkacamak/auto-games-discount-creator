<?php

namespace Rambouillet\Util;

if (! class_exists('Rambouillet\Util\Helper')) {
    /**
     * Class Helper
     *
     * @package Rambouillet\Util
     */
    class Helper
    {

        /**
         * @param $f
         * @param string $zt
         *
         * @return false|string|string[]
         */
        public static function getTurkishDate($f, $zt = 'now')
        {
            $z        = date("$f", strtotime($zt));
            $donustur = [
                'Monday'    => 'Pazartesi',
                'Tuesday'   => 'Salı',
                'Wednesday' => 'Çarşamba',
                'Thursday'  => 'Perşembe',
                'Friday'    => 'Cuma',
                'Saturday'  => 'Cumartesi',
                'Sunday'    => 'Pazar',
                'January'   => 'Ocak',
                'February'  => 'Şubat',
                'March'     => 'Mart',
                'April'     => 'Nisan',
                'May'       => 'Mayıs',
                'June'      => 'Haziran',
                'July'      => 'Temmuz',
                'August'    => 'Ağustos',
                'September' => 'Eylül',
                'October'   => 'Ekim',
                'November'  => 'Kasım',
                'December'  => 'Aralık',
                'Mon'       => 'Pts',
                'Tue'       => 'Sal',
                'Wed'       => 'Çar',
                'Thu'       => 'Per',
                'Fri'       => 'Cum',
                'Sat'       => 'Cts',
                'Sun'       => 'Paz',
                'Jan'       => 'Oca',
                'Feb'       => 'Şub',
                'Mar'       => 'Mar',
                'Apr'       => 'Nis',
                'Jun'       => 'Haz',
                'Jul'       => 'Tem',
                'Aug'       => 'Ağu',
                'Sep'       => 'Eyl',
                'Oct'       => 'Eki',
                'Nov'       => 'Kas',
                'Dec'       => 'Ara',
            ];
            foreach ($donustur as $en => $tr) {
                $z = str_replace($en, $tr, $z);
            }
            if (strpos($z, 'Mayıs') !== false && strpos($f, 'F') === false) {
                $z = str_replace('Mayıs', 'May', $z);
            }

            return $z;
        }

        public static function getRemoteImage($url)
        {
            $guzzle = new Client(
                [
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36' .
                            ' (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36',
                    ],
                ]
            );

            if ($url) {
                $html = $guzzle->get($url)->getBody()->getContents();
                if ($html) {
                    $xpath = self::getXpath($html);
                    $image = $xpath->query('//meta[@property="og:image"]');
                    if ($image && $image->length) {
                        return $image->item(0)->getAttribute('content') ? $image->item(0)->getAttribute(
                            'content'
                        ) : false;
                    }
                }
            }
            return false;
        }

        /**
         * @param $data
         *
         * @return DOMXPath
         */
        public static function getXpath($data)
        {
            /* Use internal libxml errors -- turn on in production, off for debugging */
            libxml_use_internal_errors(true);

            $data = mb_convert_encoding($data, 'HTML-ENTITIES', 'UTF-8');
            /* Createa a new DomDocument object */
            $dom = new \DOMDocument();
            /* Load the HTML */

            $dom->loadHTML($data);
            $dom->preserveWhiteSpace = false;

            $dom->saveHTML();

            /* Create a new XPath object */
            return new DOMXPath($dom);
        }
    }
}
