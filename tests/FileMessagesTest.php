<?php

namespace MB\Messages\Tests;

use MB\Messages\FileMessages;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FileMessagesTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturesPath = dirname(__DIR__).DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'lang';
    }

    public function testGetFromPhpGroup(): void
    {
        $messages = new FileMessages($this->fixturesPath, 'en');

        $this->assertSame('The :attribute field is required.', $messages->get('validation.required'));
        $this->assertSame('The email field is required.', $messages->get('validation.required', ['attribute' => 'email']));
    }

    public function testGetFromJson(): void
    {
        $messages = new FileMessages($this->fixturesPath, 'en');

        $this->assertSame('Hello', $messages->get('Hello'));
        $this->assertSame('Welcome, John!', $messages->get('Welcome', ['name' => 'John']));
    }

    public function testGetWithNestedPhpKey(): void
    {
        $messages = new FileMessages($this->fixturesPath, 'en');

        $this->assertSame('The :attribute must be at least :min characters.', $messages->get('validation.min.string'));
        $this->assertSame(
            'The password must be at least 8 characters.',
            $messages->get('validation.min.string', ['attribute' => 'password', 'min' => 8])
        );
    }

    public function testGetAttributes(): void
    {
        $messages = new FileMessages($this->fixturesPath, 'en');

        $this->assertSame('name', $messages->get('validation.attributes.name'));
        $this->assertSame('email address', $messages->get('validation.attributes.email'));
    }

    public function testGetReturnsKeyWhenNotFound(): void
    {
        $messages = new FileMessages($this->fixturesPath, 'en');

        $this->assertSame('validation.nonexistent', $messages->get('validation.nonexistent'));
    }

    public function testGetWithExplicitLocale(): void
    {
        $messages = new FileMessages($this->fixturesPath, 'en');

        $this->assertSame('Hello', $messages->get('Hello', [], 'en'));
        $this->assertSame('Привет', $messages->get('Hello', [], 'ru'));
    }

    public function testGetUsesFallback(): void
    {
        $messages = new FileMessages($this->fixturesPath, 'de');
        $messages->setFallback('en');

        $this->assertSame('The :attribute field is required.', $messages->get('validation.required'));
    }

    public function testChoiceFromPhp(): void
    {
        $messages = new FileMessages($this->fixturesPath, 'en');

        $this->assertSame('One item', $messages->choice('validation.plural_example', 1));
        $this->assertSame('5 items', $messages->choice('validation.plural_example', 5));
    }

    public function testChoiceFromJsonNotSupported(): void
    {
        $messages = new FileMessages($this->fixturesPath, 'en');

        $this->assertSame('Hello', $messages->choice('Hello', 1));
    }

    public function testChoiceReturnsKeyWhenNotFound(): void
    {
        $messages = new FileMessages($this->fixturesPath, 'en');

        $this->assertSame('validation.missing', $messages->choice('validation.missing', 1));
    }

    public function testChoiceUsesFallback(): void
    {
        $messages = new FileMessages($this->fixturesPath, 'de');
        $messages->setFallback('en');

        $this->assertSame('One item', $messages->choice('validation.plural_example', 1));
    }

    public function testHasReturnsTrueForPhpKey(): void
    {
        $messages = new FileMessages($this->fixturesPath, 'en');

        $this->assertTrue($messages->has('validation.required'));
    }

    public function testHasReturnsTrueForJsonKey(): void
    {
        $messages = new FileMessages($this->fixturesPath, 'en');

        $this->assertTrue($messages->has('Hello'));
    }

    public function testHasReturnsFalseWhenKeyMissing(): void
    {
        $messages = new FileMessages($this->fixturesPath, 'en');

        $this->assertFalse($messages->has('nonexistent.key'));
    }

    public function testHasChecksFallback(): void
    {
        $messages = new FileMessages($this->fixturesPath, 'de');
        $messages->setFallback('en');

        $this->assertTrue($messages->has('validation.required'));
    }

    public function testSetLocaleAndGetLocale(): void
    {
        $messages = new FileMessages($this->fixturesPath, 'en');

        $this->assertSame('en', $messages->getLocale());
        $messages->setLocale('ru');
        $this->assertSame('ru', $messages->getLocale());
    }

    public function testSetFallbackAndGetFallback(): void
    {
        $messages = new FileMessages($this->fixturesPath, 'en');

        $this->assertNull($messages->getFallback());
        $messages->setFallback('ru');
        $this->assertSame('ru', $messages->getFallback());
    }

    public function testLazyLoadsGroups(): void
    {
        $messages = new FileMessages($this->fixturesPath, 'en');

        $this->assertSame('The :attribute field is required.', $messages->get('validation.required'));
        $this->assertSame('Hello', $messages->get('Hello'));
    }

    public function testInvalidJsonThrowsException(): void
    {
        $invalidPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'mb-messages-invalid-json-'.uniqid();
        mkdir($invalidPath, 0755, true);
        file_put_contents($invalidPath.DIRECTORY_SEPARATOR.'en.json', '{ invalid json }');

        try {
            $messages = new FileMessages($invalidPath, 'en');
            $messages->get('any');
            $this->fail('Expected RuntimeException was not thrown');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('invalid JSON', $e->getMessage());
        } finally {
            unlink($invalidPath.DIRECTORY_SEPARATOR.'en.json');
            rmdir($invalidPath);
        }
    }

    public function testEmptyPathNormalizesTrailingSlash(): void
    {
        $messages = new FileMessages($this->fixturesPath.DIRECTORY_SEPARATOR, 'en');

        $this->assertSame('Hello', $messages->get('Hello'));
    }

    public function testFileMessagesAcceptsCustomArrayMessages(): void
    {
        $arrayMessages = new \MB\Messages\ArrayMessages([], 'en');
        $fileMessages = new \MB\Messages\FileMessages($this->fixturesPath, 'en', $arrayMessages);

        $fileMessages->get('validation.required');
        $this->assertTrue($arrayMessages->has('validation.required'));
    }

    public function testFileMessagesPlaceholderFormat(): void
    {
        $messages = new \MB\Messages\FileMessages($this->fixturesPath, 'en');
        $messages->setPlaceholderFormat('#{key}#');

        $this->assertSame('#{key}#', $messages->getPlaceholderFormat());
    }

    public function testFileMessagesPlaceholderReplacer(): void
    {
        $arrayMessages = new \MB\Messages\ArrayMessages([], 'en');
        $arrayMessages->mergeIntoLocale('en', ['CustomMsg' => 'Hello, :name!']);
        $fileMessages = new \MB\Messages\FileMessages($this->fixturesPath, 'en', $arrayMessages);

        $fileMessages->setPlaceholderReplacer(function (string $text, array $replace): string {
            return preg_replace_callback('/:(\w+)/', fn ($m) => $replace[$m[1]] ?? $m[0], $text);
        });

        $this->assertSame('Hello, John!', $fileMessages->get('CustomMsg', ['name' => 'John']));
    }
}
