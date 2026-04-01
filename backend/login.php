<?php
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
    $tipoLogin = $data['tipo_login'] ?? 'comum'; // Pode ser 'comum', 'professor', 'admin', 'desenvolvedor'
    
    // Busca o usuário baseado no e-mail (usamos email genérico para desenv, ou nomes... Aqui usando email/nome)
    // Para Desenvolvedor, eles logam usando NOME em vez de EMAIL na plataforma como descrito (Lorrany, Arthur, etc)
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
        // Em um sistema real, a senha é verificada por password_verify($senha, $user['senha']). 
        // Aqui estamos aceitando login fictício p/ fins de protótipo de telas ou verify se houver hash
        if (password_verify($senha, $user['senha']) || $senha == 'senha123') { 
            
            // Retorna sucessos e regras extras - ex: redirecionar admin pra trocar a senha
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Login aprovado!',
                'primeiro_acesso' => $user['primeiro_acesso'],
                'url_redirecionamento' => ($user['primeiro_acesso'] == 1) ? 'nova_senha.html' : 'mapa.html'
            ]);
            exit;

        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Senha incorreta.']);
            exit;
        }
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não encontrado com este e-mail ou nome para este tipo.']);
        exit;
    }
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido ou campos vazios.']);
}
?>
