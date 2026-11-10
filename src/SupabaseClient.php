<?php

declare(strict_types=1);

final class SupabaseClient
{
    private string $baseUrl;
    private string $projectUrl;
    private string $apiKey;
    private string $bearerToken;

    public function __construct(string $url, string $apiKey, ?string $bearerToken = null)
    {
        $this->projectUrl = rtrim($url, '/');
        $this->baseUrl = $this->projectUrl . '/rest/v1';
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

    public function storageUpload(string $bucket, string $path, string $bytes, string $mime): void
    {
        $this->storageRequest('POST', $bucket, $path, $bytes, [
            'Content-Type: ' . $mime,
            'x-upsert: false',
        ]);
    }

    public function storageDownload(string $bucket, string $path): array
    {
        return $this->storageRequest('GET', $bucket, $path);
    }

    public function storageDelete(string $bucket, string $path): void
    {
        $this->storageRequest('DELETE', $bucket, $path);
    }

    private function storageRequest(
        string $method,
        string $bucket,
        string $path,
        ?string $body = null,
        array $extraHeaders = []
    ): array {
        $encodedPath = implode('/', array_map('rawurlencode', explode('/', ltrim($path, '/'))));
        $url = $this->projectUrl . '/storage/v1/object/' . rawurlencode($bucket) . '/' . $encodedPath;
        $headers = array_merge([
            'apikey: ' . $this->apiKey,
            'Authorization: Bearer ' . $this->bearerToken,
        ], $extraHeaders);
        $responseHeaders = [];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $header) use (&$responseHeaders): int {
                $length = strlen($header);
                $parts = explode(':', $header, 2);
                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return $length;
            },
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Falha de comunicação com o armazenamento: ' . $error);
        }
        if ($status < 200 || $status >= 300) {
            $decoded = json_decode($response, true);
            $message = is_array($decoded)
                ? ($decoded['message'] ?? $decoded['error'] ?? 'Falha no armazenamento.')
                : 'Falha no armazenamento.';
            throw new RuntimeException("Storage HTTP {$status}: {$message}");
        }

        return [
            'body' => $response,
            'content_type' => $responseHeaders['content-type'] ?? 'application/octet-stream',
        ];
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
