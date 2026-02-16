<?php

namespace MB\Messages\Tests;

use MB\Messages\ArrayMessages;
use MB\Messages\PotentiallyMessagesString;
use PHPUnit\Framework\TestCase;

class PotentiallyMessagesStringTest extends TestCase
{
    public function testToStringWithTranslatorResolvesKey(): void
    {
        $translator = new ArrayMessages([], 'en');
        $translator->addMessages('en', 'validation', ['required' => 'The field is required.']);

        $pms = new PotentiallyMessagesString('validation.required', $translator);

        $this->assertSame('The field is required.', (string) $pms);
    }

    public function testToStringWithTranslatorReturnsKeyWhenNotTranslated(): void
    {
        $translator = new ArrayMessages([], 'en');
        $pms = new PotentiallyMessagesString('validation.missing', $translator);

        $this->assertSame('validation.missing', (string) $pms);
    }

    public function testToStringWithoutTranslatorReturnsMessage(): void
    {
        $pms = new PotentiallyMessagesString('validation.required', null);

        $this->assertSame('validation.required', (string) $pms);
    }

    public function testToStringWithStringContainingSpaceSkipsTranslation(): void
    {
        $translator = new ArrayMessages([], 'en');
        $translator->addMessages('en', 'm', ['key with spaces' => 'Translated']);

        $pms = new PotentiallyMessagesString('key with spaces', $translator);

        $this->assertSame('key with spaces', (string) $pms);
    }

    public function testToStringWithSimpleKeyResolves(): void
    {
        $translator = new ArrayMessages([], 'en');
        $translator->addMessages('en', 'm', ['simple' => 'Simple translation']);

        $pms = new PotentiallyMessagesString('m.simple', $translator);

        $this->assertSame('Simple translation', (string) $pms);
    }

    public function testImplementsStringable(): void
    {
        $pms = new PotentiallyMessagesString('hello', null);

        $this->assertInstanceOf(\Stringable::class, $pms);
        $this->assertSame('hello', $pms->__toString());
    }
}
