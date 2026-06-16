<?php
// ============================================================
// AETHOS — avaliacoes.php
// CRUD de avaliações de locais esportivos
// ?acao=criar | editar | listar_por_local | remover
// ============================================================
session_start();
header('Content-Type: application/json');
require_once 'conexao.php';
require_once 'helpers.php';

$acao = isset($_GET['acao']) ? $_GET['acao'] : '';

switch ($acao) {

    // ─── POST: Cria nova avaliação (Atleta logado) ──────────
    case 'criar':
        exigir_sessao(array('comum'));
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) $data = $_POST;

        $local_id  = isset($data['local_id']) ? intval($data['local_id'])  : 0;
        $nota      = isset($data['nota'])     ? intval($data['nota'])      : 0;
        $comentario = isset($data['comentario']) ? trim($data['comentario']) : null;

        if (!$local_id) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'ID do local inválido.'));
            exit;
        }
        if ($nota < 1 || $nota > 5) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Nota deve ser entre 1 e 5.'));
            exit;
        }

        // Verifica se o local existe e está aprovado
        $stmt_local = $pdo->prepare(
            "SELECT id FROM locais_esportivos WHERE id = :id AND aprovado = 1 AND ativo = 1"
        );
        $stmt_local->bindParam(':id', $local_id, PDO::PARAM_INT);
        $stmt_local->execute();
        if (!$stmt_local->fetch()) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Local não encontrado ou não aprovado.'));
            exit;
        }

        $usuario_id = $_SESSION['usuario_id'];

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO avaliacoes (local_id, usuario_id, nota, comentario)
                 VALUES (:local_id, :usuario_id, :nota, :comentario)"
            );
            $stmt->bindParam(':local_id',   $local_id,  PDO::PARAM_INT);
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->bindParam(':nota',       $nota,       PDO::PARAM_INT);
            $stmt->bindParam(':comentario', $comentario);
            $stmt->execute();

            registrar_log($pdo, 'INFO', 'locais', 'avaliacao_criada',
                'Usuário ' . $_SESSION['nome'] . ' avaliou local ID ' . $local_id . ' com nota ' . $nota,
                $usuario_id);

            echo json_encode(array('sucesso' => true, 'mensagem' => 'Avaliação enviada com sucesso!'));

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(array('sucesso' => false, 'mensagem' => 'Você já avaliou este local. Use a opção de editar.'));
            } else {
                error_log('[Aethos] Erro em avaliacoes.php/criar: ' . $e->getMessage());
                echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao salvar avaliação.'));
            }
        }
        break;

    // ─── POST: Edita avaliação existente ────────────────────
    case 'editar':
        exigir_sessao(array('comum'));
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) $data = $_POST;

        $local_id   = isset($data['local_id'])  ? intval($data['local_id'])  : 0;
        $nota       = isset($data['nota'])      ? intval($data['nota'])      : 0;
        $comentario = isset($data['comentario']) ? trim($data['comentario']) : null;
        $usuario_id = $_SESSION['usuario_id'];

        if ($nota < 1 || $nota > 5) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Nota inválida.'));
            exit;
        }

        try {
            $stmt = $pdo->prepare(
                "UPDATE avaliacoes SET nota = :nota, comentario = :comentario
                 WHERE local_id = :local_id AND usuario_id = :usuario_id AND ativa = 1"
            );
            $stmt->bindParam(':nota',       $nota,        PDO::PARAM_INT);
            $stmt->bindParam(':comentario', $comentario);
            $stmt->bindParam(':local_id',   $local_id,    PDO::PARAM_INT);
            $stmt->bindParam(':usuario_id', $usuario_id,  PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                echo json_encode(array('sucesso' => false, 'mensagem' => 'Avaliação não encontrada ou sem permissão.'));
            } else {
                registrar_log($pdo, 'INFO', 'locais', 'avaliacao_editada',
                    'Usuário ' . $_SESSION['nome'] . ' editou avaliação do local ID ' . $local_id,
                    $usuario_id);
                echo json_encode(array('sucesso' => true, 'mensagem' => 'Avaliação atualizada!'));
            }
        } catch (PDOException $e) {
            error_log('[Aethos] Erro em avaliacoes.php/editar: ' . $e->getMessage());
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao atualizar avaliação.'));
        }
        break;

    // ─── GET: Lista avaliações de um local (?local_id=X) ────
    case 'listar_por_local':
        $local_id = isset($_GET['local_id']) ? intval($_GET['local_id']) : 0;
        if (!$local_id) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'ID do local inválido.'));
            break;
        }

        $stmt = $pdo->prepare(
            "SELECT a.id, a.nota, a.comentario, a.criado_em,
                    u.nome AS avaliador_nome, u.foto_perfil AS avaliador_foto
             FROM avaliacoes a
             INNER JOIN usuarios u ON a.usuario_id = u.id
             WHERE a.local_id = :local_id AND a.ativa = 1
             ORDER BY a.criado_em DESC
             LIMIT 50"
        );
        $stmt->bindParam(':local_id', $local_id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(array('sucesso' => true, 'data' => $stmt->fetchAll()));
        break;

    // ─── POST: Remove avaliação (Admin/Dev ou dono) ─────────
    case 'remover':
        exigir_sessao();
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) $data = $_POST;

        $id = isset($data['id']) ? intval($data['id']) : 0;
        if (!$id) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'ID inválido.'));
            break;
        }

        $usuario_id  = $_SESSION['usuario_id'];
        $tipo        = $_SESSION['tipo_usuario'];
        $pode_remover = false;

        if (in_array($tipo, array('admin', 'desenvolvedor'))) {
            $pode_remover = true;
        } else {
            // Verifica se é o dono
            $stmt_c = $pdo->prepare("SELECT usuario_id FROM avaliacoes WHERE id = :id AND ativa = 1");
            $stmt_c->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt_c->execute();
            $av = $stmt_c->fetch();
            if ($av && $av['usuario_id'] == $usuario_id) $pode_remover = true;
        }

        if (!$pode_remover) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Sem permissão para remover esta avaliação.'));
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE avaliacoes SET ativa = 0 WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            registrar_log($pdo, 'AVISO', 'admin', 'avaliacao_removida',
                'Avaliação ID ' . $id . ' removida por ' . $_SESSION['nome'],
                $usuario_id);

            echo json_encode(array('sucesso' => true, 'mensagem' => 'Avaliação removida.'));
        } catch (PDOException $e) {
            error_log('[Aethos] Erro em avaliacoes.php/remover: ' . $e->getMessage());
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao remover avaliação.'));
        }
        break;

    default:
        echo json_encode(array('sucesso' => false, 'mensagem' => 'Ação inválida.'));
        break;
}
?>
