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

    public function test_gemini_25_payload_drops_resolution_but_keeps_reference_image(): void
    {
        Http::fake([
            'https://openrouter.test/api/v1/images' => Http::response([
                'data' => [['b64_json' => base64_encode('image')]],
                'usage' => ['cost' => 0.0021],
            ]),
        ]);

        $reference = 'data:image/jpeg;base64,' . base64_encode('reference');

        app(OpenRouterService::class)->generateImageFromPrompt(
            'google/gemini-2.5-flash-image',
            'Create a product portrait',
            '2K',
            '4:5',
            1,
            ['input_references' => [[
                'type' => 'image_url',
                'image_url' => ['url' => $reference],
            ]]]
        );

        Http::assertSent(function (Request $request) use ($reference) {
            return !array_key_exists('resolution', $request->data())
                && $request['aspect_ratio'] === '4:5'
                && $request['input_references'][0]['image_url']['url'] === $reference;
        });
    }

    public function test_standard_quality_is_forwarded_as_the_provider_1k_resolution(): void
    {
        Http::fake([
            'https://openrouter.test/api/v1/images' => Http::response([
                'data' => [['b64_json' => base64_encode('image')]],
            ]),
        ]);

        app(OpenRouterService::class)->generateImageFromPrompt(
            'google/gemini-3.1-flash-image',
            'Portrait',
            '720',
            '3:4'
        );

        Http::assertSent(function (Request $request) {
            return $request['resolution'] === '1K'
                && $request['aspect_ratio'] === '3:4';
        });
    }

    public function test_paid_quality_is_forwarded_as_the_provider_2k_resolution(): void
    {
        Http::fake([
            'https://openrouter.test/api/v1/images' => Http::response([
                'data' => [['b64_json' => base64_encode('image')]],
            ]),
        ]);

        app(OpenRouterService::class)->generateImageFromPrompt(
            'google/gemini-3.1-flash-image',
            'Portrait',
            '1080',
            '4:5',
            1,
            [
                'requested_output_resolution' => '1080',
                'requested_aspect_ratio' => '4:5',
                'order_id' => 18,
            ]
        );

        Http::assertSent(function (Request $request) {
            return $request['resolution'] === '2K'
                && $request['aspect_ratio'] === '4:5'
                && !array_key_exists('requested_output_resolution', $request->data())
                && !array_key_exists('requested_aspect_ratio', $request->data())
                && !array_key_exists('order_id', $request->data());
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

    public function test_openrouter_image_reference_is_not_removed_from_images_payload(): void
    {
        Http::fake([
            'https://openrouter.test/api/v1/images' => Http::response([
                'data' => [['b64_json' => base64_encode('image')]],
            ]),
        ]);

        $reference = 'data:image/png;base64,' . base64_encode('reference');

        app(OpenRouterService::class)->generateImageFromPrompt(
            'black-forest-labs/flux.2-klein-4b',
            'A clean product photo',
            '1K',
            '1:1',
            1,
            ['input_references' => [[
                'type' => 'image_url',
                'image_url' => ['url' => $reference],
            ]]]
        );

        Http::assertSent(function (Request $request) use ($reference) {
            return $request['model'] === 'black-forest-labs/flux.2-klein-4b'
                && $request['input_references'][0]['image_url']['url'] === $reference;
        });
    }

    public function test_flux_2_payload_drops_unsupported_resolution(): void
    {
        Http::fake([
            'https://openrouter.test/api/v1/images' => Http::response([
                'data' => [['b64_json' => base64_encode('image')]],
            ]),
        ]);

        app(OpenRouterService::class)->generateImageFromPrompt(
            'black-forest-labs/flux.2-pro',
            'A premium product photo',
            '2K',
            '4:5'
        );

        Http::assertSent(function (Request $request) {
            return $request['aspect_ratio'] === '4:5'
                && !array_key_exists('resolution', $request->data());
        });
    }

    public function test_legacy_output_format_is_normalized_for_images_api(): void
    {
        Http::fake([
            'https://openrouter.test/api/v1/images' => Http::response([
                'data' => [['b64_json' => base64_encode('image')]],
            ]),
        ]);

        app(OpenRouterService::class)->generateImageFromPrompt(
            'black-forest-labs/flux.2-klein-4b',
            'A studio product photo',
            '720',
            '1:1',
            1,
            ['output_format' => 'png_url']
        );

        Http::assertSent(function (Request $request) {
            return $request['output_format'] === 'png'
                && !array_key_exists('resolution', $request->data());
        });
    }
}
