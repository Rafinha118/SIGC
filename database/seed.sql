-- ============================================================================
-- Carga Inicial de Dados (Seed) - SIGC Lee Market
-- Senhas geradas com hash bcrypt padrão BCRYPT
--   - Dono:    CPF: 111.111.111-11 | Nome: Carlos Dono     | Senha: dono123
--   - Gerente: CPF: 222.222.222-22 | Nome: Mariana Gerente | Senha: gerente123
--   - Caixa:   CPF: 333.333.333-33 | Nome: Lucas Caixa     | Senha: caixa123
-- ============================================================================

-- Usuários Iniciais
INSERT INTO `usuarios` (`cpf`, `nome`, `senha`, `cargo`, `status`, `criado_em`) VALUES
('111.111.111-11', 'Carlos Dono', '$2y$10$UQOcoMAtKxf16iPlZ1oEbuVHi3yXnoBtpJODRlQ1Soz5m7xTo0wDW', 'dono', 1, NOW()),
('222.222.222-22', 'Mariana Gerente', '$2y$10$5x9.WgUursT0HxGLK/e7ZOM6dAt8yPHQEKoFmZ4.w7XbFdy3jxh1K', 'gerente', 1, NOW()),
('333.333.333-33', 'Lucas Caixa', '$2y$10$BpJ6NpTui8m0ItfOMbonveK27DSli47NroFWkQemRcdGa6lybhiFm', 'caixa', 1, NOW());

-- Fornecedores Iniciais
INSERT INTO `fornecedores` (`nome`, `cnpj`, `telefone`, `email`, `endereco`, `criado_em`) VALUES
('Distribuidora Alimentos Brasil Ltda', '12.345.678/0001-90', '(11) 3456-7890', 'contato@alimentosbrasil.com.br', 'Av. das Indústrias, 1500 - São Paulo, SP', NOW()),
('Laticínios Vale Verde S/A', '98.765.432/0001-10', '(35) 3214-5678', 'vendas@valeverde.com.br', 'Rodovia MG-050, Km 120 - Passos, MG', NOW()),
('Bebidas & Cia Distribuidora', '45.123.789/0001-55', '(11) 2345-6789', 'pedidos@bebidasecia.com.br', 'Rua das Palmeiras, 450 - Campinas, SP', NOW()),
('Higienix Produtos de Limpeza', '33.987.654/0001-22', '(19) 3876-1234', 'sac@higienix.com.br', 'Parque Fabril, Lote 12 - Paulínia, SP', NOW());

-- Produtos Iniciais
INSERT INTO `produtos` (`codigo_barras`, `nome`, `categoria`, `preco_custo`, `preco_venda`, `estoque_atual`, `estoque_minimo`, `fornecedor_id`, `ativo`, `criado_em`) VALUES
('7891000100101', 'Arroz Branco Tipo 1 5kg', 'Mercearia', 18.50, 27.90, 85, 20, 1, 1, NOW()),
('7891000100102', 'Feijão Carioca 1kg', 'Mercearia', 5.20, 8.49, 120, 25, 1, 1, NOW()),
('7891000100103', 'Óleo de Soja 900ml', 'Mercearia', 4.10, 6.75, 95, 30, 1, 1, NOW()),
('7891000100104', 'Açúcar Refinado 1kg', 'Mercearia', 2.80, 4.39, 140, 25, 1, 1, NOW()),
('7891000100105', 'Café Torrado e Moído 500g', 'Mercearia', 12.00, 18.90, 60, 15, 1, 1, NOW()),
('7892000200201', 'Leite Integral UHT 1L', 'Laticínios', 3.30, 4.89, 210, 40, 2, 1, NOW()),
('7892000200202', 'Manteiga com Sal 200g', 'Laticínios', 7.50, 11.50, 45, 10, 2, 1, NOW()),
('7892000200203', 'Queijo Mussarela Fatiado 200g', 'Laticínios', 6.80, 10.90, 50, 15, 2, 1, NOW()),
('7893000300301', 'Refrigerante Cola 2L', 'Bebidas', 5.10, 8.99, 110, 20, 3, 1, NOW()),
('7893000300302', 'Suco Integral de Uva 1.5L', 'Bebidas', 9.20, 14.50, 40, 10, 3, 1, NOW()),
('7893000300303', 'Água Mineral sem Gás 500ml', 'Bebidas', 0.85, 2.00, 250, 50, 3, 1, NOW()),
('7894000400401', 'Detergente Líquido 500ml', 'Limpeza', 1.45, 2.79, 180, 30, 4, 1, NOW()),
('7894000400402', 'Sabão em Pó 1kg', 'Limpeza', 7.80, 12.90, 75, 15, 4, 1, NOW()),
('7894000400403', 'Desinfetante Lavanda 1L', 'Limpeza', 3.20, 5.49, 90, 20, 4, 1, NOW());

-- Movimentações Iniciais de Estoque (Entradas)
INSERT INTO `movimentacoes_estoque` (`produto_id`, `usuario_id`, `tipo`, `quantidade`, `quantidade_anterior`, `quantidade_nova`, `motivo`, `criado_em`) VALUES
(1, 1, 'ENTRADA', 85, 0, 85, 'Estoque inicial de abertura da loja', NOW()),
(2, 1, 'ENTRADA', 120, 0, 120, 'Estoque inicial de abertura da loja', NOW()),
(6, 1, 'ENTRADA', 210, 0, 210, 'Estoque inicial de abertura da loja', NOW());

-- Fluxo de Caixa Inicial (Suprimento de Fundo de Troco)
INSERT INTO `fluxo_caixa` (`tipo`, `categoria`, `descricao`, `valor`, `referencia_venda_id`, `usuario_id`, `data_movimento`) VALUES
('ENTRADA', 'Suprimento', 'Fundo de troco de abertura de caixa', 500.00, NULL, 1, NOW());

-- Log de Auditoria Inicial
INSERT INTO `auditoria_logs` (`usuario_id`, `usuario_nome`, `usuario_cpf`, `usuario_cargo`, `setor`, `acao`, `detalhes`, `ip_address`, `criado_em`) VALUES
(1, 'Carlos Dono', '111.111.111-11', 'dono', 'Sistema', 'INICIALIZACAO', 'Carga inicial do sistema e cadastro de usuários padrão', '127.0.0.1', NOW());
