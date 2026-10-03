<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** اتصال یک شات کتابخانه به یک محصول پروداکتی (فعال، پیش‌فرض، قیمت، ترتیب). */
class ProductShot extends Model
{
    protected $fillable = [
        'product_id', 'shot_id', 'enabled', 'is_default', 'credits_override', 'sample_image', 'sort',
        'prompt_override', 'model_configuration', 'allowed_aspect_ratios', 'aspect_ratio_default',
        'aspect_ratio_user_selectable', 'options_enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'is_default' => 'boolean',
        'credits_override' => 'integer',
        'model_configuration' => 'array',
        'allowed_aspect_ratios' => 'array',
        'aspect_ratio_user_selectable' => 'boolean',
        'options_enabled' => 'array',
        'sort' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function shot(): BelongsTo
    {
        return $this->belongsTo(ShotLibrary::class, 'shot_id');
    }

    public function credits(string $quality = 'standard'): int
    {
        $configured = (array) data_get($this->model_configuration, 'quality_credits', []);
        if (array_key_exists($quality, $configured) && is_numeric($configured[$quality])) {
            return max(0, (int) $configured[$quality]);
        }

        return max(0, (int) ($this->credits_override ?? $this->shot?->default_credits ?? 0));
    }

    public function qualityModel(string $quality): array
    {
        $value = data_get($this->model_configuration, "quality_models.{$quality}", []);

        return is_array($value) ? $value : [];
    }

    public function allowedRatios(): array
    {
        $supported = (array) config('product_shots.aspect_ratios', ['4:5', '1:1', '9:16']);
        $configured = array_values(array_intersect($supported, (array) $this->allowed_aspect_ratios));

        return $configured ?: [$this->defaultRatio()];
    }

    public function defaultRatio(): string
    {
        $supported = (array) config('product_shots.aspect_ratios', ['4:5', '1:1', '9:16']);
        $ratio = (string) ($this->aspect_ratio_default ?: $this->shot?->aspect_ratio_default ?: config('product_shots.default_aspect_ratio', '4:5'));

        return in_array($ratio, $supported, true) ? $ratio : (string) config('product_shots.default_aspect_ratio', '4:5');
    }

    public function optionEnabled(string $key, bool $default = true): bool
    {
        $options = (array) $this->options_enabled;

        return array_key_exists($key, $options) ? (bool) $options[$key] : $default;
    }

    public function sampleImageUrl(): ?string
    {
        return ShotLibrary::publicUrl($this->sample_image) ?? $this->shot?->sampleImageUrl();
    }
}
