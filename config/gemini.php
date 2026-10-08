<?php
// Google Gemini API client for the CommunityConnect chatbot.
require_once __DIR__ . '/env.php';

const GEMINI_SYSTEM_INSTRUCTION = "You are CommunityConnect's assistant for Sto. Niño Barangay Hall. "
    . "Be helpful, polite, and reasonably concise. You can chat normally about anything, but you're "
    . "primarily here to help residents with barangay services: document requests (clearance, "
    . "indigency, residency, business permits), reporting problems, lost & found items, and general "
    . "information about the barangay. If asked something you genuinely can't help with (e.g. legal "
    . "advice, medical advice, anything outside a barangay assistant's scope), say so plainly and "
    . "suggest they contact the barangay hall directly.";

/**
 * Calls the Gemini API with a short conversation history and returns the
 * assistant's reply text, or null on any failure (caller should show a
 * friendly fallback message instead of raising an error to the resident).
 *
 * @param array<int, array{role: string, text: string}> $history oldest-first
 */
function gemini_generate_reply(array $history): ?string
{
    $apiKey = env('GEMINI_API_KEY');
    $model = env('GEMINI_MODEL', 'gemini-3.8-flash');

    if (!$apiKey) {
        return null;
    }

    $contents = array_map(
        static fn(array $turn) => [
            'role' => $turn['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $turn['text']]],
        ],
        $history
    );

    $payload = [
        'system_instruction' => ['parts' => [['text' => GEMINI_SYSTEM_INSTRUCTION]]],
        'contents' => $contents,
        'generationConfig' => [
            'thinkingConfig' => ['thinkingBudget' => 0],
            'maxOutputTokens' => 512,
        ],
    ];

    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlError !== '' || $httpCode !== 200) {
        error_log("Gemini API call failed (HTTP $httpCode): $curlError " . substr((string)$response, 0, 500));
        return null;
    }

    $data = json_decode($response, true);
    $parts = $data['candidates'][0]['content']['parts'] ?? null;
    if (!$parts) {
        return null;
    }

    $text = implode('', array_column($parts, 'text'));
    return $text !== '' ? $text : null;
}
