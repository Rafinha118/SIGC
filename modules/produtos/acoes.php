<?php
/**
 * Ações de Backend para Produtos - SIGC Lee Market
 * Restrito ao perfil Dono (salvar, atualizar, alterar preço e excluir)
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
            header('Location: ' . baseUrl('modules/produtos/index.php'));
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $codigoBarras = trim($_POST['codigo_barras'] ?? '');
        $nome = trim($_POST['nome'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $precoCusto = (float)str_replace(',', '.', $_POST['preco_custo'] ?? '0');
        $precoVenda = (float)str_replace(',', '.', $_POST['preco_venda'] ?? '0');
        $estoqueAtual = (int)($_POST['estoque_atual'] ?? 0);
        $estoqueMinimo = (int)($_POST['estoque_minimo'] ?? 5);
        $fornecedorId = !empty($_POST['fornecedor_id']) ? (int)$_POST['fornecedor_id'] : null;

        if (empty($codigoBarras) || empty($nome) || empty($categoria) || $precoVenda <= 0) {
            definirToast('erro', 'Preencha todos os campos obrigatórios e garanta que o preço de venda seja positivo.');
            header('Location: ' . baseUrl($id > 0 ? "modules/produtos/form.php?id={$id}" : 'modules/produtos/form.php'));
            exit;
        }

        try {
            // Verifica duplicidade de código de barras
            $stmtCheck = $pdo->prepare("SELECT id FROM produtos WHERE codigo_barras = ? AND id != ? AND ativo = 1 LIMIT 1");
            $stmtCheck->execute([$codigoBarras, $id]);
            if ($stmtCheck->fetch()) {
                definirToast('erro', "O código de barras '{$codigoBarras}' já está em uso por outro produto.");
                header('Location: ' . baseUrl($id > 0 ? "modules/produtos/form.php?id={$id}" : 'modules/produtos/form.php'));
                exit;
            }

            if ($id > 0) {
                // Atualização
                $stmtAntes = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
                $stmtAntes->execute([$id]);
                $prodAntes = $stmtAntes->fetch();

                $stmtUpdate = $pdo->prepare("
                    UPDATE produtos SET 
                        codigo_barras = ?, nome = ?, categoria = ?, preco_custo = ?, preco_venda = ?,
                        estoque_atual = ?, estoque_minimo = ?, fornecedor_id = ?, atualizado_em = ?
                    WHERE id = ?
                ");
                $stmtUpdate->execute([
                    $codigoBarras, $nome, $categoria, $precoCusto, $precoVenda,
                    $estoqueAtual, $estoqueMinimo, $fornecedorId, date('Y-m-d H:i:s'), $id
                ]);

                registrarAuditoria(
                    'Produtos',
                    'PRODUTO_ATUALIZADO',
                    "Atualizado produto '{$nome}' (ID: {$id}). Preço Venda: de R$ {$prodAntes['preco_venda']} para R$ {$precoVenda}"
                );

                definirToast('sucesso', "Produto '{$nome}' atualizado com sucesso!");
            } else {
                // Novo Cadastro
                $stmtInsert = $pdo->prepare("
                    INSERT INTO produtos 
                    (codigo_barras, nome, categoria, preco_custo, preco_venda, estoque_atual, estoque_minimo, fornecedor_id, ativo, criado_em, atualizado_em)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)
                ");
                $agora = date('Y-m-d H:i:s');
                $stmtInsert->execute([
                    $codigoBarras, $nome, $categoria, $precoCusto, $precoVenda,
                    $estoqueAtual, $estoqueMinimo, $fornecedorId, $agora, $agora
                ]);
                $novoId = $pdo->lastInsertId();

                // Registra movimentação inicial no estoque se estoqueAtual > 0
                if ($estoqueAtual > 0) {
                    $stmtMov = $pdo->prepare("
                        INSERT INTO movimentacoes_estoque 
                        (produto_id, usuario_id, tipo, quantidade, quantidade_anterior, quantidade_nova, motivo, criado_em)
                        VALUES (?, ?, 'ENTRADA', ?, 0, ?, 'Cadastro inicial do produto', ?)
                    ");
                    $stmtMov->execute([$novoId, usuarioAtual()['id'], $estoqueAtual, $estoqueAtual, $agora]);
                }

                registrarAuditoria(
                    'Produtos',
                    'PRODUTO_CRIADO',
                    "Cadastrado novo produto '{$nome}' (Código: {$codigoBarras}, Preço Venda: R$ {$precoVenda})"
                );

                definirToast('sucesso', "Produto '{$nome}' cadastrado com sucesso!");
            }

            header('Location: ' . baseUrl('modules/produtos/index.php'));
            exit;

        } catch (Throwable $e) {
            error_log("Erro ao salvar produto: " . $e->getMessage());
            definirToast('erro', 'Erro ao salvar produto no banco de dados.');
            header('Location: ' . baseUrl('modules/produtos/index.php'));
            exit;
        }

    case 'alterar_preco':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . baseUrl('modules/produtos/index.php'));
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $novoPreco = (float)str_replace(',', '.', $_POST['novo_preco'] ?? '0');

        if ($id <= 0 || $novoPreco <= 0) {
            definirToast('erro', 'Preço informado inválido.');
            header('Location: ' . baseUrl('modules/produtos/index.php'));
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT id, nome, preco_venda FROM produtos WHERE id = ? AND ativo = 1");
            $stmt->execute([$id]);
            $prod = $stmt->fetch();

            if (!$prod) {
                definirToast('erro', 'Produto não localizado.');
                header('Location: ' . baseUrl('modules/produtos/index.php'));
                exit;
            }

            $precoAntigo = (float)$prod['preco_venda'];
            $stmtUpdate = $pdo->prepare("UPDATE produtos SET preco_venda = ?, atualizado_em = ? WHERE id = ?");
            $stmtUpdate->execute([$novoPreco, date('Y-m-d H:i:s'), $id]);

            registrarAuditoria(
                'Produtos',
                'PRECO_ALTERADO',
                "Preço do produto '{$prod['nome']}' alterado de R$ " . number_format($precoAntigo, 2, ',', '.') . " para R$ " . number_format($novoPreco, 2, ',', '.')
            );

            definirToast('sucesso', "Preço do produto '{$prod['nome']}' alterado para R$ " . number_format($novoPreco, 2, ',', '.') . "!");
            header('Location: ' . baseUrl('modules/produtos/index.php'));
            exit;

        } catch (Throwable $e) {
            error_log("Erro ao alterar preço: " . $e->getMessage());
            definirToast('erro', 'Erro no banco de dados ao modificar preço.');
            header('Location: ' . baseUrl('modules/produtos/index.php'));
            exit;
        }

    case 'excluir':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            definirToast('erro', 'Identificador de produto inválido.');
            header('Location: ' . baseUrl('modules/produtos/index.php'));
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT id, nome FROM produtos WHERE id = ?");
            $stmt->execute([$id]);
            $prod = $stmt->fetch();

            if (!$prod) {
                definirToast('erro', 'Produto não encontrado.');
                header('Location: ' . baseUrl('modules/produtos/index.php'));
                exit;
            }

            // Realiza soft delete para preservar integridade de vendas anteriores e auditorias
            $stmtDel = $pdo->prepare("UPDATE produtos SET ativo = 0, atualizado_em = ? WHERE id = ?");
            $stmtDel->execute([date('Y-m-d H:i:s'), $id]);

            registrarAuditoria(
                'Produtos',
                'PRODUTO_REMOVIDO',
                "Produto '{$prod['nome']}' (ID: {$id}) foi removido do catálogo ativo pelo Dono."
            );

            definirToast('sucesso', "Produto '{$prod['nome']}' removido com sucesso!");
            header('Location: ' . baseUrl('modules/produtos/index.php'));
            exit;

        } catch (Throwable $e) {
            error_log("Erro ao remover produto: " . $e->getMessage());
            definirToast('erro', 'Não foi possível remover o produto.');
            header('Location: ' . baseUrl('modules/produtos/index.php'));
            exit;
        }

    default:
        header('Location: ' . baseUrl('modules/produtos/index.php'));
        exit;
}
