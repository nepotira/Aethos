<?php
// ============================================================
// AETHOS — verificar_email.php
// Endpoint para validar o código de e-mail e ativar a conta
// ============================================================
session_start();
header('Content-Type: application/json');
require_once 'conexao.php';
require_once 'helpers.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($data['email']) && isset($data['codigo'])) {
    
    $email  = trim($data['email']);
    $codigo = trim($data['codigo']);

    if (empty($email) || empty($codigo)) {
        echo json_encode(array('sucesso' => false, 'mensagem' => 'Preencha o código corretamente.'));
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Usuário não encontrado.'));
            exit;
        }

        if ($user['email_verificado'] == 1) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Este e-mail já foi verificado. Faça login.'));
            exit;
        }

        if ($user['codigo_verificacao'] !== $codigo) {
            // Código incorreto
            registrar_log($pdo, 'AVISO', 'auth', 'falha_verificacao_email', 'Código incorreto digitado para ' . $email, $user['id']);
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Código de verificação incorreto.'));
            exit;
        }

        // --- SUCESSO: Ativa o E-mail ---
        $update = $pdo->prepare("UPDATE usuarios SET email_verificado = 1, codigo_verificacao = NULL WHERE id = :id");
        $update->bindParam(':id', $user['id'], PDO::PARAM_INT);
        $update->execute();

        // --- Loga o usuário automaticamente ---
        session_regenerate_id(true);
        $_SESSION['usuario_id']   = $user['id'];
        $_SESSION['nome']         = $user['nome'];
        $_SESSION['tipo_usuario'] = $user['tipo_usuario'];
        $_SESSION['foto_perfil']  = $user['foto_perfil'];

        registrar_log($pdo, 'INFO', 'auth', 'email_verificado', 'E-mail ativado com sucesso: ' . $email, $user['id']);

        // --- Redirecionamento por perfil (Lei 2.6) ---
        $forcaReset = false;
        if (($user['tipo_usuario'] === 'admin' || $user['tipo_usuario'] === 'desenvolvedor')
            && $user['primeiro_acesso'] == 1) {
            $forcaReset = true;
        }

        if ($forcaReset) {
            $destino = 'nova_senha.html';
        } elseif ($user['tipo_usuario'] === 'admin') {
            $destino = 'admin.html';
        } elseif ($user['tipo_usuario'] === 'desenvolvedor') {
            $destino = 'dev.html';
        } else {
            $destino = 'index.html';
        }

        echo json_encode(array(
            'sucesso' => true,
            'mensagem' => 'Conta ativada com sucesso! Entrando...',
            'url_redirecionamento' => $destino
        ));

    } catch (PDOException $e) {
        error_log('[Aethos] Erro em verificar_email.php: ' . $e->getMessage());
        echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro interno de servidor.'));
    }

} else {
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Parâmetros inválidos.'));
}
?>
