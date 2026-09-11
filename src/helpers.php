<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = compact('type', 'message');
}

function getFlashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function validateCsrf(): void
{
    $sent = (string)($_POST['csrf'] ?? '');
    $stored = (string)($_SESSION['csrf'] ?? '');

    if ($sent === '' || $stored === '' || !hash_equals($stored, $sent)) {
        // Em hospedagens gratuitas a sessão pode expirar entre a abertura
        // do formulário e o envio. Em vez de exibir erro técnico, volta
        // ao login para criar uma sessão limpa.
        clearLocalSession();
        header('Location: login.php?motivo=csrf');
        exit;
    }
}

function postString(string $key, bool $required = false): string
{
    $value = trim((string)($_POST[$key] ?? ''));
    if ($required && $value === '') {
        throw new InvalidArgumentException("Campo obrigatório: {$key}");
    }
    return $value;
}

function postFloat(string $key, bool $required = false): float
{
    $raw = str_replace(',', '.', trim((string)($_POST[$key] ?? '')));
    if ($required && $raw === '') {
        throw new InvalidArgumentException("Campo obrigatório: {$key}");
    }
    if ($raw === '') {
        return 0.0;
    }
    if (!is_numeric($raw)) {
        throw new InvalidArgumentException("Valor inválido: {$key}");
    }
    return (float)$raw;
}

function materialStatus(float $current, float $minimum): string
{
    return $current <= $minimum ? 'critico' : 'normal';
}


function userRole(): string
{
    return $_SESSION['user_role'] ?? 'executante';
}

function isAdmin(): bool
{
    return userRole() === 'admin';
}

function requireAdmin(): void
{
    if (!isAdmin()) {
        flash('error', 'Você não possui permissão para acessar esta área.');
        redirect('movimentacao.php');
    }
}

function requireRole(array $roles): void
{
    if (!in_array(userRole(), $roles, true)) {
        flash('error', 'Você não possui permissão para acessar esta área.');
        redirect(isAdmin() ? 'dashboard.php' : 'movimentacao.php');
    }
}


function clearLocalSession(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function jwtExpiration(?string $token): ?int
{
    if (!$token) {
        return null;
    }

    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    $payload = strtr($parts[1], '-_', '+/');
    $padding = strlen($payload) % 4;
    if ($padding) {
        $payload .= str_repeat('=', 4 - $padding);
    }

    $decoded = base64_decode($payload, true);
    if ($decoded === false) {
        return null;
    }

    $data = json_decode($decoded, true);
    return isset($data['exp']) ? (int)$data['exp'] : null;
}

function sessionTokenNeedsRefresh(?string $token, int $marginSeconds = 60): bool
{
    $exp = jwtExpiration($token);
    if ($exp === null) {
        return true;
    }

    return $exp <= (time() + $marginSeconds);
}
