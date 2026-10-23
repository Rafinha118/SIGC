<?php
/**
 * Conexão com o Banco de Dados via PDO (Seguro contra SQL Injection)
 * Sistema Integrado de Gestão Comercial (SIGC) - Lee Market
 */

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'sigc');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

function getDB(): PDO {
    static $pdoInstance = null;
    if ($pdoInstance !== null) {
        return $pdoInstance;
    }

    $dbHost = DB_HOST;
    $dbPort = DB_PORT;
    $dbName = DB_NAME;
    $dbUser = DB_USER;
    $dbPass = DB_PASS;

    // 1. Tenta conectar ao MySQL via PDO
    try {
        $dsnWithoutDb = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
        $pdoInit = new PDO($dsnWithoutDb, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 2
        ]);

        // Garante que a base existe
        $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $dsnWithDb = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
        $pdoInstance = new PDO($dsnWithDb, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        inicializarTabelasMySQL($pdoInstance);
        return $pdoInstance;

    } catch (Throwable $e) {
        // 2. Se MySQL não estiver ativo (ex: offline no ambiente atual), usa SQLite com PDO
        // garantindo que a aplicação funcione imediatamente sem quebrar.
        $sqliteDir = __DIR__ . '/../database';
        if (!is_dir($sqliteDir)) {
            @mkdir($sqliteDir, 0777, true);
        }
        $sqlitePath = $sqliteDir . '/sigc.sqlite';
        $dsnSqlite = "sqlite:" . $sqlitePath;
        
        $pdoInstance = new PDO($dsnSqlite, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        inicializarTabelasSQLite($pdoInstance);
        return $pdoInstance;
    }
}

/**
 * Cria tabelas e insere seeds no MySQL se ainda não existirem
 */
function inicializarTabelasMySQL(PDO $pdo): void {
    $check = $pdo->query("SHOW TABLES LIKE 'usuarios'")->fetch();
    if (!$check) {
        $schemaPath = __DIR__ . '/../database/schema.sql';
        $seedPath = __DIR__ . '/../database/seed.sql';
        if (file_exists($schemaPath)) {
            $sql = file_get_contents($schemaPath);
            $pdo->exec($sql);
        }
        if (file_exists($seedPath)) {
            $sqlSeed = file_get_contents($seedPath);
            $pdo->exec($sqlSeed);
        }
    }
}

/**
 * Cria tabelas e insere seeds no SQLite se ainda não existirem
 */
function inicializarTabelasSQLite(PDO $pdo): void {
    $check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='usuarios'")->fetch();
    if (!$check) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS usuarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cpf VARCHAR(14) NOT NULL UNIQUE,
                nome VARCHAR(150) NOT NULL,
                senha VARCHAR(255) NOT NULL,
                cargo VARCHAR(20) NOT NULL,
                status INTEGER NOT NULL DEFAULT 1,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS fornecedores (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome VARCHAR(150) NOT NULL,
                cnpj VARCHAR(20),
                telefone VARCHAR(25) NOT NULL,
                email VARCHAR(100) NOT NULL,
                endereco VARCHAR(255),
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS produtos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                codigo_barras VARCHAR(50) NOT NULL UNIQUE,
                nome VARCHAR(150) NOT NULL,
                categoria VARCHAR(80) NOT NULL,
                preco_custo REAL NOT NULL DEFAULT 0.00,
                preco_venda REAL NOT NULL DEFAULT 0.00,
                estoque_atual INTEGER NOT NULL DEFAULT 0,
                estoque_minimo INTEGER NOT NULL DEFAULT 5,
                fornecedor_id INTEGER,
                ativo INTEGER NOT NULL DEFAULT 1,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id)
            );

            CREATE TABLE IF NOT EXISTS movimentacoes_estoque (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                produto_id INTEGER NOT NULL,
                usuario_id INTEGER NOT NULL,
                tipo VARCHAR(25) NOT NULL,
                quantidade INTEGER NOT NULL,
                quantidade_anterior INTEGER NOT NULL,
                quantidade_nova INTEGER NOT NULL,
                motivo TEXT,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (produto_id) REFERENCES produtos(id),
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
            );

            CREATE TABLE IF NOT EXISTS vendas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                codigo_venda VARCHAR(30) NOT NULL UNIQUE,
                usuario_id INTEGER NOT NULL,
                total REAL NOT NULL DEFAULT 0.00,
                forma_pagamento VARCHAR(30) NOT NULL,
                valor_recebido REAL,
                troco REAL,
                status VARCHAR(20) NOT NULL DEFAULT 'CONCLUIDA',
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
            );

            CREATE TABLE IF NOT EXISTS itens_venda (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                venda_id INTEGER NOT NULL,
                produto_id INTEGER NOT NULL,
                quantidade INTEGER NOT NULL,
                preco_unitario REAL NOT NULL,
                subtotal REAL NOT NULL,
                FOREIGN KEY (venda_id) REFERENCES vendas(id),
                FOREIGN KEY (produto_id) REFERENCES produtos(id)
            );

            CREATE TABLE IF NOT EXISTS fluxo_caixa (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tipo VARCHAR(20) NOT NULL,
                categoria VARCHAR(50) NOT NULL,
                descricao VARCHAR(255) NOT NULL,
                valor REAL NOT NULL,
                referencia_venda_id INTEGER,
                usuario_id INTEGER NOT NULL,
                data_movimento DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (referencia_venda_id) REFERENCES vendas(id),
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
            );

            CREATE TABLE IF NOT EXISTS auditoria_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER,
                usuario_nome VARCHAR(150),
                usuario_cpf VARCHAR(20),
                usuario_cargo VARCHAR(30),
                setor VARCHAR(50) NOT NULL,
                acao VARCHAR(80) NOT NULL,
                detalhes TEXT,
                ip_address VARCHAR(45),
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Insere seeds no SQLite via exec
        $pdo->exec("
            INSERT INTO usuarios (cpf, nome, senha, cargo, status) VALUES 
            ('111.111.111-11', 'Carlos Dono', '$2y$10\$UQOcoMAtKxf16iPlZ1oEbuVHi3yXnoBtpJODRlQ1Soz5m7xTo0wDW', 'dono', 1),
            ('222.222.222-22', 'Mariana Gerente', '$2y$10\$5x9.WgUursT0HxGLK/e7ZOM6dAt8yPHQEKoFmZ4.w7XbFdy3jxh1K', 'gerente', 1),
            ('333.333.333-33', 'Lucas Caixa', '$2y$10\$BpJ6NpTui8m0ItfOMbonveK27DSli47NroFWkQemRcdGa6lybhiFm', 'caixa', 1);

            INSERT INTO fornecedores (nome, cnpj, telefone, email, endereco) VALUES
            ('Distribuidora Alimentos Brasil Ltda', '12.345.678/0001-90', '(11) 3456-7890', 'contato@alimentosbrasil.com.br', 'Av. das Indústrias, 1500 - São Paulo, SP'),
            ('Laticínios Vale Verde S/A', '98.765.432/0001-10', '(35) 3214-5678', 'vendas@valeverde.com.br', 'Rodovia MG-050, Km 120 - Passos, MG'),
            ('Bebidas & Cia Distribuidora', '45.123.789/0001-55', '(11) 2345-6789', 'pedidos@bebidasecia.com.br', 'Rua das Palmeiras, 450 - Campinas, SP'),
            ('Higienix Produtos de Limpeza', '33.987.654/0001-22', '(19) 3876-1234', 'sac@higienix.com.br', 'Parque Fabril, Lote 12 - Paulínia, SP');

            INSERT INTO produtos (codigo_barras, nome, categoria, preco_custo, preco_venda, estoque_atual, estoque_minimo, fornecedor_id, ativo) VALUES
            ('7891000100101', 'Arroz Branco Tipo 1 5kg', 'Mercearia', 18.50, 27.90, 85, 20, 1, 1),
            ('7891000100102', 'Feijão Carioca 1kg', 'Mercearia', 5.20, 8.49, 120, 25, 1, 1),
            ('7891000100103', 'Óleo de Soja 900ml', 'Mercearia', 4.10, 6.75, 95, 30, 1, 1),
            ('7891000100104', 'Açúcar Refinado 1kg', 'Mercearia', 2.80, 4.39, 140, 25, 1, 1),
            ('7891000100105', 'Café Torrado e Moído 500g', 'Mercearia', 12.00, 18.90, 60, 15, 1, 1),
            ('7892000200201', 'Leite Integral UHT 1L', 'Laticínios', 3.30, 4.89, 210, 40, 2, 1),
            ('7892000200202', 'Manteiga com Sal 200g', 'Laticínios', 7.50, 11.50, 45, 10, 2, 1),
            ('7892000200203', 'Queijo Mussarela Fatiado 200g', 'Laticínios', 6.80, 10.90, 50, 15, 2, 1),
            ('7893000300301', 'Refrigerante Cola 2L', 'Bebidas', 5.10, 8.99, 110, 20, 3, 1),
            ('7893000300302', 'Suco Integral de Uva 1.5L', 'Bebidas', 9.20, 14.50, 40, 10, 3, 1),
            ('7893000300303', 'Água Mineral sem Gás 500ml', 'Bebidas', 0.85, 2.00, 250, 50, 3, 1),
            ('7894000400401', 'Detergente Líquido 500ml', 'Limpeza', 1.45, 2.79, 180, 30, 4, 1),
            ('7894000400402', 'Sabão em Pó 1kg', 'Limpeza', 7.80, 12.90, 75, 15, 4, 1),
            ('7894000400403', 'Desinfetante Lavanda 1L', 'Limpeza', 3.20, 5.49, 90, 20, 4, 1);

            INSERT INTO fluxo_caixa (tipo, categoria, descricao, valor, usuario_id) VALUES
            ('ENTRADA', 'Suprimento', 'Fundo de troco de abertura de caixa', 500.00, 1);

            INSERT INTO auditoria_logs (usuario_id, usuario_nome, usuario_cpf, usuario_cargo, setor, acao, detalhes, ip_address) VALUES
            (1, 'Carlos Dono', '111.111.111-11', 'dono', 'Sistema', 'INICIALIZACAO', 'Carga inicial do sistema e cadastro de usuários padrão', '127.0.0.1');
        ");
    }
}
