<?php
// ============================================================
// AETHOS — admin/listar_avaliacoes.php
// Lista todas as avaliações ativas com filtro por nota
// ============================================================
session_start();
header('Content-Type: application/json');
require_once '../conexao.php';
require_once '../helpers.php';

exigir_sessao(array('admin', 'desenvolvedor'));

$nota = isset($_GET['nota']) ? intval($_GET['nota']) : null;

$where = array('a.ativa = 1');
$params = array();

if ($nota && $nota >= 1 && $nota <= 5) {
    $where[] = 'a.nota = :nota';
    $params[':nota'] = $nota;
}

$whereClause = implode(' AND ', $where);

try {
    $stmt = $pdo->prepare(
        "SELECT a.id, a.nota, a.comentario, a.criado_em,
                u.nome AS avaliador_nome,
                l.nome AS local_nome
         FROM avaliacoes a
         INNER JOIN usuarios u ON a.usuario_id = u.id
         INNER JOIN locais_esportivos l ON a.local_id = l.id
         WHERE $whereClause
         ORDER BY a.criado_em DESC
         LIMIT 200"
    );
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(array('sucesso' => true, 'data' => $stmt->fetchAll()));
} catch (PDOException $e) {
    error_log('[Aethos] Erro em listar_avaliacoes: ' . $e->getMessage());
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao buscar avaliações.'));
}
?>
