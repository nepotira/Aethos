<?php
// ============================================================
// AETHOS — backend/redefinir_senha.php
// Fluxo completo de redefinição de senha por e-mail
// ?acao=solicitar | verificar_token | confirmar
// Requer: PHPMailer ou mail() nativo
// ============================================================
header('Content-Type: application/json');
require_once 'conexao.php';
require_once 'helpers.php';
require_once 'config.php';

$acao = isset($_GET['acao']) ? $_GET['acao'] : '';

switch ($acao) {

    // ─── POST: Solicita redefinição ──────────────────────────
    case 'solicitar':
        $data  = json_decode(file_get_contents('php://input'), true);
        $email = isset($data['email']) ? strtolower(trim($data['email'])) : '';

        // Resposta sempre genérica (não revelar se e-mail existe)
        $resposta_generica = array(
            'sucesso'  => true,
            'mensagem' => 'Se o e-mail estiver cadastrado, você receberá as instruções em breve.'
        );

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode($resposta_generica);
            exit;
        }

        // Busca usuário
        $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE email = :email AND ativo = 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user) {
            // Simula delay para evitar timing attack
            usleep(rand(100000, 300000));
            echo json_encode($resposta_generica);
            exit;
        }

        // Invalida tokens anteriores deste usuário
        $stmt_inv = $pdo->prepare("UPDATE tokens_redefinicao SET usado = 1 WHERE usuario_id = :uid AND usado = 0");
        $stmt_inv->bindParam(':uid', $user['id'], PDO::PARAM_INT);
        $stmt_inv->execute();

        // Gera token único de 64 chars
        if (function_exists('random_bytes')) {
            $token = bin2hex(random_bytes(32));
        } else {
            // Fallback PHP 5.4
            $token = bin2hex(openssl_random_pseudo_bytes(32));
        }

        $expira_em  = date('Y-m-d H:i:s', time() + (TOKEN_EXPIRACAO_HORAS * 3600));
        $usuario_id = $user['id'];

        try {
            $stmt_tok = $pdo->prepare(
                "INSERT INTO tokens_redefinicao (usuario_id, token, expira_em)
                 VALUES (:uid, :token, :expira)"
            );
            $stmt_tok->bindParam(':uid',    $usuario_id, PDO::PARAM_INT);
            $stmt_tok->bindParam(':token',  $token);
            $stmt_tok->bindParam(':expira', $expira_em);
            $stmt_tok->execute();

            // Monta o link
            $link = BASE_URL . 'redefinir-senha.html?token=' . $token;

            // ─── Tenta enviar e-mail ──────────────────────────
            $enviado = false;

            // Tenta PHPMailer se disponível
            if (file_exists(__DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
                require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
                require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
                require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';

                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USER;
                    $mail->Password   = SMTP_PASS;
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = SMTP_PORT;
                    $mail->CharSet    = 'UTF-8';

                    $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
                    $mail->addAddress($email, $user['nome']);
                    $mail->isHTML(true);
                    $mail->Subject = 'Aethos — Redefinição de Senha';
                    $mail->Body    = '
                        <div style="font-family:Inter,sans-serif; background:#0D0F32; color:#fff; padding:2rem; border-radius:12px;">
                            <h2 style="color:#A5B4FC;">Redefinição de Senha — Aethos</h2>
                            <p>Olá, <strong>' . htmlspecialchars($user['nome']) . '</strong>!</p>
                            <p>Você solicitou a redefinição de sua senha. Clique no botão abaixo para criar uma nova senha:</p>
                            <a href="' . $link . '" style="display:inline-block; background:#A5B4FC; color:#0D0F32; padding:0.75rem 2rem; border-radius:8px; text-decoration:none; font-weight:700; margin:1rem 0;">Redefinir Senha</a>
                            <p style="color:rgba(255,255,255,0.5); font-size:0.85rem;">Este link expira em ' . TOKEN_EXPIRACAO_HORAS . ' hora(s). Se você não solicitou isso, ignore este e-mail.</p>
                            <p style="color:rgba(255,255,255,0.4); font-size:0.8rem;">Link: <a href="' . $link . '" style="color:#A5B4FC;">' . $link . '</a></p>
                        </div>';
                    $mail->send();
                    $enviado = true;
                } catch (Exception $e) {
                    error_log('[Aethos] Erro PHPMailer: ' . $mail->ErrorInfo);
                }
            }

            // Fallback: mail() nativo
            if (!$enviado) {
                $assunto  = 'Aethos — Redefinição de Senha';
                $corpo    = "Olá, {$user['nome']}!\n\nClique no link abaixo para redefinir sua senha:\n{$link}\n\nEste link expira em " . TOKEN_EXPIRACAO_HORAS . " hora(s).";
                $headers  = "From: " . SMTP_FROM_NAME . " <" . SMTP_USER . ">\r\nContent-Type: text/plain; charset=UTF-8\r\n";
                $enviado  = @mail($email, $assunto, $corpo, $headers);
            }

            registrar_log($pdo, 'INFO', 'auth', 'token_redefinicao_gerado',
                'Token gerado para ' . $email . ' (enviado: ' . ($enviado ? 'sim' : 'nao') . ')',
                $usuario_id);

        } catch (PDOException $e) {
            error_log('[Aethos] Erro em redefinir_senha/solicitar: ' . $e->getMessage());
        }

        echo json_encode($resposta_generica);
        break;

    // ─── GET: Verifica se token é válido ─────────────────────
    case 'verificar_token':
        $token = isset($_GET['token']) ? trim($_GET['token']) : '';
        if (!$token) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Token não informado.'));
            exit;
        }

        $stmt = $pdo->prepare(
            "SELECT id, usuario_id, expira_em, usado FROM tokens_redefinicao
             WHERE token = :token"
        );
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $tok = $stmt->fetch();

        if (!$tok) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Token inválido.'));
            exit;
        }
        if ($tok['usado']) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Este link já foi utilizado.'));
            exit;
        }
        if (strtotime($tok['expira_em']) < time()) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Este link expirou.'));
            exit;
        }

        echo json_encode(array('sucesso' => true, 'mensagem' => 'Token válido.'));
        break;

    // ─── POST: Confirma nova senha ───────────────────────────
    case 'confirmar':
        $data      = json_decode(file_get_contents('php://input'), true);
        $token     = isset($data['token'])     ? trim($data['token'])     : '';
        $nova_senha = isset($data['nova_senha']) ? $data['nova_senha'] : '';

        if (!$token || strlen($nova_senha) < 5) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Dados inválidos.'));
            exit;
        }

        // Busca o token
        $stmt = $pdo->prepare(
            "SELECT id, usuario_id, expira_em, usado FROM tokens_redefinicao
             WHERE token = :token"
        );
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $tok = $stmt->fetch();

        if (!$tok || $tok['usado'] || strtotime($tok['expira_em']) < time()) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Link inválido ou expirado.'));
            exit;
        }

        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $uid  = $tok['usuario_id'];

        try {
            // Atualiza a senha
            $stmt_upd = $pdo->prepare(
                "UPDATE usuarios SET senha = :senha, primeiro_acesso = 0 WHERE id = :id"
            );
            $stmt_upd->bindParam(':senha', $hash);
            $stmt_upd->bindParam(':id',    $uid, PDO::PARAM_INT);
            $stmt_upd->execute();

            // Marca o token como usado
            $stmt_tok = $pdo->prepare("UPDATE tokens_redefinicao SET usado = 1 WHERE id = :id");
            $stmt_tok->bindParam(':id', $tok['id'], PDO::PARAM_INT);
            $stmt_tok->execute();

            registrar_log($pdo, 'INFO', 'auth', 'senha_redefinida',
                'Senha redefinida via token por usuário ID ' . $uid, $uid);

            echo json_encode(array('sucesso' => true, 'mensagem' => 'Senha redefinida com sucesso!'));

        } catch (PDOException $e) {
            error_log('[Aethos] Erro em redefinir_senha/confirmar: ' . $e->getMessage());
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao salvar nova senha.'));
        }
        break;

    default:
        echo json_encode(array('sucesso' => false, 'mensagem' => 'Ação inválida.'));
        break;
}
?>
