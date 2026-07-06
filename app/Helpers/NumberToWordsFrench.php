<?php

namespace App\Helpers;

class NumberToWordsFrench
{
    public static function convert($number): string
    {
        $number = (int) $number;

        if ($number == 0) {
            return 'zéro';
        }

        $ones = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
                 'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
        $tens = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante', 'quatre-vingt', 'quatre-vingt'];

        $result = '';

        if ($number >= 1000000) {
            $millions = (int) ($number / 1000000);
            if ($millions == 1) {
                $result .= 'un million ';
            } else {
                $result .= self::convertHundreds($millions, $ones, $tens) . ' millions ';
            }
            $number = $number % 1000000;
        }

        if ($number >= 1000) {
            $thousands = (int) ($number / 1000);
            if ($thousands == 1) {
                $result .= 'mille ';
            } else {
                $result .= self::convertHundreds($thousands, $ones, $tens) . ' mille ';
            }
            $number = $number % 1000;
        }

        if ($number > 0) {
            $result .= self::convertHundreds($number, $ones, $tens);
        }

        return trim($result);
    }

    protected static function convertHundreds($number, array $ones, array $tens): string
    {
        $result = '';

        if ($number >= 100) {
            $hundred = (int) ($number / 100);
            if ($hundred == 1) {
                $result .= 'cent';
            } else {
                $result .= $ones[$hundred] . ' cent';
            }
            $number = $number % 100;
            if ($number > 0) {
                $result .= ' ';
            }
        }

        if ($number >= 20) {
            $ten = (int) ($number / 10);
            $one = $number % 10;

            if ($ten == 7) {
                $result .= 'soixante';
                if ($one > 0) {
                    $result .= '-' . $ones[10 + $one];
                } else {
                    $result .= '-dix';
                }
            } elseif ($ten == 8) {
                $result .= 'quatre-vingt';
                if ($one > 0) {
                    $result .= '-' . $ones[$one];
                } else {
                    $result .= 's';
                }
            } elseif ($ten == 9) {
                $result .= 'quatre-vingt';
                if ($one > 0) {
                    $result .= '-' . $ones[10 + $one];
                } else {
                    $result .= '-dix';
                }
            } else {
                $result .= $tens[$ten];
                if ($one > 0) {
                    if ($ten == 8 && $one == 1) {
                        $result .= '-et-un';
                    } else {
                        $result .= '-' . $ones[$one];
                    }
                }
            }
        } elseif ($number > 0) {
            $result .= $ones[$number];
        }

        return trim($result);
    }
}
