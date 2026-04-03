<?php

namespace Theme\Nest\Http\Controllers;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Models\Product;
use Botble\Media\Facades\RvMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Theme\Nest\Services\AiTryOnService;

class TryOnController
{
    public function generate(Request $request, int $id, BaseHttpResponse $response, AiTryOnService $aiTryOnService)
    {
        $apiKey = (string) env('OPENAI_API_KEY');

        if (! $apiKey) {
            return $response
                ->setError()
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setMessage('AI try-on is not configured. Please set OPENAI_API_KEY in your .env.');
        }

        $validated = $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'prompt' => ['nullable', 'string', 'max:8000'],
            'consent' => ['accepted'],
        ]);

        /** @var \Botble\Ecommerce\Models\Product $product */
        $product = Product::query()->findOrFail($id);

        $referenceImagePath = RvMedia::getRealPath($product->image);

        if (! $referenceImagePath || ! is_file($referenceImagePath)) {
            return $response
                ->setError()
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setMessage('Product reference image is missing.');
        }

        $uploadPath = $validated['photo']->store('tryon/uploads', 'public');
        $uploadRealPath = Storage::disk('public')->path($uploadPath);

        try {
            $prompt = $this->buildPrompt($validated['prompt'] ?? null);

            $images = $aiTryOnService->generateTryOnImages(
                apiKey: $apiKey,
                personImagePath: $uploadRealPath,
                referenceImagePath: $referenceImagePath,
                prompt: $prompt,
                count: 3
            );

            return $response->setData([
                'images' => $images,
            ]);
        } catch (RuntimeException $exception) {
            return $response
                ->setError()
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setMessage($exception->getMessage());
        } finally {
            Storage::disk('public')->delete($uploadPath);
        }
    }

    private function buildPrompt(?string $customPrompt): string
    {
        $default = implode("\n", [
            'Virtual try-on: Put the saree from the reference image onto the person in the uploaded photo.',
            'Preserve the person’s face identity exactly (no change to facial features, age, skin tone, or expression).',
            'Keep the same pose, camera angle, framing, lighting, ornaments/jewelry, and add maruthani (mehendi) visible on her hand.',
            'Make the saree draping realistic with perfect pleats, natural folds, and accurate fabric texture.',
            'Photorealistic, highly detailed, professional portrait fashion photography.',
            'Output: 3 variations, portrait 9:16.',
        ]);

        $customPrompt = trim((string) $customPrompt);

        if ($customPrompt !== '') {
            return $customPrompt;
        }

        return $default;
    }
}
