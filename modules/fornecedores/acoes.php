<?php
/**
 * Ações de Fornecedores - SIGC Lee Market
 * Restrito ao perfil Dono
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/audit.php';

exigirCargo('dono');

$acao = $_GET['acao'] ?? '';
$pdo = getDB();

switch ($acao) {
    case 'salvar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . baseUrl('modules/fornecedores/index.php'));
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $cnpj = trim($_POST['cnpj'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $endereco = trim($_POST['endereco'] ?? '');

        if (empty($nome) || empty($telefone) || empty($email)) {
            definirToast('erro', 'Nome, telefone e e-mail são obrigatórios.');
            header('Location: ' . baseUrl($id > 0 ? "modules/fornecedores/form.php?id={$id}" : 'modules/fornecedores/form.php'));
            exit;
        }

        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE fornecedores 
                    SET nome = ?, cnpj = ?, telefone = ?, email = ?, endereco = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nome, $cnpj, $telefone, $email, $endereco, $id]);

                registrarAuditoria('Fornecedores', 'FORNECEDOR_ATUALIZADO', "Fornecedor '{$nome}' (ID: {$id}) atualizado.");
                definirToast('sucesso', "Fornecedor '{$nome}' atualizado com sucesso!");
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO fornecedores (nome, cnpj, telefone, email, endereco, criado_em)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nome, $cnpj, $telefone, $email, $endereco, date('Y-m-d H:i:s')]);
                $novoId = $pdo->lastInsertId();

                registrarAuditoria('Fornecedores', 'FORNECEDOR_CRIADO', "Fornecedor '{$nome}' (ID: {$novoId}) cadastrado.");
                definirToast('sucesso', "Fornecedor '{$nome}' cadastrado com sucesso!");
            }

            header('Location: ' . baseUrl('modules/fornecedores/index.php'));
            exit;

        } catch (Throwable $e) {
            error_log("Erro ao salvar fornecedor: " . $e->getMessage());
            definirToast('erro', 'Erro ao salvar informações no banco de dados.');
            header('Location: ' . baseUrl('modules/fornecedores/index.php'));
            exit;
        }

    case 'excluir':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            definirToast('erro', 'Identificador inválido.');
            header('Location: ' . baseUrl('modules/fornecedores/index.php'));
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT nome FROM fornecedores WHERE id = ?");
            $stmt->execute([$id]);
            $nome = $stmt->fetchColumn();

            if (!$nome) {
                definirToast('erro', 'Fornecedor não encontrado.');
                header('Location: ' . baseUrl('modules/fornecedores/index.php'));
                exit;
            }

            // Desvincula produtos primeiro para integridade referencial
            $pdo->prepare("UPDATE produtos SET fornecedor_id = NULL WHERE fornecedor_id = ?")->execute([$id]);

            // Exclui o fornecedor
            $stmtDel = $pdo->prepare("DELETE FROM fornecedores WHERE id = ?");
            $stmtDel->execute([$id]);

            registrarAuditoria('Fornecedores', 'FORNECEDOR_REMOVIDO', "Fornecedor '{$nome}' (ID: {$id}) foi removido do sistema.");
            definirToast('sucesso', "Fornecedor '{$nome}' excluído com sucesso!");
            header('Location: ' . baseUrl('modules/fornecedores/index.php'));
            exit;

        } catch (Throwable $e) {
            error_log("Erro ao remover fornecedor: " . $e->getMessage());
            definirToast('erro', 'Não foi possível excluir o fornecedor.');
            header('Location: ' . baseUrl('modules/fornecedores/index.php'));
            exit;
        }

    default:
        header('Location: ' . baseUrl('modules/fornecedores/index.php'));
        exit;
}
