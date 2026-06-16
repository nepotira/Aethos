<?php
// ============================================================
// AETHOS — dev/listar_logs.php
// Lista logs do sistema com filtros e paginação
// ============================================================
session_start();
header('Content-Type: application/json');
require_once '../conexao.php';
require_once '../helpers.php';

exigir_sessao(array('desenvolvedor'));

$nivel   = isset($_GET['nivel'])   ? $_GET['nivel']   : null;
$modulo  = isset($_GET['modulo'])  ? $_GET['modulo']  : null;
$de      = isset($_GET['de'])      ? $_GET['de']      : null;
$ate     = isset($_GET['ate'])     ? $_GET['ate']      : null;
$pagina  = isset($_GET['pagina'])  ? max(1, intval($_GET['pagina'])) : 1;
$por_pag = 50;
$offset  = ($pagina - 1) * $por_pag;

$where  = array('1=1');
$params = array();

$niveis_validos = array('INFO', 'AVISO', 'ERRO', 'CRITICO');
if ($nivel && in_array($nivel, $niveis_validos)) {
    $where[]          = 'l.nivel = :nivel';
    $params[':nivel'] = $nivel;
}

if ($modulo) {
    $where[]           = 'l.modulo = :modulo';
    $params[':modulo'] = $modulo;
}

if ($de) {
    $where[]      = 'l.criado_em >= :de';
    $params[':de'] = $de . ' 00:00:00';
}

if ($ate) {
    $where[]       = 'l.criado_em <= :ate';
    $params[':ate'] = $ate . ' 23:59:59';
}

$whereClause = implode(' AND ', $where);

try {
    // Contadores por nível
    $stmt_count = $pdo->prepare(
        "SELECT nivel, COUNT(*) as total FROM logs_sistema GROUP BY nivel"
    );
    $stmt_count->execute();
    $contadores = array();
    while ($row = $stmt_count->fetch()) {
        $contadores[$row['nivel']] = intval($row['total']);
    }

    // Total filtrado
    $stmt_total = $pdo->prepare("SELECT COUNT(*) as total FROM logs_sistema l WHERE $whereClause");
    foreach ($params as $k => $v) $stmt_total->bindValue($k, $v);
    $stmt_total->execute();
    $total = $stmt_total->fetch()['total'];

    // Dados paginados
    $stmt = $pdo->prepare(
        "SELECT l.id, l.nivel, l.modulo, l.acao, l.descricao, l.ip, l.user_agent, l.criado_em,
                u.nome AS usuario_nome
         FROM logs_sistema l
         LEFT JOIN usuarios u ON l.usuario_id = u.id
         WHERE $whereClause
         ORDER BY l.criado_em DESC
         LIMIT :limit OFFSET :offset"
    );
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit',  $por_pag, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(array(
        'sucesso'     => true,
        'total'       => intval($total),
        'pagina'      => $pagina,
        'total_pags'  => ceil($total / $por_pag),
        'contadores'  => $contadores,
        'data'        => $stmt->fetchAll()
    ));
} catch (PDOException $e) {
    error_log('[Aethos] Erro em listar_logs: ' . $e->getMessage());
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao buscar logs.'));
}
?>
