<?php
/**
 * Ações de RH e Colaboradores - SIGC Lee Market
 * Restrito ao perfil Dono
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/audit.php';

exigirCargo('dono');

$acao = $_GET['acao'] ?? '';
$pdo = getDB();
$userLogado = usuarioAtual();

switch ($acao) {
    case 'salvar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . baseUrl('modules/rh/index.php'));
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $cpf = trim($_POST['cpf'] ?? '');
        $cargo = strtolower(trim($_POST['cargo'] ?? 'caixa'));
        $status = (int)($_POST['status'] ?? 1);
        $senha = trim($_POST['senha'] ?? '');

        if (empty($nome) || empty($cpf) || empty($cargo)) {
            definirToast('erro', 'Nome, CPF e Cargo são obrigatórios.');
            header('Location: ' . baseUrl($id > 0 ? "modules/rh/form.php?id={$id}" : 'modules/rh/form.php'));
            exit;
        }

        if (!in_array($cargo, ['dono', 'gerente', 'caixa'], true)) {
            definirToast('erro', 'Cargo selecionado inválido.');
            header('Location: ' . baseUrl('modules/rh/index.php'));
            exit;
        }

        try {
            // Verifica duplicidade de CPF
            $stmtCpf = $pdo->prepare("SELECT id FROM usuarios WHERE cpf = ? AND id != ? LIMIT 1");
            $stmtCpf->execute([$cpf, $id]);
            if ($stmtCpf->fetch()) {
                definirToast('erro', "O CPF '{$cpf}' já pertence a outro colaborador cadastrado.");
                header('Location: ' . baseUrl($id > 0 ? "modules/rh/form.php?id={$id}" : 'modules/rh/form.php'));
                exit;
            }

            if ($id > 0) {
                // Atualização
                if (!empty($senha)) {
                    $senhaHash = password_hash($senha, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("
                        UPDATE usuarios 
                        SET nome = ?, cpf = ?, cargo = ?, status = ?, senha = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$nome, $cpf, $cargo, $status, $senhaHash, $id]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE usuarios 
                        SET nome = ?, cpf = ?, cargo = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$nome, $cpf, $cargo, $status, $id]);
                }

                registrarAuditoria(
                    'RH',
                    'COLABORADOR_ATUALIZADO',
                    "Atualizado colaborador '{$nome}' (ID: {$id}, Cargo: {$cargo}, Status: {$status})"
                );

                definirToast('sucesso', "Colaborador '{$nome}' atualizado com sucesso!");
            } else {
                // Novo cadastro
                if (empty($senha)) {
                    definirToast('erro', 'A senha é obrigatória para o cadastro de novos colaboradores.');
                    header('Location: ' . baseUrl('modules/rh/form.php'));
                    exit;
                }

                $senhaHash = password_hash($senha, PASSWORD_BCRYPT);
                $agora = date('Y-m-d H:i:s');
                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (nome, cpf, senha, cargo, status, criado_em)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nome, $cpf, $senhaHash, $cargo, $status, $agora]);
                $novoId = $pdo->lastInsertId();

                registrarAuditoria(
                    'RH',
                    'COLABORADOR_CRIADO',
                    "Cadastrado novo colaborador '{$nome}' (ID: {$novoId}, Cargo: {$cargo})"
                );

                definirToast('sucesso', "Colaborador '{$nome}' cadastrado com sucesso!");
            }

            header('Location: ' . baseUrl('modules/rh/index.php'));
            exit;

        } catch (Throwable $e) {
            error_log("Erro ao salvar colaborador: " . $e->getMessage());
            definirToast('erro', 'Erro ao salvar colaborador no banco de dados.');
            header('Location: ' . baseUrl('modules/rh/index.php'));
            exit;
        }

    case 'status':
        $id = (int)($_GET['id'] ?? 0);
        $novoStatus = (int)($_GET['status'] ?? 0);

        if ($id <= 0) {
            definirToast('erro', 'Identificador inválido.');
            header('Location: ' . baseUrl('modules/rh/index.php'));
            exit;
        }

        if ($id === (int)$userLogado['id']) {
            definirToast('erro', 'Você não pode desativar seu próprio perfil.');
            header('Location: ' . baseUrl('modules/rh/index.php'));
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $nome = $stmt->fetchColumn();

            if (!$nome) {
                definirToast('erro', 'Colaborador não encontrado.');
                header('Location: ' . baseUrl('modules/rh/index.php'));
                exit;
            }

            $stmtUp = $pdo->prepare("UPDATE usuarios SET status = ? WHERE id = ?");
            $stmtUp->execute([$novoStatus, $id]);

            $statusTxt = $novoStatus === 1 ? 'ativado' : 'desativado';
            registrarAuditoria('RH', 'STATUS_ALTERADO', "Colaborador '{$nome}' (ID: {$id}) foi {$statusTxt}.");

            definirToast('sucesso', "Colaborador '{$nome}' {$statusTxt} com sucesso!");
            header('Location: ' . baseUrl('modules/rh/index.php'));
            exit;

        } catch (Throwable $e) {
            error_log("Erro ao alternar status do colaborador: " . $e->getMessage());
            definirToast('erro', 'Erro ao alterar situação do colaborador.');
            header('Location: ' . baseUrl('modules/rh/index.php'));
            exit;
        }

    default:
        header('Location: ' . baseUrl('modules/rh/index.php'));
        exit;
}
