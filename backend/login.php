<?php
// ============================================================
// AETHOS — login.php
// Endpoint de autenticação com redirecionamento por perfil
// Compatível com PHP 5.4.17+
// ============================================================
session_start();
header('Content-Type: application/json');
require_once 'conexao.php';
require_once 'helpers.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($data['email']) && isset($data['senha'])) {

    $email     = $data['email'];
    $senha     = $data['senha'];
    $tipoLogin = isset($data['tipo_login']) ? $data['tipo_login'] : 'comum';

    // --- Verificar rate limiting: bloqueia se mais de 5 tentativas falhas do mesmo IP em 15min ---
    $ip = obter_ip();
    $stmt_rate = $pdo->prepare(
        "SELECT COUNT(*) as tentativas FROM logs_sistema
         WHERE modulo = 'auth' AND acao = 'login_falha'
         AND ip = :ip AND criado_em > DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
    );
    $stmt_rate->bindParam(':ip', $ip);
    $stmt_rate->execute();
    $rate = $stmt_rate->fetch();

    if ($rate['tentativas'] >= 5) {
        registrar_log($pdo, 'CRITICO', 'seguranca', 'rate_limit_bloqueio',
            'IP bloqueado por ' . $rate['tentativas'] . ' tentativas falhas.', null);
        echo json_encode(array(
            'sucesso'  => false,
            'mensagem' => 'Muitas tentativas. Aguarde 15 minutos antes de tentar novamente.'
        ));
        exit;
    }

    // --- Busca o usuário ---
    if ($tipoLogin == 'desenvolvedor') {
        // Dev é autenticado por NOME, não por e-mail
        $stmt = $pdo->prepare(
            "SELECT * FROM usuarios WHERE nome = :email AND tipo_usuario = 'desenvolvedor' AND ativo = 1"
        );
    } else {
        $stmt = $pdo->prepare(
            "SELECT * FROM usuarios WHERE email = :email AND tipo_usuario = :tipo AND ativo = 1"
        );
        $stmt->bindParam(':tipo', $tipoLogin);
    }

    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha'])) {

        // --- Checagem de E-mail Verificado ---
        if ($user['email_verificado'] == 0) {
            echo json_encode(array(
                'sucesso'  => false,
                'mensagem' => 'Sua conta ainda não foi ativada. Redirecionando para verificação de e-mail...',
                'url_redirecionamento' => 'verificar-email.html?email=' . urlencode($user['email'])
            ));
            exit;
        }

        // --- Login bem-sucedido ---
        session_regenerate_id(true); // Previne Session Fixation

        $_SESSION['usuario_id']   = $user['id'];
        $_SESSION['nome']         = $user['nome'];
        $_SESSION['tipo_usuario'] = $user['tipo_usuario'];
        $_SESSION['foto_perfil']  = $user['foto_perfil'];

        // --- Primeiro acesso: Admin/Dev → troca de senha obrigatória ---
        $forcaReset = false;
        if (($user['tipo_usuario'] === 'admin' || $user['tipo_usuario'] === 'desenvolvedor')
            && $user['primeiro_acesso'] == 1) {
            $forcaReset = true;
        }

        // --- Redirecionamento por perfil (Lei 2.6 do Regimento) ---
        if ($forcaReset) {
            $destino = 'nova_senha.html';
        } elseif ($user['tipo_usuario'] === 'admin') {
            $destino = 'admin.html';
        } elseif ($user['tipo_usuario'] === 'desenvolvedor') {
            $destino = 'dev.html';
        } else {
            $destino = 'index.html';
        }

        // Registra login bem-sucedido
        registrar_log($pdo, 'INFO', 'auth', 'login_sucesso',
            'Usuário ' . $user['nome'] . ' (' . $user['tipo_usuario'] . ') fez login.',
            $user['id']);

        echo json_encode(array(
            'sucesso'             => true,
            'mensagem'            => 'Login aprovado! Redirecionando...',
            'primeiro_acesso'     => $forcaReset,
            'url_redirecionamento' => $destino
        ));
        exit;

    } else {
        // --- Login falhou ---
        registrar_log($pdo, 'AVISO', 'auth', 'login_falha',
            'Tentativa falha de login com identificador: ' . $email . ' | Perfil: ' . $tipoLogin,
            null);

        if (!$user) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Usuário não encontrado.'));
        } else {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Senha incorreta.'));
        }
        exit;
    }

} else {
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Método inválido ou campos ausentes.'));
}
?>
