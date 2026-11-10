<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/OrcamentoArquivo.php';

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FALHA: {$message}\n");
        exit(1);
    }
}

$tempDir = sys_get_temp_dir() . '/orcamento-test-' . bin2hex(random_bytes(4));
mkdir($tempDir, 0700, true);

try {
    $pdf = $tempDir . '/proposta.pdf';
    file_put_contents($pdf, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");

    $valid = OrcamentoArquivo::validar([
        'name' => 'C:\\Documentos\\Proposta Empresa.pdf',
        'tmp_name' => $pdf,
        'size' => filesize($pdf),
        'error' => UPLOAD_ERR_OK,
    ]);

    assertTrue($valid['extensao'] === 'pdf', 'PDF deve ser aceito.');
    assertTrue($valid['nome_original'] === 'Proposta Empresa.pdf', 'Nome do navegador deve ser normalizado.');
    assertTrue($valid['mime'] === 'application/pdf', 'MIME do PDF deve ser normalizado.');
    assertTrue(str_ends_with($valid['caminho'], '.pdf'), 'Caminho deve manter somente a extensão validada.');
    assertTrue(!str_contains($valid['caminho'], 'Proposta Empresa'), 'Caminho não pode reutilizar o nome original.');

    $fake = $tempDir . '/malicioso.pdf';
    file_put_contents($fake, '<script>alert(1)</script>');
    try {
        OrcamentoArquivo::validar([
            'name' => 'malicioso.pdf',
            'tmp_name' => $fake,
            'size' => filesize($fake),
            'error' => UPLOAD_ERR_OK,
        ]);
        assertTrue(false, 'Arquivo com MIME falso deveria ser recusado.');
    } catch (InvalidArgumentException $e) {
        assertTrue(str_contains($e->getMessage(), 'formato'), 'Erro deve informar formato inválido.');
    }

    try {
        OrcamentoArquivo::validar([
            'name' => 'grande.pdf',
            'tmp_name' => $pdf,
            'size' => OrcamentoArquivo::MAX_BYTES + 1,
            'error' => UPLOAD_ERR_OK,
        ]);
        assertTrue(false, 'Arquivo acima do limite deveria ser recusado.');
    } catch (InvalidArgumentException $e) {
        assertTrue(str_contains($e->getMessage(), '15 MB'), 'Erro deve informar o limite.');
    }

    echo "OK: validação de arquivos de orçamento.\n";
} finally {
    foreach (glob($tempDir . '/*') ?: [] as $file) {
        unlink($file);
    }
    rmdir($tempDir);
}
