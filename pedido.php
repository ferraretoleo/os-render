<?php

require_once __DIR__ . '/src/bootstrap.php';

$materials = $supabase->select('materiais', [
    'select' => 'id,nome,unidade_medida,quantidade_atual,quantidade_minima',
    'order' => 'nome.asc',
]);

$popupMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    try {
        $materialId = postString('material_id', true);
        $requestedQty = postFloat('quantidade_solicitada', true);
        $requestedBy = postString('solicitante', true);

        if ($requestedQty <= 0) {
            throw new InvalidArgumentException('A quantidade solicitada deve ser maior que zero.');
        }

        $rows = $supabase->select('materiais', [
            'select' => '*',
            'id' => 'eq.' . $materialId,
            'limit' => 1,
        ]);

        if (!$rows) {
            throw new RuntimeException('Material não encontrado.');
        }

        $material = $rows[0];

        $supabase->insert('solicitacoes_compra', [
            'material_id' => $materialId,
            'quantidade_solicitada' => $requestedQty,
            'quantidade_estoque_no_momento' => (float)$material['quantidade_atual'],
            'solicitante' => $requestedBy,
            'solicitado_por' => $_SESSION['user']['id'] ?? null,
            'status' => 'solicitado',
        ]);

        $dataHora = date('d/m/Y H:i');
        $estoqueAtual = rtrim(rtrim(number_format((float)$material['quantidade_atual'], 3, ',', '.'), '0'), ',');
        $qtdSolicitada = rtrim(rtrim(number_format($requestedQty, 3, ',', '.'), '0'), ',');
        $unidade = (string)$material['unidade_medida'];

        $popupMessage =
            "SOLICITAÇÃO DE COMPRA\n\n" .
            "Residencial Vivere Palhano\n\n" .
            "Produto: {$material['nome']}\n" .
            "Estoque atual: {$estoqueAtual} {$unidade}\n" .
            "Quantidade solicitada: {$qtdSolicitada} {$unidade}\n" .
            "Solicitante: {$requestedBy}\n" .
            "Data/Hora: {$dataHora}\n\n" .
            "Solicitação registrada no Controle de Estoque.";

        flash('success', 'Solicitação registrada com sucesso.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
}

$pageTitle = 'Solicitar compra';
require __DIR__ . '/_layout_top.php';
?>

<div class="mx-auto max-w-3xl rounded-2xl bg-white p-6 shadow-sm">
    <h2 class="text-2xl font-bold">Solicitação de compra</h2>
    <p class="mt-1 text-sm text-slate-500">
        Após registrar, o sistema abrirá uma mensagem pronta para copiar e enviar pelo WhatsApp.
    </p>

    <form method="post" class="mt-6 grid gap-4">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

        <label>
            <span class="mb-1 block text-sm font-medium">Produto</span>
            <select id="material_id" name="material_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <option value="">Selecione</option>
                <?php foreach ($materials as $m): ?>
                    <option
                        value="<?= e($m['id']) ?>"
                        data-current="<?= e((string)$m['quantidade_atual']) ?>"
                        data-unit="<?= e($m['unidade_medida']) ?>"
                    >
                        <?= e($m['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div id="saldoBox" class="hidden rounded-xl bg-slate-50 p-4 text-sm">
            Saldo atual: <strong id="saldoAtual"></strong>
        </div>

        <label>
            <span class="mb-1 block text-sm font-medium">Quantidade solicitada</span>
            <input name="quantidade_solicitada" required type="number" min="0.001" step="0.001"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>

        <label>
            <span class="mb-1 block text-sm font-medium">Solicitante</span>
            <input name="solicitante" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>

        <button class="rounded-lg bg-emerald-600 px-5 py-2.5 font-semibold text-white hover:bg-emerald-500">
            Registrar solicitação
        </button>
    </form>
</div>

<?php if ($popupMessage !== ''): ?>
<div id="whatsappModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="text-xl font-bold">Solicitação registrada</h3>
                <p class="mt-1 text-sm text-slate-500">Copie a mensagem abaixo e envie pelo WhatsApp.</p>
            </div>
            <button type="button" onclick="fecharModal()" class="text-2xl leading-none text-slate-400 hover:text-slate-700">&times;</button>
        </div>

        <textarea id="textoWhatsapp" readonly rows="11"
                  class="mt-5 w-full rounded-xl border border-slate-300 bg-slate-50 p-4 text-sm"><?= e($popupMessage) ?></textarea>

        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
            <button type="button" onclick="copiarMensagem()"
                    class="flex-1 rounded-lg bg-emerald-600 px-4 py-2.5 font-semibold text-white hover:bg-emerald-500">
                Copiar mensagem
            </button>

            <button type="button" onclick="abrirWhatsApp()"
                    class="flex-1 rounded-lg bg-green-700 px-4 py-2.5 font-semibold text-white hover:bg-green-600">
                Abrir WhatsApp
            </button>

            <?php if (isAdmin()): ?>
                <a href="relatorio_pedidos.php"
                   class="flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-center font-semibold hover:bg-slate-50">
                    Ir para relatório
                </a>
            <?php else: ?>
                <button type="button" onclick="fecharModal()"
                        class="flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-center font-semibold hover:bg-slate-50">
                    Fechar
                </button>
            <?php endif; ?>
        </div>

        <p id="copyFeedback" class="mt-3 hidden text-sm font-medium text-emerald-700">
            Mensagem copiada.
        </p>
    </div>
</div>
<?php endif; ?>

<script>
const select = document.getElementById('material_id');
const box = document.getElementById('saldoBox');
const saldo = document.getElementById('saldoAtual');

select.addEventListener('change', () => {
    const option = select.options[select.selectedIndex];

    if (!option.value) {
        box.classList.add('hidden');
        saldo.textContent = '';
        return;
    }

    saldo.textContent = `${option.dataset.current} ${option.dataset.unit}`;
    box.classList.remove('hidden');
});

function fecharModal() {
    const modal = document.getElementById('whatsappModal');
    if (modal) modal.remove();
}

async function copiarMensagem() {
    const textarea = document.getElementById('textoWhatsapp');
    const feedback = document.getElementById('copyFeedback');

    try {
        await navigator.clipboard.writeText(textarea.value);
        feedback.classList.remove('hidden');
    } catch (e) {
        textarea.select();
        document.execCommand('copy');
        feedback.classList.remove('hidden');
    }
}

function abrirWhatsApp() {
    const texto = document.getElementById('textoWhatsapp').value;
    const url = 'https://wa.me/?text=' + encodeURIComponent(texto);
    window.open(url, '_blank');
}
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
