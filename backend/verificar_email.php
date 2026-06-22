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

$acao = isset($_GET['acao']) ? $_GET['acao'] : (isset($data['acao']) ? $data['acao'] : 'verificar');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $acao === 'reenviar') {
    $email = isset($data['email']) ? trim($data['email']) : '';
    if (empty($email)) {
        echo json_encode(array('sucesso' => false, 'mensagem' => 'E-mail não informado.'));
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
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Este e-mail já foi verificado.'));
            exit;
        }

        // Gera novo código de 6 dígitos
        $novo_codigo = sprintf("%06d", mt_rand(1, 999999));
        $update = $pdo->prepare("UPDATE usuarios SET codigo_verificacao = :codigo WHERE id = :id");
        $update->bindParam(':codigo', $novo_codigo);
        $update->bindParam(':id', $user['id'], PDO::PARAM_INT);
        $update->execute();

        registrar_log($pdo, 'INFO', 'auth', 'codigo_reenviado', 'Novo código de verificação solicitado para ' . $email, $user['id']);

        // --- Tenta enviar o e-mail ---
        $enviado = false;
        $nome = $user['nome'];
        if (file_exists(__DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
            require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
            require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
            require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = defined('SMTP_USER') ? SMTP_USER : 'teste@gmail.com';
                $mail->Password   = defined('SMTP_PASS') ? SMTP_PASS : 'senha';
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom($mail->Username, 'Aethos');
                $mail->addAddress($email, $nome);
                $mail->isHTML(true);
                $mail->Subject = 'Aethos — Seu Novo Código de Verificação';
                $mail->Body    = '
                    <div style="font-family:Inter,sans-serif; background:#0D0F32; color:#fff; padding:2rem; border-radius:12px;">
                        <h2 style="color:#A5B4FC;">Olá, ' . htmlspecialchars($nome) . '!</h2>
                        <p>Você solicitou um novo código. Insira o código de 6 dígitos abaixo na tela de verificação:</p>
                        <div style="font-size:2rem; font-weight:800; letter-spacing:0.5rem; color:#818CF8; margin:1.5rem 0; text-align:center; background:rgba(255,255,255,0.05); padding:1rem; border-radius:12px;">
                            ' . $novo_codigo . '
                        </div>
                    </div>';
                $mail->send();
                $enviado = true;
            } catch (Exception $e) {
                error_log('[Aethos] Erro PHPMailer no reenvio: ' . $mail->ErrorInfo);
            }
        }

        // Fallback para mail() nativo caso o PHPMailer não esteja configurado
        if (!$enviado) {
            $assunto = 'Aethos — Novo Código de Verificação';
            $corpo   = "Olá, {$nome}!\n\nSeu novo código de verificação é: {$novo_codigo}\n\nInsira este código na plataforma.";
            $headers = "From: no-reply@aethos.com\r\nContent-Type: text/plain; charset=UTF-8\r\n";
            mail($email, $assunto, $corpo, $headers);
        }

        echo json_encode(array(
            'sucesso' => true,
            'mensagem' => 'Novo código de verificação enviado para o seu e-mail!'
        ));
        exit;
    } catch (PDOException $e) {
        error_log('[Aethos] Erro ao reenviar código: ' . $e->getMessage());
        echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro interno de servidor.'));
        exit;
    }
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
