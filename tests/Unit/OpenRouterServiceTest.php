<?php

namespace Tests\Unit;

use App\Services\OpenRouterService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenRouterServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.openrouter.api_key' => 'test-key',
            'services.openrouter.base_url' => 'https://openrouter.test/api/v1',
            'services.openrouter.base_urls' => null,
            'services.openrouter.timeout' => 30,
            'services.openrouter.max_attempts' => 1,
        ]);
    }

    public function test_single_image_response_is_returned_unchanged(): void
    {
        Http::fake([
            'https://openrouter.test/api/v1/images' => Http::response([
                'data' => [['b64_json' => base64_encode('image')]],
                'usage' => ['cost' => 0.01],
            ]),
        ]);

        $result = app(OpenRouterService::class)->generateImageFromPrompt(
            'google/gemini-3.1-flash-image',
            'A studio portrait'
        );

        $this->assertCount(1, $result['data']);
        Http::assertSentCount(1);
    }

    public function test_model_limited_to_one_image_is_retried_as_separate_requests(): void
    {
        Http::fakeSequence('https://openrouter.test/api/v1/images')
            ->push(['error' => ['message' => 'n must be between 1 and 1']], 400)
            ->push(['data' => [['b64_json' => base64_encode('one')]], 'usage' => ['cost' => 0.01]])
            ->push(['data' => [['b64_json' => base64_encode('two')]], 'usage' => ['cost' => 0.02]])
            ->push(['data' => [['b64_json' => base64_encode('three')]], 'usage' => ['cost' => 0.03]]);

        $result = app(OpenRouterService::class)->generateImageFromPrompt(
            'google/gemini-3.1-flash-image',
            'A cinematic portrait',
            '1K',
            '1:1',
            3
        );

        $this->assertCount(3, $result['data']);
        $this->assertSame(0.06, $result['usage']['cost']);
        Http::assertSentCount(4);
        Http::assertSent(function (Request $request) {
            return $request['n'] === 1;
        });
    }

    public function test_gemini_payload_drops_unsupported_legacy_parameters(): void
    {
        Http::fake([
            'https://openrouter.test/api/v1/images' => Http::response([
                'data' => [['b64_json' => base64_encode('image')]],
            ]),
        ]);

        app(OpenRouterService::class)->generateImageFromPrompt(
            'google/gemini-3.1-flash-image',
            'Portrait',
            '2K',
            '3:4',
            1,
            ['negative_prompt' => 'blurry', 'strength' => 0.8, 'input_fidelity' => 'high']
        );

        Http::assertSent(function (Request $request) {
            return $request['resolution'] === '2K'
                && $request['aspect_ratio'] === '3:4'
                && str_contains($request['prompt'], 'Avoid these unwanted traits: blurry')
                && !array_key_exists('negative_prompt', $request->data())
                && !array_key_exists('strength', $request->data())
                && !array_key_exists('input_fidelity', $request->data());
        });
    }

    public function test_gpt_image_payload_converts_resolution_and_portrait_ratio(): void
    {
        Http::fake([
            'https://openrouter.test/api/v1/images' => Http::response([
                'data' => [['b64_json' => base64_encode('image')]],
            ]),
        ]);

        app(OpenRouterService::class)->generateImageFromPrompt(
            'openai/gpt-image-1',
            'Portrait',
            '2K',
            '4:5'
        );

        Http::assertSent(function (Request $request) {
            return $request['quality'] === 'high'
                && $request['aspect_ratio'] === '2:3'
                && !array_key_exists('resolution', $request->data());
        });
    }
}
