<?php
// ============================================================
// AETHOS — admin/excluir_usuario.php
// Soft delete (desativa) ou reativa usuário
// ============================================================
session_start();
header('Content-Type: application/json');
require_once '../conexao.php';
require_once '../helpers.php';

exigir_sessao(array('admin', 'desenvolvedor'));

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) $data = $_POST;

$id    = isset($data['id'])     ? intval($data['id'])   : 0;
$ativo = isset($data['ativo'])  ? intval($data['ativo']) : 0; // 0 = desativar, 1 = reativar

if (!$id) {
    echo json_encode(array('sucesso' => false, 'mensagem' => 'ID inválido.'));
    exit;
}

// Admin não pode desativar a si mesmo
if ($id == $_SESSION['usuario_id']) {
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Você não pode desativar sua própria conta.'));
    exit;
}

// Não permite desativar outros admins (exceto dev)
if ($_SESSION['tipo_usuario'] !== 'desenvolvedor') {
    $stmt_check = $pdo->prepare("SELECT tipo_usuario FROM usuarios WHERE id = :id");
    $stmt_check->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_check->execute();
    $target = $stmt_check->fetch();
    if ($target && in_array($target['tipo_usuario'], array('admin', 'desenvolvedor'))) {
        echo json_encode(array('sucesso' => false, 'mensagem' => 'Não é possível alterar perfis privilegiados.'));
        exit;
    }
}

try {
    $stmt = $pdo->prepare("UPDATE usuarios SET ativo = :ativo WHERE id = :id");
    $stmt->bindParam(':ativo', $ativo, PDO::PARAM_INT);
    $stmt->bindParam(':id',    $id,    PDO::PARAM_INT);
    $stmt->execute();

    $acao_log = $ativo ? 'usuario_reativado' : 'usuario_desativado';
    $nivel    = $ativo ? 'INFO' : 'AVISO';
    $msg      = $ativo ? 'reativado' : 'desativado';

    registrar_log($pdo, $nivel, 'admin', $acao_log,
        'Usuário ID ' . $id . ' ' . $msg . ' pelo Admin ' . $_SESSION['nome'],
        $_SESSION['usuario_id']);

    echo json_encode(array(
        'sucesso'  => true,
        'mensagem' => 'Usuário ' . $msg . ' com sucesso.'
    ));
} catch (PDOException $e) {
    error_log('[Aethos] Erro em excluir_usuario: ' . $e->getMessage());
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao atualizar usuário.'));
}
?>
