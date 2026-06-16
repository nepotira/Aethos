<?php
// ============================================================
// AETHOS — admin/rejeitar_local.php
// Rejeita (soft delete) local esportivo pendente
// ============================================================
session_start();
header('Content-Type: application/json');
require_once '../conexao.php';
require_once '../helpers.php';

exigir_sessao(array('admin', 'desenvolvedor'));

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) $data = $_POST;

$id = isset($data['id']) ? intval($data['id']) : 0;
if (!$id) {
    echo json_encode(array('sucesso' => false, 'mensagem' => 'ID inválido.'));
    exit;
}

try {
    $stmt = $pdo->prepare(
        "UPDATE locais_esportivos SET ativo = 0 WHERE id = :id AND aprovado = 0"
    );
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        echo json_encode(array('sucesso' => false, 'mensagem' => 'Local não encontrado ou já processado.'));
        exit;
    }

    registrar_log($pdo, 'AVISO', 'admin', 'local_rejeitado',
        'Local ID ' . $id . ' rejeitado pelo Admin ' . $_SESSION['nome'],
        $_SESSION['usuario_id']);

    echo json_encode(array('sucesso' => true, 'mensagem' => 'Local rejeitado e removido da fila.'));

} catch (PDOException $e) {
    error_log('[Aethos] Erro em rejeitar_local: ' . $e->getMessage());
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao rejeitar local.'));
}
?>
