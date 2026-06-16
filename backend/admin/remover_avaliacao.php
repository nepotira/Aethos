<?php
// ============================================================
// AETHOS — admin/remover_avaliacao.php
// Soft delete de avaliação pelo Admin/Dev
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
    $stmt = $pdo->prepare("UPDATE avaliacoes SET ativa = 0 WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        echo json_encode(array('sucesso' => false, 'mensagem' => 'Avaliação não encontrada.'));
        exit;
    }

    registrar_log($pdo, 'AVISO', 'admin', 'avaliacao_removida',
        'Avaliação ID ' . $id . ' removida pelo Admin ' . $_SESSION['nome'],
        $_SESSION['usuario_id']);

    echo json_encode(array('sucesso' => true, 'mensagem' => 'Avaliação removida com sucesso.'));

} catch (PDOException $e) {
    error_log('[Aethos] Erro em remover_avaliacao: ' . $e->getMessage());
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao remover avaliação.'));
}
?>
