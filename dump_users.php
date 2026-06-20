<?php
require_once 'backend/conexao.php';
try {
    $stmt = $pdo->query("SELECT id, nome, email, tipo_usuario, cpf FROM usuarios");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($users)) {
        echo "No users found in database.\n";
    } else {
        foreach ($users as $u) {
            echo "ID: {$u['id']} | Nome: {$u['nome']} | Email: {$u['email']} | Tipo: {$u['tipo_usuario']} | CPF: {$u['cpf']}\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
