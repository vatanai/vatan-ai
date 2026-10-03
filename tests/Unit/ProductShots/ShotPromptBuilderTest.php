<?php

namespace Tests\Unit\ProductShots;

use App\Models\Product;
use App\Models\ShotLibrary;
use App\Services\ProductShots\ShotGrammar;
use App\Services\ProductShots\ShotPromptBuilder;
use Tests\TestCase;

class ShotPromptBuilderTest extends TestCase
{
    private function shot(array $tokens, ?string $template = null): ShotLibrary
    {
        return new ShotLibrary(['key' => 't', 'name_fa' => 'تست', 'tokens' => $tokens, 'prompt_template' => $template]);
    }

    public function test_grammar_normalize_drops_unknown_axes_and_tokens(): void
    {
        $clean = ShotGrammar::normalize([
            'framing' => ['hero-centered', 'closeup-macro'],
            'lighting' => ['soft-window', 'not-a-token'],
            'bogus' => 'x',
            'human' => 'alien',
        ]);

        $this->assertSame('hero-centered', $clean['framing']);
        $this->assertSame(['soft-window'], $clean['lighting']);
        $this->assertArrayNotHasKey('bogus', $clean);
        $this->assertArrayNotHasKey('human', $clean);
    }

    public function test_prompt_contains_token_phrases_fidelity_and_output_requirements(): void
    {
        $product = new Product(['shot_settings' => ['product_description' => 'amber glass serum bottle', 'brand_palette' => 'peach and gold']]);
        $prompt = app(ShotPromptBuilder::class)->build($product, $this->shot([
            'framing' => 'hero-centered', 'lighting' => ['hard-sun-leaf-shadow'], 'surface' => 'travertine-plinth',
            'props' => ['liquid-splash', 'ingredients-scatter'], 'human' => 'none', 'mood' => 'warm-natural',
        ]), '9:16');

        $this->assertStringContainsString('amber glass serum bottle', $prompt);
        $this->assertStringContainsString('palm-leaf shadows', $prompt);
        $this->assertStringContainsString('travertine stone plinth', $prompt);
        $this->assertStringContainsString('splash of liquid', $prompt);
        $this->assertStringContainsString('peach and gold', $prompt);
        $this->assertStringContainsString(ShotPromptBuilder::productFidelityBlock(), $prompt);
        $this->assertStringContainsString('aspect ratio 9:16', $prompt);
        $this->assertStringNotContainsString('fictional generic adult model', $prompt);
        $this->assertStringNotContainsString('{', $prompt);
    }

    public function test_human_shots_get_safety_block(): void
    {
        $prompt = app(ShotPromptBuilder::class)->build(new Product(), $this->shot(['human' => 'model-portrait-with-prop']));

        $this->assertStringContainsString('fictional generic adult model', $prompt);
    }

    public function test_enabled_brand_identity_prompt_is_appended_at_the_end(): void
    {
        $custom = 'Use the same warm amber highlights and restrained luxury direction in every image.';
        $product = new Product(['shot_settings' => [
            'brand_identity_enabled' => true,
            'brand_identity_prompt' => $custom,
        ]]);

        $prompt = app(ShotPromptBuilder::class)->build($product, $this->shot(['mood' => 'clean-luxury']));

        $this->assertStringContainsString('Brand identity continuity', $prompt);
        $this->assertStringEndsWith($custom, $prompt);
    }

    public function test_disabled_brand_identity_prompt_is_not_sent(): void
    {
        $product = new Product(['shot_settings' => [
            'brand_identity_enabled' => false,
            'brand_identity_prompt' => 'This must not be sent.',
        ]]);

        $prompt = app(ShotPromptBuilder::class)->build($product, $this->shot(['mood' => 'clean-luxury']));

        $this->assertStringNotContainsString('This must not be sent.', $prompt);
        $this->assertStringNotContainsString('Brand identity continuity', $prompt);
    }

    public function test_custom_template_placeholders_and_single_fidelity_block(): void
    {
        $prompt = app(ShotPromptBuilder::class)->build(
            new Product(['shot_settings' => ['product_description' => 'red lipstick']]),
            $this->shot(['mood' => 'editorial'], 'Ad of {product}. Mood: {mood}. {fidelity} Ratio {aspect_ratio}.'),
            '1:1'
        );

        $this->assertStringStartsWith('Ad of red lipstick. Mood: high-fashion magazine editorial mood.', $prompt);
        $this->assertSame(1, substr_count($prompt, 'Product fidelity (highest priority)'));
        $this->assertStringContainsString('Ratio 1:1', $prompt);
    }

    public function test_seeded_style_tokens_all_resolve(): void
    {
        foreach (ShotGrammar::vocabulary() as $axis => $tokens) {
            foreach ($tokens as $key => $token) {
                $this->assertNotSame('', ShotGrammar::phrase($axis, $key), "$axis.$key");
                $this->assertNotSame('', $token['fa']);
            }
        }
    }
}
