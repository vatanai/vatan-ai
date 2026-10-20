<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImportSetting extends Model
{
    protected $fillable = ['prompt_amendment', 'text_model'];

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'prompt_amendment' => self::defaultAmendment(),
            'text_model' => 'openai/gpt-4o-mini',
        ]);
    }

    public static function defaultAmendment(): string
    {
        return 'Adapt this image-generation prompt for Vatan product photography. Preserve its original creative idea, scene, lighting, composition, camera direction, and specific shot details as closely as possible; make only the smallest necessary edits. The user will provide a raw photo of their own product. Treat that photo as the sole source of truth and preserve the exact product identity, shape, proportions, color, material, packaging, label, logo, and visible text. Never invent product features, claims, brands, or accessories. Make the prompt create one polished, commercially useful product photograph for the requested slide, not a grid or collage. Remove references to external websites, prompt libraries, ChatGPT, or other image services. Return a ready-to-use image prompt in English, with no commentary.';
    }
}
