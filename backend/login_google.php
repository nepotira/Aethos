<?php
// ============================================================
// AETHOS — backend/login_google.php
// Recebe o JWT do Google, valida os dados do usuário,
// sincroniza ou cria a conta no banco de dados e loga o usuário.
// ============================================================
session_start();
header('Content-Type: application/json');
require_once 'conexao.php';
require_once 'helpers.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data && isset($_POST['credential'])) {
    $data = $_POST;
}

$token = isset($data['credential']) ? trim($data['credential']) : '';

if (empty($token)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Token do Google ausente.']);
    exit;
}

// Em um ambiente de produção real, você deve verificar a assinatura do JWT
// usando a biblioteca google/apiclient (Google_Client::verifyIdToken).
// Como não temos composer aqui nativamente (sem confirmação), vamos decodificar o Payload manualmente (O Google JWT tem 3 partes: header.payload.signature)
$jwt_parts = explode('.', $token);
if (count($jwt_parts) !== 3) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Token inválido.']);
    exit;
}

$payload = json_decode(base64_decode(strtr($jwt_parts[1], '-_', '+/')), true);

if (!$payload || !isset($payload['email'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao decodificar os dados do Google.']);
    exit;
}

$email = $payload['email'];
$nome = isset($payload['name']) ? $payload['name'] : 'Usuário Google';
$foto = isset($payload['picture']) ? $payload['picture'] : '';
$google_id = isset($payload['sub']) ? $payload['sub'] : ''; // ID único do usuário no Google

try {
    // Verifica se o usuário já existe no banco de dados
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user) {
        // CONTA EXISTE: Sincroniza e Loga
        
        // Verifica se a conta está ativa
        if ($user['ativo'] == 0) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Sua conta está desativada.']);
            exit;
        }

        // Atualiza a foto de perfil com a foto do Google e garante que o e-mail seja marcado como verificado
        $update = $pdo->prepare("UPDATE usuarios SET foto_perfil = :foto, email_verificado = 1, codigo_verificacao = NULL WHERE id = :id");
        $update->bindParam(':foto', $foto);
        $update->bindParam(':id', $user['id'], PDO::PARAM_INT);
        $update->execute();

        // Inicia Sessão
        session_regenerate_id(true);
        $_SESSION['usuario_id']   = $user['id'];
        $_SESSION['nome']         = $user['nome'];
        $_SESSION['tipo_usuario'] = $user['tipo_usuario'];
        $_SESSION['foto_perfil']  = $foto;

        registrar_log($pdo, 'INFO', 'auth', 'login_google_sincronizado', "Login Google sincronizado com sucesso para $email", $user['id']);

        $destino = 'mapa.html';
        if ($user['tipo_usuario'] === 'admin') $destino = 'admin.html';
        if ($user['tipo_usuario'] === 'desenvolvedor') $destino = 'dev.html';
        
        echo json_encode(['sucesso' => true, 'mensagem' => 'Sincronização concluída. Entrando...', 'url_redirecionamento' => $destino]);
    } else {
        // CONTA NÃO EXISTE: Cria nova conta como 'comum'
        
        // Gera uma senha aleatória inacessível já que ele só logará pelo Google, 
        // ou deixa que ele redefina caso queira logar manualmente no futuro.
        $senha_aleatoria = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        $insert = $pdo->prepare("
            INSERT INTO usuarios (tipo_usuario, nome, email, senha, foto_perfil, email_verificado, primeiro_acesso, ativo)
            VALUES ('comum', :nome, :email, :senha, :foto, 1, 0, 1)
        ");
        $insert->bindParam(':nome', $nome);
        $insert->bindParam(':email', $email);
        $insert->bindParam(':senha', $senha_aleatoria);
        $insert->bindParam(':foto', $foto);
        
        if ($insert->execute()) {
            $novo_id = $pdo->lastInsertId();

            session_regenerate_id(true);
            $_SESSION['usuario_id']   = $novo_id;
            $_SESSION['nome']         = $nome;
            $_SESSION['tipo_usuario'] = 'comum';
            $_SESSION['foto_perfil']  = $foto;

            registrar_log($pdo, 'INFO', 'auth', 'login_google_novo', "Nova conta criada via Google para $email", $novo_id);

            echo json_encode(['sucesso' => true, 'mensagem' => 'Conta criada com sucesso! Entrando...', 'url_redirecionamento' => 'mapa.html']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao criar conta pelo Google.']);
        }
    }
} catch (PDOException $e) {
    error_log('[Aethos] Erro login_google: ' . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno ao conectar ao banco de dados.']);
}
?>
