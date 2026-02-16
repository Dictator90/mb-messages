# MB Messages

A package for working with translations and messages, implementing logic similar to Laravel's `illuminate/translation`. Supports in-memory storage (arrays) and loading from the filesystem (PHP and JSON). Uses `mb4it/collections` for deep key nesting and ICU plural rules for pluralization.

## Requirements

- PHP 8.2+

## Installation

```bash
composer require mb/messages
```

## Components

### MessagesInterface

Contract for translation providers. Declares methods:

- `get(string $key, array $replace = [], ?string $locale = null): string` — get translation by key
- `choice(string $key, int $number, array $replace = [], ?string $locale = null): string` — get translation with pluralization
- `has(string $key, ?string $locale = null): bool` — check if translation exists

### ArrayMessages

Implementation of `MessagesInterface` for storing translations in memory (arrays). Useful for programmatic loading and tests.

```php
use MB\Messages\ArrayMessages;

$messages = new ArrayMessages([], locale: 'en', fallback: 'ru');

// Adding translations: addMessages(locale, namespace, array)
$messages->addMessages('en', 'validation', [
    'required' => 'The :attribute field is required.',
    'email' => 'The :attribute must be a valid email address.',
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
        'numeric' => 'The :attribute must be at least :min.',
    ],
    'attributes' => [
        'name' => 'name',
        'email' => 'email address',
    ],
]);

// Get translation
$messages->get('validation.required', ['attribute' => 'email']);
// "The email field is required."

// Nested keys
$messages->get('validation.min.string', ['attribute' => 'password', 'min' => 8]);
// "The password must be at least 8 characters."

// Attributes
$messages->get('validation.attributes.email');
// "email address"
```

**ArrayMessages methods:**

| Method | Description |
|--------|-------------|
| `__construct(array $messages, string $locale, ?string $fallback, ?MessageSelector $selector)` | Create instance |
| `addMessages(string $locale, string $namespace, array $messages)` | Add translations for locale and group |
| `mergeIntoLocale(string $locale, array $messages)` | Merge translations into locale root (for JSON) |
| `setMessages(array $messages)` | Replace all translations |
| `setLocale(string $locale)` | Set current locale |
| `getLocale(): string` | Get current locale |
| `setFallback(?string $locale)` | Set fallback locale |
| `getFallback(): ?string` | Get fallback locale |
| `setPlaceholderFormat(string $format)` | Set placeholder format (`{key}` is the marker) |
| `getPlaceholderFormat(): string` | Get current placeholder format |
| `setPlaceholderReplacer(?callable $fn)` | Set callback `(string $text, array $replace): string` |
| `getPlaceholderReplacer(): ?callable` | Get current callback |

### FileMessages

Implementation of `MessagesInterface` for loading translations from files. Internally uses `ArrayMessages` — data from PHP and JSON files is loaded into it, all operations are delegated. Supports:

- PHP group files: `{path}/{locale}/{group}.php` (e.g. `resources/lang/en/validation.php`)
- JSON files: `{path}/{locale}.json` (flat keys)

Groups are loaded lazily on first key access.

**Directory structure:**

```
resources/lang/
├── en/
│   ├── validation.php
│   └── messages.php
├── ru/
│   ├── validation.php
│   └── messages.php
├── en.json
└── ru.json
```

**Example PHP file (validation.php):**

```php
<?php

return [
    'required' => 'The :attribute field is required.',
    'email' => 'The :attribute must be a valid email address.',
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
        'numeric' => 'The :attribute must be at least :min.',
    ],
    'attributes' => [
        'name' => 'name',
        'email' => 'email address',
    ],
];
```

**Example JSON file (en.json):**

```json
{
    "Hello": "Hello",
    "Welcome": "Welcome, :name!",
    "Key with spaces": "Translated text"
}
```

**Using FileMessages:**

```php
use MB\Messages\FileMessages;

$messages = new FileMessages(__DIR__ . '/resources/lang', 'en');
$messages->setFallback('ru');

$messages->get('validation.required', ['attribute' => 'email']);
$messages->get('Hello');  // from en.json
$messages->get('Welcome', ['name' => 'John']);
$messages->has('validation.required');
```

**FileMessages methods:**

| Method | Description |
|--------|-------------|
| `__construct(string $path, string $locale = 'en', ?ArrayMessages $arrayMessages = null)` | Path to lang directory, locale, optionally custom ArrayMessages |
| `setLocale(string $locale)` | Set current locale |
| `getLocale(): string` | Get current locale |
| `setFallback(?string $locale)` | Set fallback locale |
| `getFallback(): ?string` | Get fallback locale |
| `setPlaceholderFormat(string $format)` | Set placeholder format |
| `getPlaceholderFormat(): string` | Get current placeholder format |
| `setPlaceholderReplacer(?callable $fn)` | Set callback for placeholder replacement |
| `getPlaceholderReplacer(): ?callable` | Get current callback |

### PotentiallyMessagesString

Object with deferred translation substitution on string cast (`__toString()`). Used in validation rules when the translation is only available at message output time.

