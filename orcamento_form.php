<?php

require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/OrcamentoArquivo.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
    $uploadedPath = null;

    try {
        $titulo = postString('titulo', true);
        $descricao = postString('descricao');
        $tipo = postString('tipo', true);
        $fornecedor = postString('fornecedor', true);
        $valor = postFloat('valor', true);
        $dataOrcamento = postString('data_orcamento', true);
        $validade = postString('validade');

        if (textLength($titulo) < 3 || textLength($titulo) > 150) {
            throw new InvalidArgumentException('O título deve ter entre 3 e 150 caracteres.');
        }
        if (!in_array($tipo, ['manutencao', 'obra', 'outro'], true)) {
            throw new InvalidArgumentException('Tipo de orçamento inválido.');
        }
        if (textLength($fornecedor) < 2 || textLength($fornecedor) > 150) {
            throw new InvalidArgumentException('Informe um fornecedor válido.');
        }
        if ($valor <= 0) {
            throw new InvalidArgumentException('O valor deve ser maior que zero.');
        }

        $budgetDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dataOrcamento);
        if (!$budgetDate || $budgetDate->format('Y-m-d') !== $dataOrcamento) {
            throw new InvalidArgumentException('Data do orçamento inválida.');
        }
        if ($validade !== '') {
            $validityDate = DateTimeImmutable::createFromFormat('!Y-m-d', $validade);
            if (!$validityDate || $validityDate->format('Y-m-d') !== $validade || $validityDate < $budgetDate) {
                throw new InvalidArgumentException('A validade deve ser igual ou posterior à data do orçamento.');
            }
        }

        $file = OrcamentoArquivo::validar($_FILES['arquivo'] ?? []);
        $bytes = file_get_contents($file['tmp_name']);
        if ($bytes === false) {
            throw new RuntimeException('Não foi possível ler o documento enviado.');
        }

        $supabase->storageUpload('orcamentos', $file['caminho'], $bytes, $file['mime']);
        $uploadedPath = $file['caminho'];

        $rows = $supabase->insert('orcamentos', [
            'titulo' => $titulo,
            'descricao' => $descricao !== '' ? $descricao : null,
            'tipo' => $tipo,
            'fornecedor' => $fornecedor,
            'valor' => $valor,
            'data_orcamento' => $dataOrcamento,
            'validade' => $validade !== '' ? $validade : null,
            'arquivo_nome_original' => $file['nome_original'],
            'arquivo_caminho' => $file['caminho'],
            'arquivo_mime' => $file['mime'],
            'arquivo_tamanho' => $file['tamanho'],
            'status' => 'pendente',
            'criado_por' => $_SESSION['user']['id'],
        ]);

        $id = $rows[0]['id'] ?? null;
        flash('success', 'Orçamento cadastrado e enviado para aprovação.');
        redirect($id ? 'orcamento_detalhe.php?id=' . urlencode($id) : 'orcamentos.php');
    } catch (InvalidArgumentException $e) {
        flash('error', $e->getMessage());
    } catch (Throwable $e) {
        if ($uploadedPath !== null) {
            try {
                $supabase->storageDelete('orcamentos', $uploadedPath);
            } catch (Throwable $ignored) {
            }
        }
        flash('error', 'Não foi possível cadastrar o orçamento. Tente novamente.');
    }
}

$pageTitle = 'Novo orçamento';
require __DIR__ . '/_layout_top.php';
?>

<div class="mx-auto max-w-4xl rounded-2xl bg-white p-6 shadow-sm">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold">Novo orçamento</h2>
            <p class="mt-1 text-sm text-slate-500">Cadastre a proposta e anexe o documento recebido.</p>
        </div>
        <a href="orcamentos.php" class="text-sm font-medium text-blue-600 hover:underline">Voltar</a>
    </div>

    <form method="post" enctype="multipart/form-data" class="mt-6 grid gap-4 md:grid-cols-2">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="MAX_FILE_SIZE" value="15728640">

        <label class="md:col-span-2">
            <span class="mb-1 block text-sm font-medium">Título</span>
            <input name="titulo" required maxlength="150" value="<?= e($_POST['titulo'] ?? '') ?>"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Ex.: Pintura da fachada">
        </label>
        <label>
            <span class="mb-1 block text-sm font-medium">Tipo</span>
            <select name="tipo" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <option value="manutencao">Manutenção</option>
                <option value="obra" <?= ($_POST['tipo'] ?? '') === 'obra' ? 'selected' : '' ?>>Obra</option>
                <option value="outro" <?= ($_POST['tipo'] ?? '') === 'outro' ? 'selected' : '' ?>>Outro</option>
            </select>
        </label>
        <label>
            <span class="mb-1 block text-sm font-medium">Fornecedor</span>
            <input name="fornecedor" required maxlength="150" value="<?= e($_POST['fornecedor'] ?? '') ?>"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <label>
            <span class="mb-1 block text-sm font-medium">Valor</span>
            <input name="valor" required type="number" min="0.01" step="0.01" value="<?= e($_POST['valor'] ?? '') ?>"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <label>
            <span class="mb-1 block text-sm font-medium">Data do orçamento</span>
            <input name="data_orcamento" required type="date" value="<?= e($_POST['data_orcamento'] ?? date('Y-m-d')) ?>"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <label>
            <span class="mb-1 block text-sm font-medium">Validade, opcional</span>
            <input name="validade" type="date" value="<?= e($_POST['validade'] ?? '') ?>"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <label>
            <span class="mb-1 block text-sm font-medium">Documento</span>
            <input name="arquivo" required type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                   class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
            <span class="mt-1 block text-xs text-slate-500">PDF, DOC, DOCX, JPG ou PNG, até 15 MB.</span>
        </label>
        <label class="md:col-span-2">
            <span class="mb-1 block text-sm font-medium">Descrição, opcional</span>
            <textarea name="descricao" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2"><?= e($_POST['descricao'] ?? '') ?></textarea>
        </label>

        <button class="md:col-span-2 rounded-lg bg-blue-600 px-5 py-2.5 font-semibold text-white hover:bg-blue-500">
            Salvar orçamento
        </button>
    </form>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
