<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Minimal client for any OpenAI-compatible chat API (Ollama, Gemini, Groq...).
 * AI only turns text into structured suggestions; callers validate everything and
 * nothing is saved without the user confirming.
 */
class Ai
{
    /** Ask for a JSON object. Throws AiUnavailable if the model can't be reached or answers badly. */
    public static function json(string $system, string $prompt, int $maxTokens = 1500): array
    {
        $config = config('services.ai');
        $models = array_values(array_filter([$config['model'], $config['fallback_model'] ?? null]));
        // Thinking models count their reasoning against the limit, so leave generous room.
        $maxTokens = max($maxTokens, 2048);

        foreach ($models as $i => $model) {
            $last = $i === count($models) - 1;
            $response = self::request($config, $model, $system, $prompt, $maxTokens);

            if (! $response->successful()) {
                if (! $last && in_array($response->status(), [429, 500, 503], true)) {
                    continue; // busy or rate-limited: try the fallback model
                }
                throw new AiUnavailable($response->status() === 429
                    ? 'The AI assistant is busy (free limit reached). Please try again in a minute.'
                    : 'The AI assistant returned an error ('.$response->status().'). Please try again.');
            }

            $data = self::decode((string) $response->json('choices.0.message.content'));
            if ($data !== null) {
                return $data;
            }
            // Unreadable (e.g. cut off): the fallback model may do better.
        }

        throw new AiUnavailable('The AI assistant gave an unreadable answer. Please try again.');
    }

    private static function decode(string $content): ?array
    {
        // Some models wrap JSON in ``` fences.
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($content));
        $data = json_decode($content, true);

        return is_array($data) ? $data : null;
    }

    private static function request(array $config, string $model, string $system, string $prompt, int $maxTokens): Response
    {
        try {
            return Http::withToken($config['key'])
                ->timeout($config['timeout'])
                ->post(rtrim($config['base_url'], '/').'/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.2,
                    'max_tokens' => $maxTokens,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $system.' Reply with a single JSON object only.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);
        } catch (ConnectionException) {
            throw new AiUnavailable('The AI assistant is not reachable. Is the AI service running?');
        }
    }
}
