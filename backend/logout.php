<?php
// ============================================================
// AETHOS — logout.php
// Destroi a sessão do usuário e retorna JSON de confirmação
// ============================================================
session_start();
header('Content-Type: application/json');
require_once 'conexao.php';
require_once 'helpers.php';

$usuario_id   = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;
$nome_usuario = isset($_SESSION['nome'])       ? $_SESSION['nome']       : 'Desconhecido';

// Registra o logout nos logs antes de destruir a sessão
if ($usuario_id) {
    registrar_log(
        $pdo,
        'INFO',
        'auth',
        'logout',
        'Usuário ' . $nome_usuario . ' realizou logout.',
        $usuario_id
    );
}

// Destrói todos os dados da sessão
$_SESSION = array();

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

session_destroy();

echo json_encode(array('sucesso' => true, 'mensagem' => 'Logout realizado com sucesso.'));
?>
