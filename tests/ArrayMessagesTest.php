<?php

namespace MB\Messages\Tests;

use MB\Messages\ArrayMessages;
use PHPUnit\Framework\TestCase;

class ArrayMessagesTest extends TestCase
{
    public function testGetReturnsTranslationForKey(): void
    {
        $messages = new ArrayMessages([], 'en');
        $messages->addMessages('en', 'validation', [
            'required' => 'The :attribute field is required.',
        ]);

        $this->assertSame('The email field is required.', $messages->get('validation.required', ['attribute' => 'email']));
    }

    public function testGetReturnsKeyWhenTranslationNotFound(): void
    {
        $messages = new ArrayMessages([], 'en');

        $this->assertSame('validation.missing', $messages->get('validation.missing'));
    }

    public function testGetWithNestedKeys(): void
    {
        $messages = new ArrayMessages([], 'en');
        $messages->addMessages('en', 'validation', [
            'min' => [
                'string' => 'The :attribute must be at least :min characters.',
            ],
        ]);

        $this->assertSame(
            'The password must be at least 8 characters.',
            $messages->get('validation.min.string', ['attribute' => 'password', 'min' => 8])
        );
    }

    public function testGetWithExplicitLocale(): void
    {
        $messages = new ArrayMessages([], 'en');
        $messages->addMessages('en', 'messages', ['hello' => 'Hello']);
        $messages->addMessages('ru', 'messages', ['hello' => 'Привет']);

        $this->assertSame('Hello', $messages->get('messages.hello', [], 'en'));
        $this->assertSame('Привет', $messages->get('messages.hello', [], 'ru'));
    }

    public function testGetUsesFallbackWhenTranslationMissing(): void
    {
        $messages = new ArrayMessages([], 'ru', 'en');
        $messages->addMessages('en', 'validation', ['required' => 'The :attribute field is required.']);
        $messages->addMessages('ru', 'validation', ['email' => 'Некорректный email.']);

        $this->assertSame('The name field is required.', $messages->get('validation.required', ['attribute' => 'name']));
        $this->assertSame('Некорректный email.', $messages->get('validation.email'));
    }

    public function testGetDoesNotUseFallbackWhenLocaleSameAsFallback(): void
    {
        $messages = new ArrayMessages([], 'en', 'en');
        $messages->addMessages('en', 'validation', ['required' => 'Required']);

        $this->assertSame('validation.missing', $messages->get('validation.missing'));
    }

    public function testChoiceWithPluralization(): void
    {
        $messages = new ArrayMessages([], 'en');
        $messages->addMessages('en', 'validation', [
            'plural_example' => [
                'one' => 'One item',
                'other' => ':count items',
            ],
        ]);

        $this->assertSame('One item', $messages->choice('validation.plural_example', 1));
        $this->assertSame('5 items', $messages->choice('validation.plural_example', 5, []));
    }

    public function testChoiceReturnsKeyWhenNotFound(): void
    {
        $messages = new ArrayMessages([], 'en');

        $this->assertSame('validation.missing', $messages->choice('validation.missing', 1));
    }

    public function testChoiceUsesFallback(): void
    {
        $messages = new ArrayMessages([], 'ru', 'en');
        $messages->addMessages('en', 'validation', [
            'plural_example' => ['one' => 'One', 'other' => ':count items'],
        ]);

        $this->assertSame('One', $messages->choice('validation.plural_example', 1));
    }

    public function testChoiceWithPipeFormat(): void
    {
        $messages = new ArrayMessages([], 'en');
        $messages->addMessages('en', 'validation', [
            'pipe_plural' => '{0}No items|{1}One item|[2,*]:count items',
        ]);

        $this->assertSame('No items', $messages->choice('validation.pipe_plural', 0));
        $this->assertSame('One item', $messages->choice('validation.pipe_plural', 1));
        $this->assertSame('5 items', $messages->choice('validation.pipe_plural', 5));
    }

    public function testChoiceWithPipeFormatAndPluralIndex(): void
    {
        $messages = new ArrayMessages([], 'en');
        $messages->addMessages('en', 'validation', [
            'simple_pipe' => 'One item|:count items',
        ]);

        $this->assertSame('One item', $messages->choice('validation.simple_pipe', 1));
        $this->assertSame('3 items', $messages->choice('validation.simple_pipe', 3));
    }

    public function testDeepNestedKey(): void
    {
        $messages = new ArrayMessages([], 'en');
        $messages->addMessages('en', 'ddd', [
            'aaaa' => [
                'eeee' => [
                    'qqqq' => 'Deep value',
                ],
            ],
        ]);

        $this->assertSame('Deep value', $messages->get('ddd.aaaa.eeee.qqqq'));
    }

    public function testMergeIntoLocale(): void
    {
        $messages = new ArrayMessages([], 'en');
        $messages->mergeIntoLocale('en', ['Hello' => 'Hi', 'Welcome' => 'Welcome!']);

        $this->assertSame('Hi', $messages->get('Hello'));
        $this->assertSame('Welcome!', $messages->get('Welcome'));
    }

    public function testHasReturnsTrueWhenKeyExists(): void
    {
        $messages = new ArrayMessages([], 'en');
        $messages->addMessages('en', 'validation', ['required' => 'Required']);

        $this->assertTrue($messages->has('validation.required'));
    }

