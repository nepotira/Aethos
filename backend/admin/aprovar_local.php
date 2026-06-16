<?php
// ============================================================
// AETHOS — admin/aprovar_local.php
// Aprova local esportivo e registra log
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
    $admin_id = $_SESSION['usuario_id'];
    $stmt = $pdo->prepare(
        "UPDATE locais_esportivos
         SET aprovado = 1, aprovado_por = :admin_id, aprovado_em = NOW()
         WHERE id = :id AND aprovado = 0"
    );
    $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
    $stmt->bindParam(':id',       $id,       PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        echo json_encode(array('sucesso' => false, 'mensagem' => 'Local não encontrado ou já aprovado.'));
        exit;
    }

    // Busca nome do local para log
    $stmt_nome = $pdo->prepare("SELECT nome FROM locais_esportivos WHERE id = :id");
    $stmt_nome->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_nome->execute();
    $local = $stmt_nome->fetch();
    $nome_local = $local ? $local['nome'] : 'ID ' . $id;

    registrar_log($pdo, 'INFO', 'admin', 'local_aprovado',
        'Local "' . $nome_local . '" aprovado pelo Admin ' . $_SESSION['nome'],
        $admin_id);

    echo json_encode(array('sucesso' => true, 'mensagem' => 'Local aprovado com sucesso!'));

} catch (PDOException $e) {
    error_log('[Aethos] Erro em aprovar_local: ' . $e->getMessage());
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao aprovar local.'));
}
?>