```php
use MB\Messages\ArrayMessages;
use MB\Messages\PotentiallyMessagesString;

$translator = new ArrayMessages([], 'en');
$translator->addMessages('en', 'validation', ['required' => 'The field is required.']);

$pms = new PotentiallyMessagesString('validation.required', $translator);

echo (string) $pms;  // "The field is required."
```

**Behavior:**

- If `translator` is provided and key is found — returns the translation
- If key is not found or string contains a space — returns the original message (key is not treated as valid)
- If `translator === null` — returns the original message

### CreatesPotentiallyMessagesStrings

Trait for creating `PotentiallyMessagesString` in validation rules. Extracts translator from `$this->validator->getTranslator()` if it implements `MessagesInterface`.

```php
use MB\Messages\Traits\CreatesPotentiallyMessagesStrings;

class CustomRule
{
    use CreatesPotentiallyMessagesStrings;

    public function __invoke($attribute, $value, $fail)
    {
        $fail($this->pendingPotentiallyMessagesString($attribute, 'validation.custom_rule'));
    }
}
```

**Method:**

- `pendingPotentiallyMessagesString(string $attribute, ?string $message = null): PotentiallyMessagesString`

## Keys and formats

### Key structure

- **Group.item**: `validation.required`, `validation.min.string`
- **Deep nesting**: `ddd.aaaa.eeee.qqqq` (via `data_get` from mb4it/collections)
- **JSON keys**: flat strings, e.g. `Hello`, `Welcome`

For PHP groups, the first key segment is the group name (filename without `.php`). Remaining segments are the path in the nested array.

### Placeholder replacement

By default, placeholders use the `:name` format (Laravel-style). You can set a custom format via `setPlaceholderFormat()` — the marker `{key}` in the format is replaced with the placeholder name:

```php
// Default :attribute
$messages->get('validation.required', ['attribute' => 'email']);  // "The email field is required."

// Custom format #attribute#
$messages->setPlaceholderFormat('#{key}#');
$messages->get('msg', ['attribute' => 'email']);  // for "The #attribute# field..."

// Format {attribute}
$messages->setPlaceholderFormat('{{key}}');
$messages->get('msg', ['attribute' => 'email']);  // for "The {attribute} field..."
```

**Format examples:**

| Format | Marker in string | Example |
|--------|------------------|---------|
| `:{key}` | `:attribute` | Laravel (default) |
| `#{key}#` | `#attribute#` | |
| `{{key}}` | `{attribute}` | |

**setPlaceholderReplacer(callable|null)** — full control via callback. Signature: `(string $text, array $replace): string`. When set, it is used instead of `placeholderFormat`. When `null`, format-based logic is restored.

```php
// Regex: /:(\w+)/ -> value
$messages->setPlaceholderReplacer(function (string $text, array $replace): string {
    return preg_replace_callback('/:(\w+)/', fn ($m) => $replace[$m[1]] ?? $m[0], $text);
});

// sprintf for positional arguments
$messages->setPlaceholderReplacer(fn (string $text, array $replace) => vsprintf($text, array_values($replace)));
```

### Pluralization (choice)

The `choice()` method supports two formats:

**1. Array** — keys `one`, `other`, `1`, `0`, `many`:

```php
'plural_example' => [
    'one' => 'One item',
    'other' => ':count items',
],
$messages->choice('validation.plural_example', 1);   // "One item"
$messages->choice('validation.plural_example', 5);   // "5 items"
```

**2. Pipe string** — Laravel-style, with inline conditions and ICU plural index:

```php
'pipe_plural' => '{0}No items|{1}One item|[2,*]:count items',
$messages->choice('validation.pipe_plural', 0);  // "No items"
$messages->choice('validation.pipe_plural', 1);  // "One item"
$messages->choice('validation.pipe_plural', 5);  // "5 items"

'simple_pipe' => 'One item|:count items',  // index by plural rules
$messages->choice('validation.simple_pipe', 1);  // "One item"
$messages->choice('validation.simple_pipe', 3);  // "3 items"
```

Placeholder `:count` is automatically set from `$number`.

### MessageSelector

The `MessageSelector` class handles string selection for pluralization. Supports pipe format with conditions `{0}`, `{1}`, `[2,*]`, `[*,5]` and ICU plural rules per locale. Can be injected into `ArrayMessages` and `FileMessages` constructors.

## Fallback locale

When a translation is missing in the current locale, the fallback is used (if set):

```php
$messages = new ArrayMessages([], locale: 'ru', fallback: 'en');
$messages->addMessages('en', 'validation', ['required' => 'Required.']);
$messages->addMessages('ru', 'validation', ['email' => 'Invalid email.']);

$messages->get('validation.required');  // "Required." (from en)
$messages->get('validation.email');     // "Invalid email." (from ru)
```

## Testing

```bash
composer test
```

or

```bash
vendor/bin/phpunit tests
```

Tests cover:

- ArrayMessages: get, choice (array + pipe), has, fallback, nested keys, flat structure, mergeIntoLocale, deep nesting, setPlaceholderReplacer (callback, regex, sprintf, null)
- FileMessages: PHP groups, JSON, lazy load, fallback, invalid JSON, custom ArrayMessages, setPlaceholderReplacer
- PotentiallyMessagesString: with/without translator, strings with spaces
- CreatesPotentiallyMessagesStrings: with validator, without validator, non-MessagesInterface
