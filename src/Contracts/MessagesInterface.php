<?php

namespace MB\Messages\Contracts;

interface MessagesInterface
{
    /**
     * Get the translation for the given key.
     * If the translation is not found, the key is returned.
     *
     * @param  string  $key
     * @param  array  $replace
     * @param  string|null  $locale
     * @return string
     */
    public function get(string $key, array $replace = [], ?string $locale = null): string;

    /**
     * Get the translation for a given key with pluralization.
     *
     * @param  string  $key
     * @param  int  $number
     * @param  array  $replace
     * @param  string|null  $locale
     * @return string
     */
    public function choice(string $key, int $number, array $replace = [], ?string $locale = null): string;

    /**
     * Determine if a translation exists for the given key.
     *
     * @param  string  $key
     * @param  string|null  $locale
     * @return bool
     */
    public function has(string $key, ?string $locale = null): bool;
}
