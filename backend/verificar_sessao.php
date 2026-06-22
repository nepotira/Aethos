<?php
session_start();
header('Content-Type: application/json');

// Endpoint para verificar se há uma sessão PHP ativa.
// Retorna dados do usuário logado para adaptar o header/navbar.

echo json_encode([
    'autenticado'  => isset($_SESSION['usuario_id']),
    'tipo_usuario' => isset($_SESSION['tipo_usuario']) ? $_SESSION['tipo_usuario'] : null,
    'nome'         => isset($_SESSION['nome']) ? $_SESSION['nome'] : null,
    'id'           => isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null,
    'foto_perfil'  => isset($_SESSION['foto_perfil']) ? $_SESSION['foto_perfil'] : null
]);
?>
