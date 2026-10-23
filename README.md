# Sistema Integrado de Gestão Comercial — SIGC (Lee Market)

O **SIGC (Lee Market)** é um sistema web completo desenvolvido para o gerenciamento integrado de supermercados, com arquitetura modular, segurança de dados e aderência estrita a todas as regras de negócio e requisitos estabelecidos.

---

## 📌 Identidade Visual & Design System

- **Tipografia:** Google Fonts [Isometra](https://fonts.google.com/specimen/Isometra?preview.script=Latn) com consistência tipográfica em todas as telas.
- **Paleta de Cores Oficial:**
  - 🟦 **Azul-escuro** (`#111184`): Cor principal institucional, cabeçalhos e navegação.
  - 🟨 **Amarelo** (`#FFD32C`): Destaques, botões de ação e alertas visuais.
  - ⬜ **Branco** (`#FFFFFF`): Fundos de cards e contraste.
  - ⬛ **Preto** (`#000000`): Textos e ícones.
- **Mobile First & Responsivo:** Interface otimizada com Tailwind CSS, adaptando-se a smartphones, tablets e desktops com navegação gaveta/hambúrguer.
- **Feedback & Usabilidade:**
  - **Notificações TOAST** flutuantes estilizadas para mensagens de sucesso, erro, aviso e informação.
  - **Modais Personalizados Lee Market** substituindo 100% das caixas de diálogo nativas do navegador (`alert` e `confirm`).

---

## ⚙️ Arquitetura & Stack Tecnológica

- **Backend:** PHP estruturado e modularizado com controle nativo de sessões (`$_SESSION`).
- **Banco de Dados:** MySQL com **PDO** para proteção contra SQL Injection. O sistema possui configuração automática com fallback transparente para SQLite (`database/sigc.sqlite`), permitindo execução imediata em qualquer ambiente de desenvolvimento ou produção (XAMPP, WAMP, Docker ou servidor embutido do PHP).
- **Segurança:** 
  - Senhas criptografadas com hash **Bcrypt** (`password_hash` e `password_verify`).
  - **Histórico de Auditoria Geral (CORE):** Tabela `auditoria_logs` registrando qualquer operação realizada por qualquer usuário (autor, CPF, cargo, setor, ação, detalhes, IP e data/hora).

---

## 👥 Perfis de Acesso & Credenciais de Demonstração

O sistema identifica o perfil do colaborador no momento do login (validando CPF, Nome Completo e Senha) e libera os recursos correspondentes:

| Perfil | CPF | Nome Completo | Senha | Módulos e Funções Acessíveis |
| :--- | :--- | :--- | :--- | :--- |
| **👤 Dono (Administrador)** | `111.111.111-11` | Carlos Dono | `dono123` | **Acesso Total:** Dashboard Geral/Financeiro, Fluxo de Caixa, Faturamento, Modificação de Preços, Catálogo de Produtos (+ / -), Fornecedores, Gestão de RH e Histórico de Auditoria de todos os setores. |
| **👨‍💼 Gerente** | `222.222.222-22` | Mariana Gerente | `gerente123` | **Operação & Estoque:** Dashboard Operacional, Gerenciamento de Estoque, Entrada/Ajuste de Mercadorias, Relatórios de Vendas por Período e Consulta de Fornecedores. |
| **🧑‍💻 Operador de Caixa** | `333.333.333-33` | Lucas Caixa | `caixa123` | **Frente de Caixa (PDV):** Bipagem/seleção de itens, cálculo de totais e troco, registro de pagamentos (Dinheiro, PIX, Cartão), emissão de comprovante/cupom, registro de saídas por avaria/perda e consulta rápida de estoque. |

---

## 📂 Estrutura Modular do Código

```
sigc/
├── config/
│   ├── bd.php             # Conexão PDO segura (MySQL com auto-setup e seeds)
│   └── auth.php           # Sessões nativas, RBAC e verificação de cargos
├── includes/
│   ├── header.php         # Cabeçalho com Tailwind, Isometra e paleta oficial
│   ├── navbar.php         # Menu responsivo Mobile First com perfil ativo
│   ├── footer.php         # Rodapé com renderizador de Toast e modais
│   ├── toast.php          # Gerenciamento de toasts do lado do servidor
│   └── audit.php          # Registrador central de logs de auditoria
├── modules/
│   ├── auth/              # Login com CPF, Nome e Senha, Logout
│   ├── dashboard/         # Dashboards customizados por perfil (Dono, Gerente, Caixa)
│   ├── produtos/          # Catálogo, cadastro, alteração de preços e remoção
│   ├── estoque/           # Controle de níveis, saídas por avaria/perda e entradas/ajustes
│   ├── vendas/            # Frente de Caixa (PDV), cupom fiscal e histórico
│   ├── financeiro/        # Fluxo de Caixa (entradas/saídas/saldo) e Faturamento
│   ├── fornecedores/      # Consulta de parceiros e catálogo de produtos vinculados
│   ├── relatorios/        # Relatórios de vendas por período, produto e operador
│   ├── rh/                # Gestão de colaboradores, funções e senhas bcrypt
│   └── auditoria/         # Histórico completo de auditoria para o administrador
├── assets/
│   ├── css/style.css      # Variáveis de cores Lee Market e animações
│   └── js/
│       ├── toast.js       # Toast notifications flutuantes
│       ├── modal.js       # Modais personalizados Lee Market
│       └── pdv.js         # Lógica reativa do PDV e cálculo de troco
├── database/
│   ├── schema.sql         # Esquema DDL para MySQL
│   └── seed.sql           # Carga de dados iniciais (produtos, usuários, etc.)
├── index.php              # Roteador inicial da aplicação
└── README.md              # Documentação oficial
```

---

## 🚀 Como Executar o Projeto

### Opção 1: Servidor Embutido do PHP (Recomendado para Testes Imediatos)
Abra o terminal na pasta do projeto e execute:
```bash
php -S localhost:8000
```
Em seguida, abra o navegador em: [http://localhost:8000](http://localhost:8000)

### Opção 2: XAMPP / WAMP / Laragon
1. Copie a pasta `sigc` para o diretório `htdocs` (XAMPP) ou `www` (WAMP).
2. Certifique-se de que o Apache e o MySQL estejam iniciados no painel.
3. Acesse no navegador: `http://localhost/sigc`
4. O sistema criará automaticamente a base de dados `sigc` e aplicará os esquemas e seeds na primeira inicialização via PDO!

---

## 🔒 Conformidade com os Requisitos

- [x] Backend PHP estruturado com sessões nativas
- [x] Banco de dados com PDO para total segurança contra SQL Injection
- [x] Senhas tratadas com hash Bcrypt
- [x] Histórico de auditoria e logs gravados em todas as operações
- [x] Telas de login com CPF, Nome Completo e Senha identificando o perfil
- [x] Frente de Caixa (PDV) com baixa automática de estoque e geração de cupom
- [x] Registro de saídas do estoque com validação de disponibilidade e motivos
- [x] Fluxo de Caixa com cálculo de saldo e lançamentos manuais
- [x] Acompanhamento de Faturamento por período com detalhamento de custos e formas de pagamento
- [x] Alteração de preços e gestão de produtos restrita ao Dono
- [x] Gestão de fornecedores com produtos associados
- [x] Relatórios de vendas analíticos por período, operadores e produtos
- [x] Gestão de RH com organização de funções
- [x] Tipografia oficial Google Fonts Isometra
- [x] Paleta oficial Lee Market (#111184, #FFD32C, #FFFFFF, #000000)
- [x] Toasts para mensagens de erro/sucesso
- [x] Modais customizados substituindo alert/confirm nativos
