-- ============================================================================
-- Sistema Integrado de Gestão Comercial (SIGC) - Lee Market
-- Esquema de Banco de Dados MySQL (PDO)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `sigc` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sigc`;

-- 1. Tabela de Colaboradores / Usuários (RH e Acesso)
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cpf` VARCHAR(14) NOT NULL UNIQUE,
  `nome` VARCHAR(150) NOT NULL,
  `senha` VARCHAR(255) NOT NULL,
  `cargo` ENUM('dono', 'gerente', 'caixa') NOT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_cpf (`cpf`),
  INDEX idx_cargo (`cargo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabela de Fornecedores
CREATE TABLE IF NOT EXISTS `fornecedores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(150) NOT NULL,
  `cnpj` VARCHAR(20) DEFAULT NULL,
  `telefone` VARCHAR(25) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `endereco` VARCHAR(255) DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabela de Produtos
CREATE TABLE IF NOT EXISTS `produtos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo_barras` VARCHAR(50) NOT NULL UNIQUE,
  `nome` VARCHAR(150) NOT NULL,
  `categoria` VARCHAR(80) NOT NULL,
  `preco_custo` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `preco_venda` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `estoque_atual` INT NOT NULL DEFAULT 0,
  `estoque_minimo` INT NOT NULL DEFAULT 5,
  `fornecedor_id` INT DEFAULT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_produtos_fornecedor` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_cod_barras (`codigo_barras`),
  INDEX idx_nome (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabela de Movimentações de Estoque (Entradas, Saídas e Ajustes)
CREATE TABLE IF NOT EXISTS `movimentacoes_estoque` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `produto_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `tipo` VARCHAR(25) NOT NULL, -- 'ENTRADA', 'SAIDA_VENDA', 'SAIDA_AVARIA', 'SAIDA_VENCIMENTO', 'SAIDA_CONSUMO', 'SAIDA_OUTROS', 'AJUSTE'
  `quantidade` INT NOT NULL,
  `quantidade_anterior` INT NOT NULL,
  `quantidade_nova` INT NOT NULL,
  `motivo` TEXT DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_mov_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mov_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT,
  INDEX idx_mov_tipo (`tipo`),
  INDEX idx_mov_criado (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabela de Vendas (PDV)
CREATE TABLE IF NOT EXISTS `vendas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo_venda` VARCHAR(30) NOT NULL UNIQUE,
  `usuario_id` INT NOT NULL,
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `forma_pagamento` VARCHAR(30) NOT NULL, -- 'Dinheiro', 'Cartão de Crédito', 'Cartão de Débito', 'PIX'
  `valor_recebido` DECIMAL(10,2) DEFAULT NULL,
  `troco` DECIMAL(10,2) DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'CONCLUIDA',
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_vendas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT,
  INDEX idx_vendas_criado (`criado_em`),
  INDEX idx_vendas_pagto (`forma_pagamento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabela de Itens da Venda
CREATE TABLE IF NOT EXISTS `itens_venda` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `venda_id` INT NOT NULL,
  `produto_id` INT NOT NULL,
  `quantidade` INT NOT NULL,
  `preco_unitario` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  CONSTRAINT `fk_item_venda` FOREIGN KEY (`venda_id`) REFERENCES `vendas`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabela de Fluxo de Caixa (Entradas e Saídas Financeiras)
CREATE TABLE IF NOT EXISTS `fluxo_caixa` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tipo` VARCHAR(20) NOT NULL, -- 'ENTRADA', 'SAIDA'
  `categoria` VARCHAR(50) NOT NULL, -- 'Venda', 'Suprimento', 'Sangria', 'Despesa Operacional', 'Pagamento Fornecedor'
  `descricao` VARCHAR(255) NOT NULL,
  `valor` DECIMAL(10,2) NOT NULL,
  `referencia_venda_id` INT DEFAULT NULL,
  `usuario_id` INT NOT NULL,
  `data_movimento` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_fluxo_venda` FOREIGN KEY (`referencia_venda_id`) REFERENCES `vendas`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_fluxo_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT,
  INDEX idx_fluxo_tipo (`tipo`),
  INDEX idx_fluxo_data (`data_movimento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabela de Auditoria e Logs do Sistema (CORE)
CREATE TABLE IF NOT EXISTS `auditoria_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT DEFAULT NULL,
  `usuario_nome` VARCHAR(150) DEFAULT NULL,
  `usuario_cpf` VARCHAR(20) DEFAULT NULL,
  `usuario_cargo` VARCHAR(30) DEFAULT NULL,
  `setor` VARCHAR(50) NOT NULL, -- 'Autenticação', 'Vendas', 'Estoque', 'Produtos', 'Fornecedores', 'Financeiro', 'RH'
  `acao` VARCHAR(80) NOT NULL,
  `detalhes` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_setor (`setor`),
  INDEX idx_audit_criado (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
