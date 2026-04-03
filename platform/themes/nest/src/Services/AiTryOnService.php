<?php

namespace Theme\Nest\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AiTryOnService
{
    /**
     * @return array<int, string> Public URLs of generated images
     */
    public function generateTryOnImages(
        string $apiKey,
        string $personImagePath,
        string $referenceImagePath,
        string $prompt,
        int $count = 3
    ): array {
        $endpoint = rtrim((string) env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/') . '/images/edits';
        $model = (string) env('OPENAI_IMAGE_MODEL', 'gpt-image-1');

        $http = Http::withToken($apiKey)
            ->acceptJson()
            ->asMultipart()
            ->attach('image[]', fopen($personImagePath, 'r'), basename($personImagePath))
            ->attach('image[]', fopen($referenceImagePath, 'r'), basename($referenceImagePath));

        $result = $http->post($endpoint, [
            'model' => $model,
            'prompt' => $prompt,
            'n' => $count,
            'size' => '1024x1536',
            'quality' => 'high',
            'output_format' => 'png',
            'input_fidelity' => 'high',
        ]);

        if (! $result->successful()) {
            $message = $result->json('error.message') ?: $result->body();
            throw new RuntimeException('AI try-on failed: ' . $message);
        }

        $images = $result->json('data') ?: [];

        if (! is_array($images) || ! $images) {
            throw new RuntimeException('AI try-on failed: empty response.');
        }

        $urls = [];
        $batchId = (string) Str::uuid();

        foreach ($images as $index => $item) {
            $b64 = $item['b64_json'] ?? null;

            if (! is_string($b64) || $b64 === '') {
                continue;
            }

            $binary = base64_decode($b64, true);

            if ($binary === false) {
                continue;
            }

            $path = sprintf('tryon/results/%s-%d.png', $batchId, (int) $index + 1);
            Storage::disk('public')->put($path, $binary);
            $urls[] = Storage::disk('public')->url($path);
        }

        if (! $urls) {
            throw new RuntimeException('AI try-on failed: could not decode images.');
        }

        return $urls;
    }
}

