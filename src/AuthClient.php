<?php

declare(strict_types=1);

final class AuthClient
{
    private string $authUrl;
    private string $anonKey;

    public function __construct(string $url, string $anonKey)
    {
        $this->authUrl = rtrim($url, '/') . '/auth/v1';
        $this->anonKey = $anonKey;
    }

    public function signIn(string $email, string $password): array
    {
        return $this->request(
            'POST',
            '/token?grant_type=password',
            ['email' => $email, 'password' => $password]
        );
    }

    public function refreshSession(string $refreshToken): array
    {
        return $this->request(
            'POST',
            '/token?grant_type=refresh_token',
            ['refresh_token' => $refreshToken]
        );
    }

    public function signOut(string $accessToken): void
    {
        $this->request('POST', '/logout', null, $accessToken);
    }

    public function getUser(string $accessToken): array
    {
        return $this->request('GET', '/user', null, $accessToken);
    }

    private function request(
        string $method,
        string $path,
        ?array $payload = null,
        ?string $accessToken = null
    ): array {
        $ch = curl_init($this->authUrl . $path);

        $headers = [
            'apikey: ' . $this->anonKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($accessToken) {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        }

        $response = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Falha de comunicação com Supabase Auth: ' . $error);
        }

        $data = $response !== '' ? json_decode($response, true) : [];

        if ($status < 200 || $status >= 300) {
            $message = is_array($data)
                ? ($data['msg'] ?? $data['error_description'] ?? $data['message'] ?? 'Falha de autenticação.')
                : 'Falha de autenticação.';
            throw new RuntimeException($message);
        }

        return is_array($data) ? $data : [];
    }
}
