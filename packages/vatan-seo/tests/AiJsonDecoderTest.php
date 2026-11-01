<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use Vatan\Seo\Ai\Ai;
use Vatan\Seo\Ai\ModelRouter;
use Vatan\Seo\Ai\OpenRouterClient;

class AiJsonDecoderTest extends TestCase
{
    private function decoder(): Ai
    {
        return new Ai(\Mockery::mock(OpenRouterClient::class), \Mockery::mock(ModelRouter::class));
    }

    public function test_extracts_json_after_prose_even_when_json_strings_contain_braces(): void
    {
        $decoded = $this->decoder()->decode('نتیجه: {"note":"متن دارای } و { داخل رشته","valid":true} پایان پاسخ');

        self::assertSame(['note' => 'متن دارای } و { داخل رشته', 'valid' => true], $decoded);
    }

    public function test_repairs_trailing_comma_and_json_fence(): void
    {
        $decoded = $this->decoder()->decode("```json\n{\"products\":[{\"id\":1,}],}\n```");

        self::assertSame(['products' => [['id' => 1]]], $decoded);
    }
}