    public function testHasReturnsFalseWhenKeyMissing(): void
    {
        $messages = new ArrayMessages([], 'en');

        $this->assertFalse($messages->has('validation.missing'));
    }

    public function testHasChecksFallback(): void
    {
        $messages = new ArrayMessages([], 'ru', 'en');
        $messages->addMessages('en', 'validation', ['required' => 'Required']);

        $this->assertTrue($messages->has('validation.required'));
    }

    public function testHasWithExplicitLocale(): void
    {
        $messages = new ArrayMessages([], 'en');
        $messages->addMessages('en', 'v', ['k' => 'v1']);
        $messages->addMessages('ru', 'v', ['k' => 'v2']);

        $this->assertTrue($messages->has('v.k', 'en'));
        $this->assertTrue($messages->has('v.k', 'ru'));
        $this->assertFalse($messages->has('v.k', 'de'));
    }

    public function testSetLocaleAndGetLocale(): void
    {
        $messages = new ArrayMessages([], 'en');

        $this->assertSame('en', $messages->getLocale());
        $messages->setLocale('ru');
        $this->assertSame('ru', $messages->getLocale());
    }

    public function testSetFallbackAndGetFallback(): void
    {
        $messages = new ArrayMessages([], 'en', 'ru');

        $this->assertSame('ru', $messages->getFallback());
        $messages->setFallback('de');
        $this->assertSame('de', $messages->getFallback());
        $messages->setFallback(null);
        $this->assertNull($messages->getFallback());
    }

    public function testSetMessagesReplacesAllMessages(): void
    {
        $messages = new ArrayMessages([], 'en');
        $messages->addMessages('en', 'v', ['k' => 'old']);
        $messages->setMessages([
            'en' => [
                'v' => ['k' => 'new'],
            ],
        ]);

        $this->assertSame('new', $messages->get('v.k'));
    }

    public function testFlatKeyStructure(): void
    {
        $messages = new ArrayMessages([
            'en' => [
                'validation.required' => 'Flat required message',
            ],
        ], 'en');

        $this->assertSame('Flat required message', $messages->get('validation.required'));
    }

    public function testPlaceholderFormatHash(): void
    {
        $messages = new ArrayMessages([], 'en');
        $messages->setPlaceholderFormat('#{key}#');
        $messages->addMessages('en', 'v', ['msg' => 'The #attribute# field is required.']);

        $this->assertSame('The email field is required.', $messages->get('v.msg', ['attribute' => 'email']));
    }

    public function testPlaceholderFormatBraces(): void
    {
        $messages = new ArrayMessages([], 'en');
        $messages->setPlaceholderFormat('{{key}}');
        $messages->addMessages('en', 'v', ['msg' => 'The {attribute} field is required.']);

        $this->assertSame('The email field is required.', $messages->get('v.msg', ['attribute' => 'email']));
    }

    public function testPlaceholderFormatGetter(): void
    {
        $messages = new ArrayMessages([], 'en');

        $this->assertSame(':{key}', $messages->getPlaceholderFormat());
        $messages->setPlaceholderFormat('#{key}#');
        $this->assertSame('#{key}#', $messages->getPlaceholderFormat());
    }

    public function testPlaceholderReplacerCallbackWithRegex(): void
    {
        $messages = new ArrayMessages([], 'en');
        $messages->setPlaceholderReplacer(function (string $text, array $replace): string {
            return preg_replace_callback('/:(\w+)/', fn ($m) => $replace[$m[1]] ?? $m[0], $text);
        });
        $messages->addMessages('en', 'v', ['msg' => 'The :attribute field must be at least :min characters.']);

        $this->assertSame(
            'The email field must be at least 8 characters.',
            $messages->get('v.msg', ['attribute' => 'email', 'min' => 8])
        );
    }

    public function testPlaceholderReplacerCallbackWithSprintf(): void
    {
        $messages = new ArrayMessages([], 'en');
        $messages->setPlaceholderReplacer(fn (string $text, array $replace): string => vsprintf($text, array_values($replace)));
        $messages->addMessages('en', 'v', ['msg' => 'The %s field must be at least %d characters.']);

        $this->assertSame(
            'The email field must be at least 8 characters.',
            $messages->get('v.msg', ['attribute' => 'email', 'min' => 8])
        );
    }

    public function testPlaceholderReplacerNullRestoresFormat(): void
    {
        $messages = new ArrayMessages([], 'en');
        $messages->setPlaceholderFormat('#{key}#');
        $messages->addMessages('en', 'v', ['msg' => 'The #attribute# field.']);

        $messages->setPlaceholderReplacer(function (string $text, array $replace): string {
            foreach ($replace as $k => $v) {
                $text = str_replace('#'.$k.'#', (string) $v, $text);
            }
            return $text;
        });
        $this->assertSame('The email field.', $messages->get('v.msg', ['attribute' => 'email']));

        $messages->setPlaceholderReplacer(null);
        $this->assertSame('The email field.', $messages->get('v.msg', ['attribute' => 'email']));
    }

    public function testPlaceholderReplacerGetter(): void
    {
        $messages = new ArrayMessages([], 'en');

        $this->assertNull($messages->getPlaceholderReplacer());

        $replacer = fn (string $t, array $r) => $t;
        $messages->setPlaceholderReplacer($replacer);
        $this->assertSame($replacer, $messages->getPlaceholderReplacer());
    }
}
