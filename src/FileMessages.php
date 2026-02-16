<?php

namespace MB\Messages;

use MB\Messages\Contracts\MessagesInterface;
use RuntimeException;

class FileMessages implements MessagesInterface
{
    protected string $path;

    protected ArrayMessages $arrayMessages;

    /**
     * Loaded flags: [locale => [group => true]].
     *
     * @var array<string, array<string, bool>>
     */
    protected array $loaded = [];

    public function __construct(string $path, string $locale = 'en', ?ArrayMessages $arrayMessages = null)
    {
        $this->path = rtrim($path, '/\\');
        $this->arrayMessages = $arrayMessages ?? new ArrayMessages([], $locale);
        $this->arrayMessages->setLocale($locale);
    }

    public function setLocale(string $locale): static
    {
        $this->arrayMessages->setLocale($locale);

        return $this;
    }

    public function getLocale(): string
    {
        return $this->arrayMessages->getLocale();
    }

    public function setFallback(?string $locale): static
    {
        $this->arrayMessages->setFallback($locale);

        return $this;
    }

    public function getFallback(): ?string
    {
        return $this->arrayMessages->getFallback();
    }

    public function setPlaceholderFormat(string $format): static
    {
        $this->arrayMessages->setPlaceholderFormat($format);

        return $this;
    }

    public function getPlaceholderFormat(): string
    {
        return $this->arrayMessages->getPlaceholderFormat();
    }

    public function setPlaceholderReplacer(?callable $fn): static
    {
        $this->arrayMessages->setPlaceholderReplacer($fn);

        return $this;
    }

    public function getPlaceholderReplacer(): ?callable
    {
        return $this->arrayMessages->getPlaceholderReplacer();
    }

    public function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->arrayMessages->getLocale();
        $this->ensureLoaded($locale, $key);
        $this->ensureLoadedForFallback($locale, $key);

        return $this->arrayMessages->get($key, $replace, $locale);
    }

    public function choice(string $key, int $number, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->arrayMessages->getLocale();
        $this->ensureLoaded($locale, $key);
        $this->ensureLoadedForFallback($locale, $key);

        return $this->arrayMessages->choice($key, $number, $replace, $locale);
    }

    public function has(string $key, ?string $locale = null): bool
    {
        $locale = $locale ?? $this->arrayMessages->getLocale();
        $this->ensureLoaded($locale, $key);
        $this->ensureLoadedForFallback($locale, $key);

        return $this->arrayMessages->has($key, $locale);
    }

    protected function ensureLoaded(string $locale, string $key): void
    {
        $this->loadJson($locale);

        $segments = explode('.', $key, 2);
        $group = $segments[0];
        $item = $segments[1] ?? null;

        if ($item !== null) {
            $this->loadGroup($locale, $group);
        }
    }

    protected function ensureLoadedForFallback(string $locale, string $key): void
    {
        $fallback = $this->arrayMessages->getFallback();
        if ($fallback !== null && $fallback !== $locale) {
            $this->ensureLoaded($fallback, $key);
        }
    }

    protected function loadJson(string $locale): void
    {
        if (isset($this->loaded[$locale]['*'])) {
            return;
        }

        $file = $this->path.DIRECTORY_SEPARATOR.$locale.'.json';
        if (! is_file($file)) {
            $this->loaded[$locale]['*'] = true;

            return;
        }

        $decoded = json_decode(file_get_contents($file), true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Translation file [{$file}] contains an invalid JSON structure.");
        }

        $messages = is_array($decoded) ? $decoded : [];
        $this->arrayMessages->mergeIntoLocale($locale, $messages);
        $this->loaded[$locale]['*'] = true;
    }

    protected function loadGroup(string $locale, string $group): void
    {
        if (isset($this->loaded[$locale][$group])) {
            return;
        }

        $file = $this->path.DIRECTORY_SEPARATOR.$locale.DIRECTORY_SEPARATOR.$group.'.php';
        if (! is_file($file)) {
            $this->loaded[$locale][$group] = true;

            return;
        }

        $messages = require $file;
        $this->arrayMessages->addMessages($locale, $group, is_array($messages) ? $messages : []);
        $this->loaded[$locale][$group] = true;
    }
}
