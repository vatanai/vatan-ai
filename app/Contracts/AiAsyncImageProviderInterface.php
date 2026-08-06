<?php

namespace App\Contracts;

use App\Models\AiModel;

interface AiAsyncImageProviderInterface
{
    public function submitGeneration(AiModel $model, string $prompt, array $payload = [], ?string $webhookUrl = null): array;

    public function getGenerationStatus(AiModel $model, string $requestId): array;

    public function cancelGeneration(AiModel $model, string $requestId): array;

    public function handleWebhook(array $payload): array;

    public function normalizeResponse(AiModel $model, array $payload): array;

    public function estimateCost(AiModel $model, array $payload = []): ?float;
}
