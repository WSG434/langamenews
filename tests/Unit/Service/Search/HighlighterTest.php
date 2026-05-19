<?php

namespace App\Tests\Unit\Service\Search;

use App\Service\Search\Highlighter;
use PHPUnit\Framework\TestCase;

class HighlighterTest extends TestCase
{
    private Highlighter $highlighter;

    protected function setUp(): void
    {
        $this->highlighter = new Highlighter();
    }

    public function testSingleWordWrappedInMark(): void
    {
        $result = $this->highlighter->highlight('Hello world', 'world');
        $this->assertStringContainsString('<mark>world</mark>', $result);
    }

    public function testCaseInsensitive(): void
    {
        $result = $this->highlighter->highlight('Москва — столица', 'москва');
        $this->assertStringContainsString('<mark>', $result);
    }

    public function testXssEscaped(): void
    {
        $result = $this->highlighter->highlight('<script>alert(1)</script>', 'alert');
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    public function testEmptyQueryReturnsEscapedText(): void
    {
        $result = $this->highlighter->highlight('Hello world', '');
        $this->assertSame('Hello world', $result);
        $this->assertStringNotContainsString('<mark>', $result);
    }

    public function testMultipleWordsHighlighted(): void
    {
        $result = $this->highlighter->highlight('PHP and Symfony framework', 'PHP Symfony');
        $this->assertStringContainsString('<mark>PHP</mark>', $result);
        $this->assertStringContainsString('<mark>Symfony</mark>', $result);
    }
}
