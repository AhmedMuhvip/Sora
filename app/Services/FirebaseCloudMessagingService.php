<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseCloudMessagingService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $tokens = $user->deviceTokens()
            ->whereNull('disabled_at')
            ->pluck('token', 'id');

        foreach ($tokens as $deviceTokenId => $token) {
            $this->sendToToken((int) $deviceTokenId, $token, $title, $body, $data);
        }
    }

    private function sendToToken(int $deviceTokenId, string $token, string $title, string $body, array $data): void
    {
        $response = Http::withToken($this->accessToken())
            ->post($this->messagingUrl(), [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $this->stringData($data),
                ],
            ]);

        if ($response->successful()) {
            DeviceToken::query()
                ->whereKey($deviceTokenId)
                ->update(['last_used_at' => now()]);

            return;
        }

        if (in_array($response->status(), [400, 404], true)) {
            DeviceToken::query()
                ->whereKey($deviceTokenId)
                ->update(['disabled_at' => now()]);
        }

        Log::warning('Firebase message send failed.', [
            'device_token_id' => $deviceTokenId,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);
    }

    private function accessToken(): string
    {
        return Cache::remember('firebase_messaging_access_token', now()->addMinutes(50), function (): string {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $this->serviceAccountJwt(),
            ]);

            $response->throw();

            return (string) $response->json('access_token');
        });
    }

    private function serviceAccountJwt(): string
    {
        $now = time();
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR));
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => config('services.firebase.client_email'),
            'scope' => self::SCOPE,
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));
        $payload = $header.'.'.$claims;
        $privateKey = str_replace('\\n', "\n", (string) config('services.firebase.private_key'));

        openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return $payload.'.'.$this->base64UrlEncode($signature);
    }

    private function isConfigured(): bool
    {
        return (bool) config('services.firebase.messaging_enabled')
            && filled(config('services.firebase.project_id'))
            && filled(config('services.firebase.client_email'))
            && filled(config('services.firebase.private_key'));
    }

    private function messagingUrl(): string
    {
        return sprintf(
            'https://fcm.googleapis.com/v1/projects/%s/messages:send',
            config('services.firebase.project_id'),
        );
    }

    private function stringData(array $data): array
    {
        return collect($data)
            ->map(fn ($value) => is_scalar($value) ? (string) $value : json_encode($value, JSON_THROW_ON_ERROR))
            ->all();
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
