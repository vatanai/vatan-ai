<?php

namespace App\Contracts;

use App\Models\AiModel;
use App\Models\Product;

interface AiImageProviderInterface
{
    public function provider(): string;

    public function validateModelConfiguration(AiModel $model): array;

    public function getModelCapabilities(AiModel $model): array;

    public function generateForProduct(Product $product, string $prompt, string $resolution, string $aspectRatio, int $count = 1, array $extraPayload = []): array;

    public function editImageForProduct(Product $product, string $prompt, array $base64Images = []): array;

    public function generateImageFromPrompt(string $modelId, string $prompt, string $resolution = '1K', string $aspectRatio = '1:1', int $n = 1, array $extraPayload = []): array;

    public function generateImage(AiModel $aiModel, string $prompt, array $extraPayload = [], ?int $timeoutOverride = null): array;

    public function editImageWithModel(string $modelId, string $prompt, array $base64Images, ?int $timeout = null): array;
}
