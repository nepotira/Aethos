<?php
session_start();
header('Content-Type: application/json');

// Endpoint simples para verificar se há uma sessão PHP ativa.
// Usado por nova_senha.html (PATCH BUG-09) para redirecionar
// o usuário para login.html se acessar a página sem autenticação.

echo json_encode([
    'autenticado' => isset($_SESSION['usuario_id'])
]);
?>
