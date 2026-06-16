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
            registrar_log($pdo, 'INFO', 'auth', 'novo_cadastro_pendente',
                'Novo cadastro pendente de verificação: ' . $nome . ' (' . $tipoUsuario . ') — ' . $email,
                $novo_id);

            echo json_encode(array(
                'sucesso'  => true,
                'mensagem' => 'Cadastro pré-aprovado! Verifique seu e-mail.',
                'email'    => $email,
                'codigo_simulado' => $codigo_verificacao // Apenas para facilitar testes locais!
            ));
        } else {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Falha ao salvar no banco.'));
        }

    } catch (PDOException $e) {
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
