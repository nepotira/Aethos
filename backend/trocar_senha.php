<?php
session_start(); // Retoma a sessão que o login.php criou
header('Content-Type: application/json');
require_once 'conexao.php';

// Segurança: O cara tem que ter logado antes para chegar aqui
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso Negado! Favor realizar o login primeiro.']);
    exit;
}

$id_logado = $_SESSION['usuario_id'];
$data = json_decode(file_get_contents('php://input'), true);
$nova_senha = isset($data['nova_senha']) ? $data['nova_senha'] : '';

if (strlen($nova_senha) >= 5) {
    try {
        // Criptografar a nova senha de forma REAL usando Bcrypt
        $senha_criptografada = password_hash($nova_senha, PASSWORD_DEFAULT);

        // Atualizar no banco: Alterar a senha, e Remover a trava de primeiro acesso
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = :senha, primeiro_acesso = 0 WHERE id = :id");
        $stmt->bindParam(':senha', $senha_criptografada);
        $stmt->bindParam(':id', $id_logado);
        
        if ($stmt->execute()) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Sua senha foi redefinida com sucesso! Redirecionando...']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Houve um erro técnico interno ao atualizar sua senha.']);
        }

    } catch (PDOException $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha no banco de dados. Contate os desenvolvedores.']);
    }

} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'A senha deve ter pelo menos 5 digitos.']);
}
?>
