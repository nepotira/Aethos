<?php
session_start(); // Inicia o gerenciador de sessões do PHP (obrigatório para manter usuários logados)
header('Content-Type: application/json');
require_once 'conexao.php';

// Pegando os dados vindos do post ou JSON de Fetch/AJAX
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    // Caso vier dados pelo jquery $.post nativo 
    $data = $_POST;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($data['email']) && isset($data['senha'])) {
    
    $email = $data['email'];
    $senha = $data['senha'];
    $tipoLogin = isset($data['tipo_login']) ? $data['tipo_login'] : 'comum'; 
    
    if ($tipoLogin == 'desenvolvedor') {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nome = :email AND tipo_usuario = 'desenvolvedor'");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email AND tipo_usuario = :tipo");
        $stmt->bindParam(':tipo', $tipoLogin);
    }
    
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    $user = $stmt->fetch();
    
    if ($user) {
        // Verifica a senha 
        if (password_verify($senha, $user['senha'])) {
            
            // ============================================
            // Início Seguro de Sessão! Guarda "quem" logou
            // ============================================
            // Regenera o ID de sessão para prevenir Session Fixation Attack
            session_regenerate_id(true);

            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nome'] = $user['nome'];
            $_SESSION['tipo_usuario'] = $user['tipo_usuario'];

            // Correção do Bug do Atleta (Módulo 2 da solicitação):
            // Só forçaremos 'primeiro_acesso = 1' se ele REALMENTE FOR admin ou desenvolvedor.
            $forcaReset = false;
            if (($user['tipo_usuario'] === 'admin' || $user['tipo_usuario'] === 'desenvolvedor') && $user['primeiro_acesso'] == 1) {
                $forcaReset = true;
            }

            // Retorna sucesso
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Login aprovado!',
                'primeiro_acesso' => $forcaReset,
                'url_redirecionamento' => ($forcaReset) ? 'nova_senha.html' : 'index.html'
            ]);
            exit;

        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Senha incorreta.']);
            exit;
        }
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não encontrado.']);
        exit;
    }
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido ou campos vazios.']);
}
?>
