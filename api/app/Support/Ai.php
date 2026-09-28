<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
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

        try {
            $response = Http::withToken($config['key'])
                ->timeout($config['timeout'])
                ->post(rtrim($config['base_url'], '/').'/chat/completions', [
                    'model' => $config['model'],
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

        if (! $response->successful()) {
            throw new AiUnavailable('The AI assistant returned an error ('.$response->status().'). Please try again.');
        }

        $content = (string) $response->json('choices.0.message.content');
        // Some models wrap JSON in ``` fences.
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($content));
        $data = json_decode($content, true);

        if (! is_array($data)) {
            throw new AiUnavailable('The AI assistant gave an unreadable answer. Please try again.');
        }

        return $data;
    }
}
