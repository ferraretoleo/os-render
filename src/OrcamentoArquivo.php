<?php

declare(strict_types=1);

final class OrcamentoArquivo
{
    public const MAX_BYTES = 15 * 1024 * 1024;

    private const MIME_POR_EXTENSAO = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/x-ole-storage', 'application/cdfv2'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
    ];

    private const MIME_NORMALIZADO = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];

    public static function validar(array $file): array
    {
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException(self::mensagemErroUpload($error));
        }

        $original = trim(basename(str_replace('\\', '/', (string)($file['name'] ?? ''))));
        $tmp = (string)($file['tmp_name'] ?? '');
        $size = (int)($file['size'] ?? 0);
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));

        if ($original === '' || !isset(self::MIME_POR_EXTENSAO[$extension])) {
            throw new InvalidArgumentException('Formato não permitido. Envie PDF, DOC, DOCX, JPG, JPEG ou PNG.');
        }
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new InvalidArgumentException('O arquivo deve ter no máximo 15 MB.');
        }
        if (!is_file($tmp) || !is_readable($tmp)) {
            throw new InvalidArgumentException('O arquivo enviado não pôde ser lido.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = strtolower((string)$finfo->file($tmp));
        if (!in_array($detectedMime, self::MIME_POR_EXTENSAO[$extension], true)) {
            throw new InvalidArgumentException('O conteúdo do arquivo não corresponde ao formato informado.');
        }

        return [
            'nome_original' => self::truncate($original, 255),
            'tmp_name' => $tmp,
            'tamanho' => $size,
            'extensao' => $extension,
            'mime' => self::MIME_NORMALIZADO[$extension],
            'caminho' => date('Y/m') . '/' . bin2hex(random_bytes(20)) . '.' . $extension,
        ];
    }

    private static function mensagemErroUpload(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o limite de 15 MB.',
            UPLOAD_ERR_NO_FILE => 'Selecione um arquivo para o orçamento.',
            default => 'Não foi possível receber o arquivo enviado.',
        };
    }

    private static function truncate(string $value, int $limit): string
    {
        preg_match_all('/./us', $value, $matches);
        return implode('', array_slice($matches[0] ?? [], 0, $limit));
    }
}
