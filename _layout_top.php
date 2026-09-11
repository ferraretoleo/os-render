<?php
$flashes = getFlashes();
$pageTitle = $pageTitle ?? 'Controle de Estoque';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-900">
<div class="min-h-screen">
    <header class="bg-slate-900 text-white shadow">
        <div class="mx-auto max-w-7xl px-4 py-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-xl font-bold">Controle de Estoque</h1>
                <p class="text-sm text-slate-300">Residencial Vivere Palhano</p>
            </div>
            <nav class="flex flex-wrap items-center gap-2 text-sm">
                <div class="mr-2 text-right">
                    <div class="text-slate-200"><?= e($_SESSION['user_name'] ?? $_SESSION['user']['email'] ?? '') ?></div>
                    <div class="text-xs text-slate-400"><?= isAdmin() ? 'Administrador' : 'Executante' ?></div>
                </div>

                <?php if (isAdmin()): ?>
                    <a class="rounded-lg bg-slate-800 px-3 py-2 hover:bg-slate-700" href="dashboard.php">Dashboard</a>
                    <a class="rounded-lg bg-slate-800 px-3 py-2 hover:bg-slate-700" href="material_form.php">Novo material</a>
                <?php endif; ?>

                <a class="rounded-lg bg-slate-800 px-3 py-2 hover:bg-slate-700" href="movimentacao.php">Movimentação</a>
                <a class="rounded-lg bg-slate-800 px-3 py-2 hover:bg-slate-700" href="historico.php">Histórico</a>
                <a class="rounded-lg bg-emerald-700 px-3 py-2 hover:bg-emerald-600" href="pedido.php">Compra</a>

                <?php if (isAdmin()): ?>
                    <a class="rounded-lg bg-slate-800 px-3 py-2 hover:bg-slate-700" href="relatorio_pedidos.php">Relatório de compras</a>
                    <a class="rounded-lg bg-amber-700 px-3 py-2 hover:bg-amber-600" href="conferencia.php">Conferência</a>
                    <a class="rounded-lg bg-indigo-700 px-3 py-2 hover:bg-indigo-600" href="usuarios.php">Usuários</a>
                <?php endif; ?>

                <a class="rounded-lg bg-red-700 px-3 py-2 hover:bg-red-600" href="logout.php">Sair</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6">
        <?php foreach ($flashes as $flash): ?>
            <div class="mb-4 rounded-lg border px-4 py-3 <?= $flash['type'] === 'error' ? 'border-red-300 bg-red-50 text-red-800' : 'border-emerald-300 bg-emerald-50 text-emerald-800' ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endforeach; ?>
