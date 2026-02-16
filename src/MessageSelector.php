<?php

namespace MB\Messages;

class MessageSelector
{
    /**
     * Select a proper translation string based on the given number.
     * Supports pipe format "{0}No items|{1}One item|[2,*]Many items" and array format ['one' => '...', 'other' => '...'].
     */
    public function choose(string|array $line, int $number, string $locale): string
    {
        if (is_array($line)) {
            return $this->selectFromArray($line, $number);
        }

        $segments = explode('|', $line);

        if (($value = $this->extract($segments, $number)) !== null) {
            return trim($value);
        }

        $segments = $this->stripConditions($segments);
        $pluralIndex = $this->getPluralIndex($locale, $number);

        if (count($segments) === 1 || ! isset($segments[$pluralIndex])) {
            return $segments[0];
        }

        return $segments[$pluralIndex];
    }

    /**
     * @param  array<string, string>  $choices
     */
    private function selectFromArray(array $choices, int $number): string
    {
        if (isset($choices[$number])) {
            return $choices[$number];
        }
        $singular = $choices['one'] ?? $choices[1] ?? null;
        $plural = $choices['other'] ?? $choices[0] ?? $choices['many'] ?? null;
        if ($number === 1 && $singular !== null) {
            return $singular;
        }
        return $plural ?? $singular ?? (string) reset($choices);
    }

    /**
     * @param  array<int, string>  $segments
     */
    private function extract(array $segments, int $number): ?string
    {
        foreach ($segments as $part) {
            $line = $this->extractFromString($part, $number);
            if ($line !== null) {
                return $line;
            }
        }
        return null;
    }

    private function extractFromString(string $part, int $number): ?string
    {
        if (preg_match('/^[\{\[]([^\[\]\{\}]*)[\}\]](.*)/s', $part, $matches) !== 1 || count($matches) !== 3) {
            return null;
        }

        $condition = $matches[1];
        $value = $matches[2];

        if (str_contains($condition, ',')) {
            [$from, $to] = explode(',', $condition, 2);
            $from = trim($from);
            $to = trim($to);

            if ($to === '*' && $number >= (int) $from) {
                return trim($value);
            }
            if ($from === '*' && $number <= (int) $to) {
                return trim($value);
            }
            if ($number >= (int) $from && $number <= (int) $to) {
                return trim($value);
            }
        }

        return ((string) (int) $condition) === (string) $number ? trim($value) : null;
    }

    /**
     * @param  array<int, string>  $segments
     * @return array<int, string>
     */
    private function stripConditions(array $segments): array
    {
        return array_map(
            fn (string $part) => preg_replace('/^[\{\[]([^\[\]\{\}]*)[\}\]]/', '', $part),
            $segments
        );
    }

    /**
     * Get the index to use for pluralization based on locale-specific plural rules.
     * Derived from Zend Framework plural rules (BSD license).
     */
    public function getPluralIndex(string $locale, int $number): int
    {
        $locale = str_replace('-', '_', $locale);
        $baseLocale = explode('_', $locale)[0];

        return match ($baseLocale) {
            'az', 'bo', 'dz', 'id', 'ja', 'jv', 'ka', 'km', 'kn', 'ko', 'ms', 'th', 'tr', 'vi', 'zh' => 0,
            'af', 'bn', 'bg', 'ca', 'da', 'de', 'el', 'en', 'eo', 'es', 'et', 'eu', 'fa', 'fi', 'fo', 'fur', 'fy',
            'gl', 'gu', 'ha', 'he', 'hu', 'is', 'it', 'ku', 'lb', 'ml', 'mn', 'mr', 'nah', 'nb', 'ne', 'nl', 'nn',
            'no', 'om', 'or', 'pa', 'pap', 'ps', 'pt', 'so', 'sq', 'sv', 'sw', 'ta', 'te', 'tk', 'ur', 'zu' => ($number == 1) ? 0 : 1,
            'am', 'bh', 'fil', 'fr', 'gun', 'hi', 'hy', 'ln', 'mg', 'nso', 'ti', 'wa', 'xbr' => (($number == 0) || ($number == 1)) ? 0 : 1,
            'be', 'bs', 'hr', 'ru', 'sr', 'uk' => (($number % 10 == 1) && ($number % 100 != 11)) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2),
            'cs', 'sk' => ($number == 1) ? 0 : ((($number >= 2) && ($number <= 4)) ? 1 : 2),
            'ga' => ($number == 1) ? 0 : (($number == 2) ? 1 : 2),
            'lt' => (($number % 10 == 1) && ($number % 100 != 11)) ? 0 : ((($number % 10 >= 2) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2),
            'sl' => ($number % 100 == 1) ? 0 : (($number % 100 == 2) ? 1 : ((($number % 100 == 3) || ($number % 100 == 4)) ? 2 : 3)),
            'mk' => ($number % 10 == 1) ? 0 : 1,
            'mt' => ($number == 1) ? 0 : ((($number == 0) || (($number % 100 > 1) && ($number % 100 < 11))) ? 1 : ((($number % 100 > 10) && ($number % 100 < 20)) ? 2 : 3)),
            'lv' => ($number == 0) ? 0 : ((($number % 10 == 1) && ($number % 100 != 11)) ? 1 : 2),
            'pl' => ($number == 1) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 12) || ($number % 100 > 14))) ? 1 : 2),
            'cy' => ($number == 1) ? 0 : (($number == 2) ? 1 : ((($number == 8) || ($number == 11)) ? 2 : 3)),
            'ro' => ($number == 1) ? 0 : ((($number == 0) || (($number % 100 > 0) && ($number % 100 < 20))) ? 1 : 2),
            'ar' => ($number == 0) ? 0 : (($number == 1) ? 1 : (($number == 2) ? 2 : ((($number % 100 >= 3) && ($number % 100 <= 10)) ? 3 : ((($number % 100 >= 11) && ($number % 100 <= 99)) ? 4 : 5)))),
            default => 0,
        };
    }
}
