<?php

require_once __DIR__ . '/src/bootstrap.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_SESSION['access_token'])) {
    clearLocalSession();
    session_start();
}

$error = '';
$info = '';

$motivo = (string)($_GET['motivo'] ?? '');
if (in_array($motivo, ['sessao', 'expirada', 'invalida', 'csrf'], true)) {
    $info = $motivo === 'csrf'
        ? 'Sua sessão expirou ou foi renovada. Entre novamente para continuar.'
        : 'Sua sessão foi encerrada. Entre novamente para continuar.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = postString('email', true);
        $password = postString('password', true);

        $session = $auth->signIn($email, $password);

        if (empty($session['access_token'])) {
            throw new RuntimeException('O Supabase não retornou um token de acesso.');
        }

        // Evita reutilização/fixação de uma sessão anterior.
        session_regenerate_id(true);

        $_SESSION['access_token'] = $session['access_token'];
        $_SESSION['refresh_token'] = $session['refresh_token'] ?? null;
        $_SESSION['user'] = $session['user'] ?? ['email' => $email];

        $loginSupabase = new SupabaseClient(
            $config['supabase']['url'],
            $config['supabase']['anon_key'],
            $session['access_token']
        );

        $profileRows = $loginSupabase->select('perfis', [
            'select' => 'nivel,nome',
            'user_id' => 'eq.' . ($_SESSION['user']['id'] ?? ''),
            'limit' => 1,
        ]);

        $_SESSION['user_role'] = $profileRows[0]['nivel'] ?? 'executante';
        $_SESSION['user_name'] = $profileRows[0]['nome'] ?? $email;

        redirect($_SESSION['user_role'] === 'admin' ? 'dashboard.php' : 'movimentacao.php');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acesso ao Controle de Estoque</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-7 shadow-lg">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Controle de Estoque</h1>
            <p class="mt-1 text-sm text-slate-500">Residencial Vivere Palhano</p>
        </div>

        <?php if ($info): ?>
            <div class="mb-4 rounded-lg border border-blue-300 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                <?= e($info) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
<label class="block">
                <span class="mb-1 block text-sm font-medium">E-mail</span>
                <input name="email" type="email" required autocomplete="username"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5">
            </label>

            <label class="block">
                <span class="mb-1 block text-sm font-medium">Senha</span>
                <input name="password" type="password" required autocomplete="current-password"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5">
            </label>

            <button class="w-full rounded-lg bg-slate-900 px-4 py-2.5 font-semibold text-white hover:bg-slate-800">
                Entrar
            </button>
        </form>
    </div>
</body>
</html>
