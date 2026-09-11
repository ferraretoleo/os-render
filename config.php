<?php

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scheme = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
) ? 'https' : 'http';

$detectedBaseUrl = $scheme . '://' . $host;

return [
    'app' => [
        'name' => getenv('APP_NAME') ?: 'Controle de Estoque',
        'timezone' => getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo',
        'base_url' => getenv('APP_BASE_URL') ?: $detectedBaseUrl,
    ],
    'supabase' => [
        'url' => rtrim(
            getenv('SUPABASE_URL') ?: 'https://bjddwpaafiwjegyaknsg.supabase.co',
            '/'
        ),
        'anon_key' => getenv('SUPABASE_PUBLISHABLE_KEY') ?: 'sb_publishable_HF_KXn13osfFII2aIasijQ_2zsAEFGb',
    ],
];
