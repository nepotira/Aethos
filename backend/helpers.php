<?php
// ============================================================
// AETHOS — helpers.php
// Funções utilitárias centralizadas do sistema
// Compatível com PHP 5.4.17+
// ============================================================

/**
 * Registra uma entrada no log do sistema.
 *
 * @param PDO    $pdo       Conexão PDO ativa
 * @param string $nivel     INFO | AVISO | ERRO | CRITICO
 * @param string $modulo    auth | locais | admin | dev | database | seguranca
 * @param string $acao      Descrição curta da ação
 * @param string $descricao Detalhes adicionais (opcional)
 * @param int    $usuario_id ID do usuário que executou (opcional)
 */
function registrar_log($pdo, $nivel, $modulo, $acao, $descricao = null, $usuario_id = null) {
    try {
        $ip         = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

        $stmt = $pdo->prepare(
            "INSERT INTO logs_sistema (nivel, modulo, acao, descricao, usuario_id, ip, user_agent)
             VALUES (:nivel, :modulo, :acao, :descricao, :usuario_id, :ip, :user_agent)"
        );
        $stmt->bindParam(':nivel',      $nivel);
        $stmt->bindParam(':modulo',     $modulo);
        $stmt->bindParam(':acao',       $acao);
        $stmt->bindParam(':descricao',  $descricao);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':ip',         $ip);
        $stmt->bindParam(':user_agent', $user_agent);
        $stmt->execute();
    } catch (Exception $e) {
        // Log failure nunca deve quebrar o fluxo principal
        error_log('[Aethos] Falha ao registrar log: ' . $e->getMessage());
    }
}

/**
 * Verifica se o usuário atual tem uma sessão ativa com o perfil exigido.
 * Retorna JSON 403 e encerra se não tiver.
 *
 * @param string|array $perfis_permitidos Perfil(s) que podem acessar
 */
function exigir_sessao($perfis_permitidos = null) {
    if (!isset($_SESSION['usuario_id'])) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(array('sucesso' => false, 'mensagem' => 'Acesso Negado. Faça login primeiro.'));
        exit;
    }

    if ($perfis_permitidos !== null) {
        if (!is_array($perfis_permitidos)) {
            $perfis_permitidos = array($perfis_permitidos);
        }
        if (!in_array($_SESSION['tipo_usuario'], $perfis_permitidos)) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Acesso Negado. Permissão insuficiente.'));
            exit;
        }
    }
}

/**
 * Obtém o IP real do cliente, considerando proxies.
 */
function obter_ip() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

/**
 * Sanitiza string para saída HTML segura.
 */
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Valida CPF brasileiro (algoritmo de dígito verificador).
 * Retorna true se válido, false caso contrário.
 */
function validar_cpf($cpf) {
    // Remove máscara
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    // Deve ter 11 dígitos
    if (strlen($cpf) != 11) return false;

    // Rejeita sequências inválidas
    if (preg_match('/^(\d)\1{10}$/', $cpf)) return false;

    // Calcula 1º dígito verificador
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += intval($cpf[$i]) * (10 - $i);
    }
    $resto = $soma % 11;
    $digito1 = ($resto < 2) ? 0 : (11 - $resto);
    if (intval($cpf[9]) != $digito1) return false;

    // Calcula 2º dígito verificador
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += intval($cpf[$i]) * (11 - $i);
    }
    $resto = $soma % 11;
    $digito2 = ($resto < 2) ? 0 : (11 - $resto);
    if (intval($cpf[10]) != $digito2) return false;

    return true;
}

/**
 * Verifica se o domínio do e-mail possui registro MX válido.
 * Retorna true se válido, false se o domínio não existe.
 */
function validar_dominio_email($email) {
    $partes = explode('@', $email);
    if (count($partes) !== 2) return false;
    $dominio = $partes[1];
    return checkdnsrr($dominio, 'MX');
}
?>
