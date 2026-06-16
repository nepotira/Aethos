<?php
// ============================================================
// AETHOS — upload.php
// Upload seguro de imagens (JPEG/PNG/WebP, máx 2MB)
// Valida MIME real via finfo_file()
// ============================================================
session_start();
header('Content-Type: application/json');
require_once 'helpers.php';
require_once 'config.php';

exigir_sessao(); // Qualquer usuário logado pode fazer upload

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['foto'])) {
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Nenhum arquivo enviado.'));
    exit;
}

$arquivo = $_FILES['foto'];
$tipo    = isset($_POST['tipo']) ? $_POST['tipo'] : 'perfil'; // perfil | local

// --- Define o diretório de destino ---
if ($tipo === 'local') {
    $dir = UPLOAD_DIR_LOCAIS;
    $dir_relativo = 'uploads/locais/';
} else {
    $dir = UPLOAD_DIR_PERFIS;
    $dir_relativo = 'uploads/perfis/';
}

// --- Cria o diretório se não existir ---
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

// --- Validação: erro de upload ---
if ($arquivo['error'] !== UPLOAD_ERR_OK) {
    $erros = array(
        UPLOAD_ERR_INI_SIZE   => 'Arquivo excede o limite do servidor.',
        UPLOAD_ERR_FORM_SIZE  => 'Arquivo excede o limite do formulário.',
        UPLOAD_ERR_PARTIAL    => 'Upload parcial. Tente novamente.',
        UPLOAD_ERR_NO_FILE    => 'Nenhum arquivo selecionado.',
        UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária não encontrada.',
        UPLOAD_ERR_CANT_WRITE => 'Erro ao gravar no disco.',
    );
    $msg = isset($erros[$arquivo['error']]) ? $erros[$arquivo['error']] : 'Erro desconhecido.';
    echo json_encode(array('sucesso' => false, 'mensagem' => $msg));
    exit;
}

// --- Validação: tamanho máximo (2MB) ---
if ($arquivo['size'] > UPLOAD_MAX_SIZE) {
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Arquivo muito grande. Máximo permitido: 2MB.'));
    exit;
}

// --- Validação: MIME type REAL (não apenas extensão) ---
$mime_permitidos = array('image/jpeg', 'image/png', 'image/webp');
$ext_por_mime    = array(
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
);

if (function_exists('finfo_open')) {
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mime_real = finfo_file($finfo, $arquivo['tmp_name']);
    finfo_close($finfo);
} else {
    // Fallback para PHP 5.4 que pode não ter finfo
    $mime_real = mime_content_type($arquivo['tmp_name']);
}

if (!in_array($mime_real, $mime_permitidos)) {
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Formato não suportado. Use JPEG, PNG ou WebP.'));
    exit;
}

$extensao   = $ext_por_mime[$mime_real];
$nome_unico = uniqid('foto_', true) . '.' . $extensao;
$caminho    = $dir . $nome_unico;

if (!move_uploaded_file($arquivo['tmp_name'], $caminho)) {
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Falha ao salvar o arquivo. Verifique permissões da pasta.'));
    exit;
}

echo json_encode(array(
    'sucesso'  => true,
    'caminho'  => $dir_relativo . $nome_unico,
    'mensagem' => 'Foto enviada com sucesso!'
));
?>
