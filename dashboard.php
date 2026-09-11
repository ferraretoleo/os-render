<?php

require_once __DIR__ . '/src/bootstrap.php';

requireAdmin();

$pageTitle = 'Dashboard';

$category = trim((string)($_GET['categoria'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));

$query = [
    'select' => 'id,nome,categoria,unidade_medida,quantidade_atual,quantidade_minima,localizacao',
    'order' => 'nome.asc',
];

if ($category !== '') {
    $query['categoria'] = 'eq.' . $category;
}

$materials = $supabase->select('materiais', $query);

if ($status !== '') {
    $materials = array_values(array_filter($materials, static function (array $m) use ($status): bool {
        return materialStatus((float)$m['quantidade_atual'], (float)$m['quantidade_minima']) === $status;
    }));
}

$allCategories = $supabase->select('materiais', [
    'select' => 'categoria',
    'order' => 'categoria.asc',
]);

$categories = [];
foreach ($allCategories as $row) {
    if (!empty($row['categoria'])) {
        $categories[$row['categoria']] = true;
    }
}
$categories = array_keys($categories);

$totalItems = count($materials);
$critical = array_values(array_filter($materials, fn($m) => (float)$m['quantidade_atual'] <= (float)$m['quantidade_minima']));

$pendingRequests = $supabase->select('solicitacoes_compra', [
    'select' => 'id,quantidade_solicitada,solicitante,data_solicitacao,status,materiais(nome,unidade_medida)',
    'status' => 'eq.solicitado',
    'order' => 'data_solicitacao.desc',
    'limit' => 5,
]);

$pendingCountRows = $supabase->select('solicitacoes_compra', [
    'select' => 'id',
    'status' => 'eq.solicitado',
]);

$pendingCount = count($pendingCountRows);

require __DIR__ . '/_layout_top.php';
?>


<div class="grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
    <aside>
        <div class="sticky top-4 rounded-2xl bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-slate-500">Solicitações de Compra</p>
                    <h2 class="mt-1 text-xl font-bold">Pendentes</h2>
                </div>

                <?php if ($pendingCount > 0): ?>
                    <span class="flex h-9 min-w-9 items-center justify-center rounded-full bg-red-100 px-2 text-sm font-bold text-red-700">
                        <?= $pendingCount ?>
                    </span>
                <?php else: ?>
                    <span class="flex h-9 min-w-9 items-center justify-center rounded-full bg-emerald-100 px-2 text-sm font-bold text-emerald-700">
                        0
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($pendingCount > 0): ?>
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3">
                    <p class="text-sm font-semibold text-amber-800">
                        Há <?= $pendingCount ?> <?= $pendingCount === 1 ? 'nova solicitação' : 'novas solicitações' ?> aguardando análise.
                    </p>
                </div>

                <div class="mt-4 space-y-3">
                    <?php foreach ($pendingRequests as $request): ?>
                        <?php
                            $requestMaterial = $request['materiais'] ?? [];
                            $requestUnit = $requestMaterial['unidade_medida'] ?? '';
                        ?>
                        <a href="relatorio_pedidos.php?status=solicitado"
                           class="block rounded-xl border border-slate-200 p-3 transition hover:border-blue-300 hover:bg-blue-50">
                            <div class="font-semibold text-slate-900">
                                <?= e($requestMaterial['nome'] ?? 'Material') ?>
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                <?= e((string)$request['quantidade_solicitada']) ?> <?= e($requestUnit) ?>
                                • <?= e($request['solicitante']) ?>
                            </div>
                            <div class="mt-1 text-xs text-slate-400">
                                <?= e(date('d/m/Y H:i', strtotime($request['data_solicitacao']))) ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <a href="relatorio_pedidos.php?status=solicitado"
                   class="mt-5 block w-full rounded-lg bg-blue-600 px-4 py-2.5 text-center font-semibold text-white hover:bg-blue-500">
                    Analisar solicitações
                </a>
            <?php else: ?>
                <div class="mt-5 rounded-xl bg-slate-50 p-4 text-center">
                    <p class="text-sm font-medium text-slate-600">Nenhuma solicitação pendente.</p>
                </div>

                <a href="relatorio_pedidos.php"
                   class="mt-4 block w-full rounded-lg border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold hover:bg-slate-50">
                    Ver histórico de compras
                </a>
            <?php endif; ?>
        </div>
    </aside>

    <section class="min-w-0">
<div class="grid gap-4 md:grid-cols-3">
    <div class="rounded-2xl bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Materiais listados</p>
        <p class="mt-2 text-3xl font-bold"><?= $totalItems ?></p>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Estoque crítico</p>
        <p class="mt-2 text-3xl font-bold text-red-600"><?= count($critical) ?></p>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Status</p>
        <p class="mt-2 text-lg font-semibold"><?= count($critical) ? 'Requer atenção' : 'Estoque saudável' ?></p>
    </div>
</div>

<?php if ($critical): ?>
<div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-5">
    <h2 class="text-lg font-bold text-red-800">Atenção: itens em estoque crítico</h2>
    <div class="mt-3 flex flex-wrap gap-2">
        <?php foreach ($critical as $item): ?>
            <span class="rounded-full bg-white px-3 py-1 text-sm text-red-700 shadow-sm">
                <?= e($item['nome']) ?>: <?= e((string)$item['quantidade_atual']) ?> <?= e($item['unidade_medida']) ?>
            </span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<form method="get" class="mt-6 grid gap-3 rounded-2xl bg-white p-4 shadow-sm md:grid-cols-4">
    <select name="categoria" class="rounded-lg border border-slate-300 px-3 py-2">
        <option value="">Todas as categorias</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
        <?php endforeach; ?>
    </select>

    <select name="status" class="rounded-lg border border-slate-300 px-3 py-2">
        <option value="">Todos os status</option>
        <option value="normal" <?= $status === 'normal' ? 'selected' : '' ?>>Normal</option>
        <option value="critico" <?= $status === 'critico' ? 'selected' : '' ?>>Crítico</option>
    </select>

    <button class="rounded-lg bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-500">Filtrar</button>
    <a href="dashboard.php" class="rounded-lg border border-slate-300 px-4 py-2 text-center hover:bg-slate-50">Limpar</a>
</form>

<div class="mt-6 overflow-x-auto rounded-2xl bg-white shadow-sm">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-600">
        <tr>
            <th class="px-4 py-3">Material</th>
            <th class="px-4 py-3">Categoria</th>
            <th class="px-4 py-3">Saldo</th>
            <th class="px-4 py-3">Mínimo</th>
            <th class="px-4 py-3">Localização</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Ações</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        <?php foreach ($materials as $m): ?>
            <?php $itemStatus = materialStatus((float)$m['quantidade_atual'], (float)$m['quantidade_minima']); ?>
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-3 font-medium"><?= e($m['nome']) ?></td>
                <td class="px-4 py-3"><?= e($m['categoria']) ?></td>
                <td class="px-4 py-3"><?= e((string)$m['quantidade_atual']) ?> <?= e($m['unidade_medida']) ?></td>
                <td class="px-4 py-3"><?= e((string)$m['quantidade_minima']) ?></td>
                <td class="px-4 py-3"><?= e($m['localizacao']) ?></td>
                <td class="px-4 py-3">
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= $itemStatus === 'critico' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' ?>">
                        <?= $itemStatus === 'critico' ? 'Crítico' : 'Normal' ?>
                    </span>
                </td>
                <td class="px-4 py-3">
                    <a href="material_form.php?id=<?= urlencode($m['id']) ?>" class="text-blue-600 hover:underline">Editar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$materials): ?>
            <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Nenhum material encontrado.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>


    </section>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
