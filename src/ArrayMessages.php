<?php

namespace MB\Messages;

use MB\Messages\Contracts\MessagesInterface;

class ArrayMessages implements MessagesInterface
{
    /**
     * Translations: [locale => [key => value]] or [locale => [namespace => [key => value]]].
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $messages = [];

    protected string $locale;

    protected ?string $fallback = null;

    protected MessageSelector $selector;

    /** Placeholder format: {key} is replaced with the placeholder name. Default ':{key}' -> :attribute */
    protected string $placeholderFormat = ':{key}';

    /**
     * Optional callback (string $text, array $replace): string — full control over placeholder replacement.
     *
     * @var callable|null
     */
    protected $placeholderReplacer = null;

    /**
     * @param  array<string, array<string, mixed>>  $messages
     */
    public function __construct(array $messages = [], string $locale = 'en', ?string $fallback = null, ?MessageSelector $selector = null)
    {
        $this->messages = $messages;
        $this->locale = $locale;
        $this->fallback = $fallback;
        $this->selector = $selector ?? new MessageSelector;
    }

    public function setFallback(?string $locale): static
    {
        $this->fallback = $locale;

        return $this;
    }

    public function getFallback(): ?string
    {
        return $this->fallback;
    }

    public function setPlaceholderFormat(string $format): static
    {
        $this->placeholderFormat = $format;

        return $this;
    }

    public function getPlaceholderFormat(): string
    {
        return $this->placeholderFormat;
    }

    public function setPlaceholderReplacer(?callable $fn): static
    {
        $this->placeholderReplacer = $fn;

        return $this;
    }

    public function getPlaceholderReplacer(): ?callable
    {
        return $this->placeholderReplacer;
    }

    /**
     * @param  array<string, array<string, mixed>>  $messages
     */
    public function setMessages(array $messages): static
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * Add messages for a locale (e.g. 'en') and optional namespace (e.g. 'validation').
     * Keys use dot notation: 'validation.required', 'validation.attributes.email'.
     *
     * @param  array<string, mixed>  $messages
     */
    public function addMessages(string $locale, string $namespace, array $messages): static
    {
        if (! isset($this->messages[$locale])) {
            $this->messages[$locale] = [];
        }
        if (! isset($this->messages[$locale][$namespace])) {
            $this->messages[$locale][$namespace] = [];
        }
        $this->messages[$locale][$namespace] = array_replace(
            $this->messages[$locale][$namespace],
            $messages
        );

        return $this;
    }

    /**
     * Merge messages into the locale root (e.g. for JSON translations).
     *
     * @param  array<string, mixed>  $messages
     */
    public function mergeIntoLocale(string $locale, array $messages): static
    {
        if (! isset($this->messages[$locale])) {
            $this->messages[$locale] = [];
        }
        $this->messages[$locale] = array_replace_recursive($this->messages[$locale], $messages);

        return $this;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->locale;
        $value = $this->getFromMessages($locale, $key);

        if ($value === null && $this->fallback !== null && $this->fallback !== $locale) {
            $value = $this->getFromMessages($this->fallback, $key);
        }

        if ($value === null) {
            return $key;
        }

        if (is_array($value)) {
            return $key;
        }

        return $this->applyReplacements($value, $replace);
    }

    public function choice(string $key, int $number, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->locale;
        $line = $this->getFromMessages($locale, $key);

        if ($line === null && $this->fallback !== null && $this->fallback !== $locale) {
            $line = $this->getFromMessages($this->fallback, $key);
        }

        if ($line === null) {
            return $key;
        }

        $replace['count'] = $number;

        $line = $this->selector->choose($line, $number, $locale);

        if (! is_string($line)) {
            return $key;
        }

        return $this->applyReplacements($line, $replace);
    }

    protected function applyReplacements(string $text, array $replace): string
    {
        if ($this->placeholderReplacer !== null) {
            return ($this->placeholderReplacer)($text, $replace);
        }
        foreach ($replace as $k => $v) {
            $placeholder = str_replace('{key}', $k, $this->placeholderFormat);
            $text = str_replace($placeholder, (string) $v, $text);
        }

        return $text;
    }

    protected function getFromMessages(string $locale, string $key): mixed
    {
        $messages = $this->messages[$locale] ?? [];
        $value = data_get($messages, $key);
        if ($value === null && is_array($messages) && array_key_exists($key, $messages)) {
            return $messages[$key];
        }
        return $value;
    }

    public function has(string $key, ?string $locale = null): bool
    {
        $locale = $locale ?? $this->locale;
        if ($this->getFromMessages($locale, $key) !== null) {
            return true;
        }
        if ($this->fallback !== null && $this->fallback !== $locale) {
            return $this->getFromMessages($this->fallback, $key) !== null;
        }
        return false;
    }
}
