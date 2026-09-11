<?php

declare(strict_types=1);

// Sessão configurada explicitamente para evitar diferenças entre
// InfinityFree, AeonFree e navegadores diferentes.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('vivere_estoque_session');

    $isHttps =
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

$configFile = dirname(__DIR__) . '/config.php';

if (!file_exists($configFile)) {
    http_response_code(500);
    exit('Arquivo config.php não encontrado. Configure o arquivo config.php com os dados do Supabase.');
}

$config = require $configFile;

date_default_timezone_set($config['app']['timezone'] ?? 'America/Sao_Paulo');

require_once __DIR__ . '/SupabaseClient.php';
require_once __DIR__ . '/AuthClient.php';
require_once __DIR__ . '/helpers.php';

$auth = new AuthClient(
    $config['supabase']['url'],
    $config['supabase']['anon_key']
);

$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$publicPages = ['login.php'];

$accessToken = $_SESSION['access_token'] ?? null;
$refreshToken = $_SESSION['refresh_token'] ?? null;

if (!in_array($currentPage, $publicPages, true)) {
    if (!$accessToken || empty($_SESSION['user']['id'])) {
        clearLocalSession();
        header('Location: login.php?motivo=sessao');
        exit;
    }

    if (sessionTokenNeedsRefresh($accessToken)) {
        try {
            if (!$refreshToken) {
                throw new RuntimeException('Refresh token ausente.');
            }

            $newSession = $auth->refreshSession($refreshToken);
            if (empty($newSession['access_token'])) {
                throw new RuntimeException('Não foi possível renovar a sessão.');
            }

            $_SESSION['access_token'] = $newSession['access_token'];
            $_SESSION['refresh_token'] = $newSession['refresh_token'] ?? $refreshToken;
            if (!empty($newSession['user'])) {
                $_SESSION['user'] = $newSession['user'];
            }
            $accessToken = $_SESSION['access_token'];
        } catch (Throwable $e) {
            clearLocalSession();
            header('Location: login.php?motivo=expirada');
            exit;
        }
    }
}

$supabase = new SupabaseClient(
    $config['supabase']['url'],
    $config['supabase']['anon_key'],
    $accessToken
);

if ($accessToken && !in_array($currentPage, $publicPages, true)) {
    try {
        $remoteUser = $auth->getUser($accessToken);
        if (empty($remoteUser['id'])) {
            throw new RuntimeException('Usuário da sessão não encontrado.');
        }

        $_SESSION['user'] = $remoteUser;

        $profileRows = $supabase->select('perfis', [
            'select' => 'nivel,nome',
            'user_id' => 'eq.' . $remoteUser['id'],
            'limit' => 1,
        ]);

        if (!$profileRows) {
            throw new RuntimeException('Perfil de acesso não encontrado.');
        }

        $_SESSION['user_role'] = $profileRows[0]['nivel'] ?? 'executante';
        $_SESSION['user_name'] = $profileRows[0]['nome'] ?? ($remoteUser['email'] ?? '');
    } catch (Throwable $e) {
        clearLocalSession();
        header('Location: login.php?motivo=invalida');
        exit;
    }
}
