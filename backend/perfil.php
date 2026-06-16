<?php
// ============================================================
// AETHOS — backend/perfil.php
// Dados do perfil do usuário logado
// ?acao=obter | editar | atualizar_foto | meus_locais | minhas_avaliacoes
// ============================================================
session_start();
header('Content-Type: application/json');
require_once 'conexao.php';
require_once 'helpers.php';

exigir_sessao();

$acao = isset($_GET['acao']) ? $_GET['acao'] : '';
$uid  = $_SESSION['usuario_id'];

switch ($acao) {

    // ─── GET: Dados completos do perfil ─────────────────────
    case 'obter':
        $stmt = $pdo->prepare(
            "SELECT id, tipo_usuario, nome, apelido, email, ddd, telefone,
                    foto_perfil, cpf, endereco_fixo, ativo, criado_em
             FROM usuarios WHERE id = :id"
        );
        $stmt->bindParam(':id', $uid, PDO::PARAM_INT);
        $stmt->execute();
        $u = $stmt->fetch();
        if (!$u) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Usuário não encontrado.'));
        } else {
            echo json_encode(array('sucesso' => true, 'data' => $u));
        }
        break;

    // ─── POST: Editar dados básicos ──────────────────────────
    case 'editar':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) $data = $_POST;

        $nome     = isset($data['nome'])     ? trim($data['nome'])     : null;
        $apelido  = isset($data['apelido'])  ? trim($data['apelido'])  : null;
        $ddd      = isset($data['ddd'])      ? trim($data['ddd'])      : null;
        $telefone = isset($data['telefone']) ? trim($data['telefone']) : null;

        if (!$nome || strlen($nome) < 2) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Nome inválido.'));
            exit;
        }

        try {
            $stmt = $pdo->prepare(
                "UPDATE usuarios SET nome = :nome, apelido = :apelido, ddd = :ddd, telefone = :tel
                 WHERE id = :id"
            );
            $stmt->bindParam(':nome',    $nome);
            $stmt->bindParam(':apelido', $apelido);
            $stmt->bindParam(':ddd',     $ddd);
            $stmt->bindParam(':tel',     $telefone);
            $stmt->bindParam(':id',      $uid, PDO::PARAM_INT);
            $stmt->execute();

            $_SESSION['nome'] = $nome;

            registrar_log($pdo, 'INFO', 'auth', 'perfil_editado',
                'Usuário ' . $nome . ' editou o próprio perfil.', $uid);

            echo json_encode(array('sucesso' => true, 'mensagem' => 'Perfil atualizado.'));
        } catch (PDOException $e) {
            error_log('[Aethos] Erro em perfil.php/editar: ' . $e->getMessage());
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao salvar.'));
        }
        break;

    // ─── POST: Atualizar foto de perfil ─────────────────────
    case 'atualizar_foto':
        $data = json_decode(file_get_contents('php://input'), true);
        $foto = isset($data['foto_perfil']) ? trim($data['foto_perfil']) : null;

        if (!$foto) {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Caminho da foto não informado.'));
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET foto_perfil = :foto WHERE id = :id");
            $stmt->bindParam(':foto', $foto);
            $stmt->bindParam(':id',   $uid, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['foto_perfil'] = $foto;
            echo json_encode(array('sucesso' => true, 'mensagem' => 'Foto atualizada.'));
        } catch (PDOException $e) {
            error_log('[Aethos] Erro ao atualizar foto: ' . $e->getMessage());
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Erro ao salvar foto.'));
        }
        break;

    // ─── GET: Locais do Professor ────────────────────────────
    case 'meus_locais':
        if ($_SESSION['tipo_usuario'] !== 'professor') {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Apenas professores possuem locais.'));
            exit;
        }
        $stmt = $pdo->prepare(
            "SELECT id, nome, modalidade, cidade, estado, aprovado, ativo, criado_em
             FROM locais_esportivos WHERE professor_id = :pid ORDER BY criado_em DESC"
        );
        $stmt->bindParam(':pid', $uid, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(array('sucesso' => true, 'data' => $stmt->fetchAll()));
        break;

    // ─── GET: Avaliações do Atleta ───────────────────────────
    case 'minhas_avaliacoes':
        if ($_SESSION['tipo_usuario'] !== 'comum') {
            echo json_encode(array('sucesso' => false, 'mensagem' => 'Apenas atletas possuem avaliações.'));
            exit;
        }
        $stmt = $pdo->prepare(
            "SELECT a.nota, a.comentario, a.criado_em,
                    l.nome AS local_nome, l.modalidade
             FROM avaliacoes a
             INNER JOIN locais_esportivos l ON a.local_id = l.id
             WHERE a.usuario_id = :uid AND a.ativa = 1
             ORDER BY a.criado_em DESC"
        );
        $stmt->bindParam(':uid', $uid, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(array('sucesso' => true, 'data' => $stmt->fetchAll()));
        break;

    default:
        echo json_encode(array('sucesso' => false, 'mensagem' => 'Ação inválida.'));
        break;
}
?>
