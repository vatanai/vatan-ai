<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use Vatan\Seo\Ai\Ai;
use Vatan\Seo\Ai\ModelRouter;
use Vatan\Seo\Ai\OpenRouterClient;
use Vatan\Seo\Models\Site;

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

    public function test_json_repair_tries_a_second_distinct_candidate_when_first_repair_is_invalid(): void
    {
        $ai = new class(\Mockery::mock(OpenRouterClient::class), \Mockery::mock(ModelRouter::class)) extends Ai {
            public array $responses = [];
            public array $excluded = [];

            protected function call(Site $site, string $role, string $purpose, string $system, string $user, array $opt, array $excludeModels = []): array
            {
                $this->excluded[] = $excludeModels;
                return array_shift($this->responses);
            }
        };
        $ai->responses = [
            ['content' => 'not json', 'model' => 'model-one'],
            ['content' => '{still broken', 'model' => 'model-two'],
            ['content' => '{"valid":true}', 'model' => 'model-three'],
        ];

        $result = $ai->json(new Site(['id' => 1]), 'fast', 'unit-fixture', 'Return JSON.', 'Test response.', ['max_tokens' => 100]);

        self::assertSame(['valid' => true], $result);
        self::assertSame([[], ['model-one'], ['model-one', 'model-two']], $ai->excluded);
    }
}
