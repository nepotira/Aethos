<?php
// ============================================================
// AETHOS — register.php
// Endpoint de cadastro de novos usuários
// Inclui: validação CPF, DNS MX, tamanho senha, logging
// Compatível com PHP 5.4.17+
// ============================================================
header('Content-Type: application/json');
require_once 'conexao.php';
require_once 'helpers.php';

$decoded = json_decode(file_get_contents('php://input'), true);
$data    = !empty($decoded) ? $decoded : $_POST;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($data['email']) && isset($data['senha'])) {

    $tipoUsuario  = isset($data['tipo_usuario']) ? $data['tipo_usuario'] : 'comum';
    $nome         = isset($data['nome'])         ? trim($data['nome'])   : '';
    $apelido      = isset($data['apelido'])      ? trim($data['apelido']) : null;
    $email        = isset($data['email'])        ? strtolower(trim($data['email'])) : '';
    $ddd          = isset($data['ddd'])          ? trim($data['ddd'])    : '';
    $telefone     = isset($data['telefone'])     ? trim($data['telefone']) : '';
    $senhaBruta   = isset($data['senha'])        ? $data['senha']        : '';

    // --- Validação: campos obrigatórios ---
    if (empty($nome) || empty($email)) {
        echo json_encode(array('sucesso' => false, 'mensagem' => 'Nome e e-mail são obrigatórios.'));
        exit;
    }

    // --- Validação: tamanho mínimo de senha ---
    if (strlen($senhaBruta) < 5) {
        echo json_encode(array('sucesso' => false, 'mensagem' => 'A senha deve ter no mínimo 5 caracteres.'));
        exit;
    }

    // --- Validação: formato de e-mail básico ---
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(array('sucesso' => false, 'mensagem' => 'O e-mail informado não é válido.'));
        exit;
    }

    // --- Validação: DNS MX do domínio do e-mail (Módulo 11 do Regimento) ---
    if (!validar_dominio_email($email)) {
        echo json_encode(array('sucesso' => false, 'mensagem' => 'O e-mail informado não parece válido. Verifique o domínio.'));
        exit;
    }

    // --- Campos exclusivos de Professor ---
    $cpf          = null;
    $endereco_fixo = null;

    if ($tipoUsuario === 'professor') {
        $cpf_bruto     = isset($data['cpf'])      ? $data['cpf']      : '';
        $endereco_fixo = isset($data['endereco']) ? $data['endereco'] : null;

        // Validação: CPF real (Módulo 10 do Regimento)
        if (!validar_cpf($cpf_bruto)) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'CPF inválido. Verifique os dados informados.'));
            exit;
        }
        // Armazena CPF somente com dígitos
        $cpf = preg_replace('/[^0-9]/', '', $cpf_bruto);

        if (empty($endereco_fixo)) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Endereço é obrigatório para professores.'));
            exit;
        }
    }

    // --- Bloqueia cadastro de perfis privilegiados via formulário público ---
    if (in_array($tipoUsuario, array('admin', 'desenvolvedor'))) {
        echo json_encode(array('sucesso' => false, 'mensagem' => 'Este perfil não pode ser cadastrado publicamente.'));
        exit;
    }

    // --- Gera hash da senha ---
    $senha = password_hash($senhaBruta, PASSWORD_DEFAULT);
    
    // --- Gera código de verificação de e-mail (Simulação) ---
    $codigo_verificacao = sprintf("%06d", mt_rand(1, 999999));

    try {
        // --- LIMPEZA DE CADASTROS NÃO VALIDADOS ---
        // Se o usuário tentou cadastrar antes mas não validou o e-mail, deletamos para ele tentar de novo
        $stmt_check = $pdo->prepare("SELECT id, email_verificado FROM usuarios WHERE email = :email OR (cpf = :cpf AND cpf IS NOT NULL)");
        $stmt_check->execute(array(':email' => $email, ':cpf' => $cpf));
        $existentes = $stmt_check->fetchAll(PDO::FETCH_ASSOC);

        foreach ($existentes as $ext) {
            if ($ext['email_verificado'] == 1) {
                echo json_encode(array('sucesso' => false, 'mensagem' => 'O E-mail ou CPF já está cadastrado e validado. Faça login.'));
                exit;
            } else {
                $stmt_del = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
                $stmt_del->execute(array(':id' => $ext['id']));
            }
        }

        $pdo->beginTransaction();

        // Se o professor selecionou um local já existente, copiamos o endereço dele
        if ($tipoUsuario === 'professor' && isset($data['local_existente']) && $data['local_existente'] === 'sim') {
            $local_id_existente = isset($data['local_id_existente']) ? $data['local_id_existente'] : null;
            if (!$local_id_existente) {
                echo json_encode(array('sucesso' => false, 'mensagem' => 'Selecione o local existente.'));
                exit;
            }
            $stmtLoc = $pdo->prepare("SELECT endereco FROM locais_esportivos WHERE id = :id");
            $stmtLoc->execute(array(':id' => $local_id_existente));
            $localData = $stmtLoc->fetch(PDO::FETCH_ASSOC);
            if ($localData) {
                $endereco_fixo = $localData['endereco'];
            }
        }

        $sql = "INSERT INTO usuarios (tipo_usuario, nome, apelido, email, senha, ddd, telefone, cpf, endereco_fixo, codigo_verificacao, email_verificado)
                VALUES (:tipo, :nome, :apelido, :email, :senha, :ddd, :tel, :cpf, :endereco, :codigo, 0)";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':tipo',     $tipoUsuario);
        $stmt->bindParam(':nome',     $nome);
        $stmt->bindParam(':apelido',  $apelido);
        $stmt->bindParam(':email',    $email);
        $stmt->bindParam(':senha',    $senha);
        $stmt->bindParam(':ddd',      $ddd);
        $stmt->bindParam(':tel',      $telefone);
        $stmt->bindParam(':cpf',      $cpf);
        $stmt->bindParam(':endereco', $endereco_fixo);
        $stmt->bindParam(':codigo',   $codigo_verificacao);

        if ($stmt->execute()) {
            $novo_id = $pdo->lastInsertId();

            // Se for professor e for criar um NOVO local, inserimos na tabela locais_esportivos
            if ($tipoUsuario === 'professor' && (!isset($data['local_existente']) || $data['local_existente'] === 'nao')) {
                $nome_local = isset($data['nome_local']) ? trim($data['nome_local']) : 'Local de ' . $nome;
                $modalidade = isset($data['modalidade']) ? trim($data['modalidade']) : 'Outro';
                $lat        = (isset($data['latitude']) && $data['latitude'] !== '') ? $data['latitude'] : null;
                $lon        = (isset($data['longitude']) && $data['longitude'] !== '') ? $data['longitude'] : null;

                $sqlLocal = "INSERT INTO locais_esportivos (professor_id, nome, modalidade, endereco, latitude, longitude, aprovado) 
                             VALUES (:prof_id, :nome_loc, :mod, :end, :lat, :lon, 0)";
                $stmtLoc = $pdo->prepare($sqlLocal);
                $stmtLoc->bindParam(':prof_id',  $novo_id);
                $stmtLoc->bindParam(':nome_loc', $nome_local);
                $stmtLoc->bindParam(':mod',      $modalidade);
                $stmtLoc->bindParam(':end',      $endereco_fixo);
                $stmtLoc->bindParam(':lat',      $lat);
                $stmtLoc->bindParam(':lon',      $lon);
                $stmtLoc->execute();
            }

            $pdo->commit();

            registrar_log($pdo, 'INFO', 'auth', 'novo_cadastro_pendente',
                'Novo cadastro pendente de verificação: ' . $nome . ' (' . $tipoUsuario . ') — ' . $email,
                $novo_id);

            // --- Envia o e-mail com PHPMailer ---
            $enviado = false;
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
                    $mail->Subject = 'Aethos — Verifique seu E-mail';
                    $mail->Body    = '
                        <div style="font-family:Inter,sans-serif; background:#0D0F32; color:#fff; padding:2rem; border-radius:12px;">
                            <h2 style="color:#A5B4FC;">Bem-vindo(a) ao Aethos, ' . htmlspecialchars($nome) . '!</h2>
                            <p>Para ativar sua conta, por favor, insira o código de 6 dígitos abaixo na tela de verificação:</p>
                            <div style="font-size:2rem; font-weight:800; letter-spacing:0.5rem; color:#818CF8; margin:1.5rem 0; text-align:center; background:rgba(255,255,255,0.05); padding:1rem; border-radius:12px;">
                                ' . $codigo_verificacao . '
                            </div>
                            <p style="color:rgba(255,255,255,0.5); font-size:0.85rem;">Se você não solicitou este cadastro, ignore este e-mail.</p>
                        </div>';
                    $mail->send();
                    $enviado = true;
                } catch (Exception $e) {
                    error_log('[Aethos] Erro PHPMailer no cadastro: ' . $mail->ErrorInfo);
                }
            }

            // Fallback para mail() nativo
            if (!$enviado) {
                $assunto = 'Aethos — Verifique seu E-mail';
                $corpo   = "Olá, {$nome}!\n\nSeu código de verificação é: {$codigo_verificacao}\n\nInsira este código na plataforma para ativar sua conta.";
                $headers = "From: no-reply@aethos.com\r\nContent-Type: text/plain; charset=UTF-8\r\n";
                @mail($email, $assunto, $corpo, $headers);
            }

            echo json_encode(array(
                'sucesso'  => true,
                'mensagem' => 'Cadastro pré-aprovado! Verifique seu e-mail.',
                'email'    => $email
            ));
        } else {
            $pdo->rollBack();
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Falha ao salvar no banco.'));
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e->getCode() == 23000) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'O E-mail ou CPF já está cadastrado.'));
        } else {
            error_log('[Aethos] Erro em register.php: ' . $e->getMessage());
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro interno de servidor.'));
        }
    }

} else {
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Requisitos não preenchidos.'));
}
?>
