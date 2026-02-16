<?php

namespace MB\Messages\Tests;

use MB\Messages\ArrayMessages;
use MB\Messages\PotentiallyMessagesString;
use MB\Messages\Traits\CreatesPotentiallyMessagesStrings;
use PHPUnit\Framework\TestCase;
use stdClass;

class CreatesPotentiallyMessagesStringsTest extends TestCase
{
    use CreatesPotentiallyMessagesStrings;

    protected ?object $validator = null;

    public function testPendingPotentiallyMessagesStringWithoutValidator(): void
    {
        $this->validator = null;

        $result = $this->pendingPotentiallyMessagesString('email', 'validation.required');

        $this->assertInstanceOf(PotentiallyMessagesString::class, $result);
        $this->assertSame('validation.required', (string) $result);
    }

    public function testPendingPotentiallyMessagesStringWithValidatorWithoutGetTranslator(): void
    {
        $this->validator = new stdClass();

        $result = $this->pendingPotentiallyMessagesString('email', 'validation.required');

        $this->assertInstanceOf(PotentiallyMessagesString::class, $result);
        $this->assertSame('validation.required', (string) $result);
    }

    public function testPendingPotentiallyMessagesStringWithValidatorWithTranslator(): void
    {
        $translator = new ArrayMessages([], 'en');
        $translator->addMessages('en', 'validation', ['required' => 'The field is required.']);

        $validator = $this->createMockValidator($translator);
        $this->validator = $validator;

        $result = $this->pendingPotentiallyMessagesString('email', 'validation.required');

        $this->assertInstanceOf(PotentiallyMessagesString::class, $result);
        $this->assertSame('The field is required.', (string) $result);
    }

    public function testPendingPotentiallyMessagesStringWithNonMessagesInterfaceTranslator(): void
    {
        $fakeTranslator = new stdClass();
        $validator = $this->createMockValidator($fakeTranslator);
        $this->validator = $validator;

        $result = $this->pendingPotentiallyMessagesString('email', 'validation.required');

        $this->assertSame('validation.required', (string) $result);
    }

    public function testPendingPotentiallyMessagesStringUsesAttributeWhenMessageNull(): void
    {
        unset($this->validator);

        $result = $this->pendingPotentiallyMessagesString('email', null);

        $this->assertSame('email', (string) $result);
    }

    private function createMockValidator(object $translator): object
    {
        return new class ($translator) {
            public function __construct(
                private object $translator,
            ) {}

            public function getTranslator(): object
            {
                return $this->translator;
            }
        };
    }
}
