<?php

require_once __DIR__ . '/src/bootstrap.php';

$id = trim((string)($_GET['id'] ?? ''));

try {
    if ($id === '') {
        throw new InvalidArgumentException('Documento não informado.');
    }
    $rows = $supabase->select('orcamentos', [
        'select' => 'arquivo_nome_original,arquivo_caminho,arquivo_mime',
        'id' => 'eq.' . $id,
        'limit' => 1,
    ]);
    if (!$rows) {
        throw new RuntimeException('Documento não encontrado.');
    }

    $budget = $rows[0];
    $download = $supabase->storageDownload('orcamentos', $budget['arquivo_caminho']);
    $original = str_replace(["\r", "\n"], '', basename($budget['arquivo_nome_original']));
    $fallback = preg_replace('/[^A-Za-z0-9._-]/', '_', $original) ?: 'orcamento';

    header('Content-Type: ' . $budget['arquivo_mime']);
    header('Content-Length: ' . strlen($download['body']));
    header('Content-Disposition: attachment; filename="' . $fallback . '"; filename*=UTF-8\'\'' . rawurlencode($original));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store, max-age=0');
    echo $download['body'];
    exit;
} catch (Throwable $e) {
    flash('error', 'Não foi possível baixar o documento.');
    redirect($id !== '' ? 'orcamento_detalhe.php?id=' . urlencode($id) : 'orcamentos.php');
}
