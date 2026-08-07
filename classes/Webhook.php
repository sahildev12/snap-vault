<?php
/**
 * Outbound / inbound webhook helpers
 */

declare(strict_types=1);

class Webhook
{
    public static function dispatch(string $event, array $payload = []): void
    {
        $url = defined('WEBHOOK_URL') ? trim((string) WEBHOOK_URL) : '';
        if ($url === '') {
            return;
        }

        $body = json_encode([
            'event'     => $event,
            'app'       => APP_NAME,
            'timestamp' => date('c'),
            'data'      => $payload,
        ], JSON_UNESCAPED_UNICODE);

        if ($body === false) {
            return;
        }

        $secret = defined('WEBHOOK_SECRET') ? (string) WEBHOOK_SECRET : '';
        $signature = $secret !== '' ? hash_hmac('sha256', $body, $secret) : '';

        $ch = curl_init($url);
        if ($ch === false) {
            return;
        }

        $headers = [
            'Content-Type: application/json',
            'X-Jammu-Event: ' . $event,
        ];
        if ($signature !== '') {
            $headers[] = 'X-Jammu-Signature: sha256=' . $signature;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 4,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    public static function verifySignature(string $rawBody, ?string $header): bool
    {
        $secret = defined('WEBHOOK_SECRET') ? (string) WEBHOOK_SECRET : '';
        if ($secret === '') {
            return false;
        }
        if ($header === null || $header === '') {
            return false;
        }
        $provided = preg_replace('/^sha256=/i', '', trim($header)) ?? '';
        $expected = hash_hmac('sha256', $rawBody, $secret);
        return hash_equals($expected, $provided);
    }
}
