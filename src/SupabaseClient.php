<?php

declare(strict_types=1);

final class SupabaseClient
{
    private string $baseUrl;
    private string $apiKey;
    private string $bearerToken;

    public function __construct(string $url, string $apiKey, ?string $bearerToken = null)
    {
        $this->baseUrl = rtrim($url, '/') . '/rest/v1';
        $this->apiKey = $apiKey;
        $this->bearerToken = $bearerToken ?: $apiKey;
    }

    public function select(string $table, array $query = []): array
    {
        return $this->request('GET', $table, null, $query);
    }

    public function insert(string $table, array $data, bool $returnRepresentation = true): array
    {
        return $this->request(
            'POST',
            $table,
            $data,
            [],
            ['Prefer: return=' . ($returnRepresentation ? 'representation' : 'minimal')]
        );
    }

    public function update(string $table, array $data, array $filters): array
    {
        return $this->request(
            'PATCH',
            $table,
            $data,
            $filters,
            ['Prefer: return=representation']
        );
    }

    public function delete(string $table, array $filters): array
    {
        return $this->request(
            'DELETE',
            $table,
            null,
            $filters,
            ['Prefer: return=representation']
        );
    }

    public function rpc(string $function, array $payload = []): array
    {
        return $this->request('POST', 'rpc/' . $function, $payload);
    }

    private function request(
        string $method,
        string $resource,
        ?array $body = null,
        array $query = [],
        array $extraHeaders = []
    ): array {
        $url = $this->baseUrl . '/' . ltrim($resource, '/');

        if ($query) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $headers = array_merge([
            'apikey: ' . $this->apiKey,
            'Authorization: Bearer ' . $this->bearerToken,
            'Content-Type: application/json',
            'Accept: application/json',
        ], $extraHeaders);

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Falha de comunicação com Supabase: ' . $error);
        }

        $decoded = $response !== '' ? json_decode($response, true) : [];

        if ($status < 200 || $status >= 300) {
            $message = is_array($decoded)
                ? ($decoded['message'] ?? $decoded['error_description'] ?? json_encode($decoded, JSON_UNESCAPED_UNICODE))
                : $response;

            throw new RuntimeException("Supabase HTTP {$status}: {$message}");
        }

        return is_array($decoded) ? $decoded : [];
    }
}
