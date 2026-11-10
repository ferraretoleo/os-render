<?php

require_once __DIR__ . '/src/bootstrap.php';

$id = trim((string)($_GET['id'] ?? ''));
if ($id === '') {
    flash('error', 'Orçamento não informado.');
    redirect('orcamentos.php');
}

try {
    $rows = $supabase->select('orcamentos', [
        'select' => '*',
        'id' => 'eq.' . $id,
        'limit' => 1,
    ]);
    if (!$rows) {
        throw new RuntimeException('Orçamento não encontrado.');
    }
    $budget = $rows[0];
    $history = $supabase->select('orcamento_historico', [
        'select' => 'id,status_anterior,status_novo,observacao,alterado_por,alterado_em',
        'orcamento_id' => 'eq.' . $id,
        'order' => 'alterado_em.desc',
    ]);

    $userIds = array_values(array_unique(array_filter(array_merge(
        [$budget['criado_por'] ?? null, $budget['decidido_por'] ?? null],
        array_column($history, 'alterado_por')
    ))));
    $userNames = [];
    if ($userIds) {
        $profiles = $supabase->select('perfis', [
            'select' => 'user_id,nome,email',
            'user_id' => 'in.(' . implode(',', $userIds) . ')',
        ]);
        foreach ($profiles as $profile) {
            $userNames[$profile['user_id']] = $profile['nome'] ?: $profile['email'];
        }
    }
} catch (Throwable $e) {
    flash('error', 'Não foi possível abrir o orçamento solicitado.');
    redirect('orcamentos.php');
}

$statusLabels = ['pendente' => 'Pendente', 'aprovado' => 'Aprovado', 'reprovado' => 'Reprovado'];
$typeLabels = ['manutencao' => 'Manutenção', 'obra' => 'Obra', 'outro' => 'Outro'];
$statusClasses = [
    'pendente' => 'bg-amber-100 text-amber-800',
    'aprovado' => 'bg-emerald-100 text-emerald-800',
    'reprovado' => 'bg-red-100 text-red-800',
];

$pageTitle = 'Detalhes do orçamento';
require __DIR__ . '/_layout_top.php';
?>

<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <a href="orcamentos.php" class="text-sm font-medium text-blue-600 hover:underline">← Orçamentos</a>
        <h2 class="mt-2 text-3xl font-bold"><?= e($budget['titulo']) ?></h2>
        <p class="mt-1 text-slate-500"><?= e($budget['fornecedor']) ?></p>
    </div>
    <span class="w-fit rounded-full px-3 py-1.5 text-sm font-bold <?= $statusClasses[$budget['status']] ?? '' ?>">
        <?= e($statusLabels[$budget['status']] ?? $budget['status']) ?>
    </span>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
    <section class="rounded-2xl bg-white p-6 shadow-sm">
        <h3 class="text-lg font-bold">Dados do orçamento</h3>
        <dl class="mt-5 grid gap-5 sm:grid-cols-2">
            <div><dt class="text-sm text-slate-500">Tipo</dt><dd class="mt-1 font-medium"><?= e($typeLabels[$budget['tipo']] ?? $budget['tipo']) ?></dd></div>
            <div><dt class="text-sm text-slate-500">Valor</dt><dd class="mt-1 text-xl font-bold text-slate-900">R$ <?= number_format((float)$budget['valor'], 2, ',', '.') ?></dd></div>
            <div><dt class="text-sm text-slate-500">Data</dt><dd class="mt-1 font-medium"><?= e(date('d/m/Y', strtotime($budget['data_orcamento']))) ?></dd></div>
            <div><dt class="text-sm text-slate-500">Validade</dt><dd class="mt-1 font-medium"><?= $budget['validade'] ? e(date('d/m/Y', strtotime($budget['validade']))) : 'Não informada' ?></dd></div>
            <div class="sm:col-span-2"><dt class="text-sm text-slate-500">Descrição</dt><dd class="mt-1 whitespace-pre-line"><?= e($budget['descricao'] ?: 'Sem descrição.') ?></dd></div>
            <div class="sm:col-span-2">
                <dt class="text-sm text-slate-500">Documento</dt>
                <dd class="mt-2">
                    <a href="orcamento_arquivo.php?id=<?= urlencode($budget['id']) ?>"
                       class="inline-flex rounded-lg bg-slate-800 px-4 py-2 font-semibold text-white hover:bg-slate-700">
                        Baixar <?= e($budget['arquivo_nome_original']) ?>
                    </a>
                    <span class="ml-2 text-xs text-slate-500"><?= number_format(((int)$budget['arquivo_tamanho']) / 1024, 1, ',', '.') ?> KB</span>
                </dd>
            </div>
        </dl>

        <?php if ($budget['observacao_decisao']): ?>
            <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm font-semibold">Observação da última decisão</p>
                <p class="mt-1 whitespace-pre-line text-sm text-slate-700"><?= e($budget['observacao_decisao']) ?></p>
            </div>
        <?php endif; ?>
    </section>

    <aside class="space-y-6">
        <?php if (isAdmin()): ?>
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <h3 class="font-bold">Decisão do síndico</h3>
                <form method="post" action="orcamento_decisao.php" class="mt-4 space-y-3">
                    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= e($budget['id']) ?>">
                    <textarea name="observacao" rows="3" maxlength="1000" placeholder="Observação da decisão"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                    <div class="grid grid-cols-2 gap-2">
                        <button name="status" value="aprovado" class="rounded-lg bg-emerald-600 px-3 py-2 font-semibold text-white hover:bg-emerald-500">Aprovar</button>
                        <button name="status" value="reprovado" class="rounded-lg bg-red-600 px-3 py-2 font-semibold text-white hover:bg-red-500">Reprovar</button>
                    </div>
                </form>
            </div>

            <div class="rounded-2xl border border-red-200 bg-red-50 p-5">
                <h3 class="font-bold text-red-800">Excluir orçamento</h3>
                <p class="mt-1 text-sm text-red-700">O documento e o histórico também serão removidos.</p>
                <form method="post" action="orcamento_excluir.php" class="mt-3" onsubmit="return confirm('Excluir este orçamento definitivamente?')">
                    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= e($budget['id']) ?>">
                    <button class="w-full rounded-lg border border-red-300 bg-white px-3 py-2 font-semibold text-red-700 hover:bg-red-100">Excluir</button>
                </form>
            </div>
        <?php endif; ?>
    </aside>
</div>

<section class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
    <h3 class="text-lg font-bold">Histórico</h3>
    <div class="mt-4 space-y-3">
        <?php foreach ($history as $entry): ?>
            <div class="rounded-xl border border-slate-200 p-4">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <p class="font-semibold">
                        <?= $entry['status_anterior'] ? e($statusLabels[$entry['status_anterior']] ?? $entry['status_anterior']) . ' → ' : '' ?>
                        <?= e($statusLabels[$entry['status_novo']] ?? $entry['status_novo']) ?>
                    </p>
                    <time class="text-xs text-slate-500"><?= e(date('d/m/Y H:i', strtotime($entry['alterado_em']))) ?></time>
                </div>
                <p class="mt-1 text-sm text-slate-600"><?= e($entry['observacao'] ?: 'Sem observação.') ?></p>
                <p class="mt-2 text-xs text-slate-400">Por <?= e($userNames[$entry['alterado_por']] ?? 'Usuário do sistema') ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
