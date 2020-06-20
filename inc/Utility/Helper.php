<?php

namespace Rambouillet\Utility;

use DOMXPath;
use GuzzleHttp\Client;

if (!class_exists('Rambouillet\Util\Helper')) {
    /**
     * Class Helper
     *
     * @package Rambouillet\Utility
     */
    class Helper
    {

        /**
         * @param string $strTime
         * @param $dateFormat
         *
         * @return false|string|string[]
         */
        public static function getTurkishDateName($strTime = 'now', $dateFormat = 'F')
        {
            setlocale(LC_TIME, 'tr_TR.UTF-8');

            switch ($dateFormat) :
                case 'F':
                    $intDateFormat = '%B';
                    break;
                case 'D':
                    $intDateFormat = '%a';
                    break;
                case 'l':
                    $intDateFormat = '%A';
                    break;
                case 'M':
                    $intDateFormat = '%b';
                    break;
                default:
                    $intDateFormat = false;
                    break;
            endswitch;

            $date_name = strftime($intDateFormat, strtotime($strTime));

            $date_names = [
                'Monday' => 'Pazartesi',
                'Tuesday' => 'Salı',
                'Wednesday' => 'Çarşamba',
                'Thursday' => 'Perşembe',
                'Friday' => 'Cuma',
                'Saturday' => 'Cumartesi',
                'Sunday' => 'Pazar',
                'January' => 'Ocak',
                'February' => 'Şubat',
                'March' => 'Mart',
                'April' => 'Nisan',
                'May' => 'Mayıs',
                'June' => 'Haziran',
                'July' => 'Temmuz',
                'August' => 'Ağustos',
                'September' => 'Eylül',
                'October' => 'Ekim',
                'November' => 'Kasım',
                'December' => 'Aralık',
                'Mon' => 'Pts',
                'Tue' => 'Sal',
                'Wed' => 'Çar',
                'Thu' => 'Per',
                'Fri' => 'Cum',
                'Sat' => 'Cts',
                'Sun' => 'Paz',
                'Jan' => 'Oca',
                'Feb' => 'Şub',
                'Mar' => 'Mar',
                'Apr' => 'Nis',
                'Jun' => 'Haz',
                'Jul' => 'Tem',
                'Aug' => 'Ağu',
                'Sep' => 'Eyl',
                'Oct' => 'Eki',
                'Nov' => 'Kas',
                'Dec' => 'Ara',
            ];

            if (array_search($date_name, $date_names)) {
                return $date_name;
            } else {
                $date_name = date($dateFormat, strtotime($strTime));

                foreach ($date_names as $english_day_name => $turkish_day_name) {
                    $date_name = str_replace($english_day_name, $turkish_day_name, $date_name);
                }
                if (false !== strpos($date_name, 'Mayıs') && false === strpos($dateFormat, 'F')) {
                    $date_name = str_replace('Mayıs', 'May', $date_name);
                }
            }


            return $date_name;
        }

        public static function getRemoteImage($url, $guzzle = false)
        {
            if (!$guzzle) {
                $guzzle = new Client(
                    [
                        'headers' => [
                            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36' .
                                ' (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36',
                        ],
                    ]
                );
            }

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

            $converted_data = mb_convert_encoding($data, 'HTML-ENTITIES', 'UTF-8');
            /* Createa a new DomDocument object */
            $dom_document = new \DOMDocument();
            /* Load the HTML */

            $dom_document->loadHTML($converted_data);
            $dom_document->preserveWhiteSpace = false;

            $dom_document->saveHTML();

            /* Create a new XPath object */
            return new DOMXPath($dom_document);
        }
    }
}
