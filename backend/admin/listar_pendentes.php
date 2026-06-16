<?php
// ============================================================
// AETHOS — admin/listar_pendentes.php
// Lista locais aguardando aprovação (aprovado=0, ativo=1)
// ============================================================
session_start();
header('Content-Type: application/json');
require_once '../conexao.php';
require_once '../helpers.php';

exigir_sessao(array('admin', 'desenvolvedor'));

try {
    $stmt = $pdo->prepare(
        "SELECT l.id, l.nome, l.modalidade, l.cidade, l.estado, l.criado_em, l.foto_capa,
                u.nome AS professor_nome, u.email AS professor_email
         FROM locais_esportivos l
         INNER JOIN usuarios u ON l.professor_id = u.id
         WHERE l.aprovado = 0 AND l.ativo = 1
         ORDER BY l.criado_em ASC"
    );
    $stmt->execute();
    $pendentes = $stmt->fetchAll();

    echo json_encode(array(
        'sucesso'   => true,
        'total'     => count($pendentes),
        'data'      => $pendentes
    ));
} catch (PDOException $e) {
    error_log('[Aethos] Erro em listar_pendentes: ' . $e->getMessage());
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao buscar pendentes.'));
}
?>
