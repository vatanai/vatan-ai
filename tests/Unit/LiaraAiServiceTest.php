<?php

namespace Tests\Unit;

use App\Services\LiaraAiService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LiaraAiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.liara.api_key' => 'test-key',
            'services.liara.base_url' => 'https://liara.test/v1',
            'services.liara.timeout' => 30,
        ]);
    }

    public function test_generation_uses_supported_liara_payload(): void
    {
        Http::fake([
            'https://liara.test/v1/images/generations' => Http::response([
                'data' => [['b64_json' => base64_encode('image')]],
            ]),
        ]);

        $result = app(LiaraAiService::class)->generateImageFromPrompt(
            'openai/gpt-image-1-mini',
            'A studio portrait'
        );

        $this->assertArrayHasKey('data', $result);
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://liara.test/v1/images/generations'
                && $request['model'] === 'openai/gpt-image-1-mini'
                && !array_key_exists('response_format', $request->data())
                && !array_key_exists('input', $request->data());
        });
    }

    public function test_reference_image_uses_multipart_edits_endpoint(): void
    {
        Http::fake([
            'https://liara.test/v1/images/edits' => Http::response([
                'data' => [['b64_json' => base64_encode('edited-image')]],
            ]),
        ]);

        $result = app(LiaraAiService::class)->generateImageFromPrompt(
            'openai/gpt-image-1-mini',
            'Preserve the subject identity',
            '1K',
            '1:1',
            1,
            [
                'input_references' => [[
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => 'data:image/jpeg;base64,' . base64_encode('reference-image'),
                    ],
                ]],
            ]
        );

        $this->assertArrayHasKey('data', $result);
        Http::assertSent(function (Request $request) {
            $body = $request->body();

            return $request->url() === 'https://liara.test/v1/images/edits'
                && str_contains($body, 'name="image"')
                && str_contains($body, 'reference-image')
                && !str_contains($body, 'response_format')
                && !str_contains($body, 'name="input"');
        });
    }
}
