# ERP Controle de Obras

Um sistema web completo para gestão e controle de obras, construído do zero utilizando tecnologias modernas e simples (sem frameworks complexos). Ideal para o acompanhamento de obras, controle financeiro, gestão de materiais, recursos humanos e relatórios.

## 🛠️ Tecnologias Utilizadas

*   **Frontend:** HTML5, CSS3, JavaScript Vanilla (ES6+), Bootstrap 5 (via CDN).
*   **Backend:** PHP 8+ puro (Arquitetura MVC simples), API REST.
*   **Banco de Dados:** MySQL / MariaDB (com PDO e Prepared Statements contra SQL Injection).

## 📂 Estrutura de Diretórios

O projeto está dividido entre `frontend/`, `backend/` e `database/`.
Todas as requisições API são centralizadas através do roteador `backend/index.php`. O frontend consome os endpoints através da classe auxiliadora `API` presente no `frontend/js/api.js`.

---

## 🚀 Como Configurar e Rodar o Projeto (Ambiente Local)

### Pré-requisitos
*   Servidor web (Apache ou Nginx) + PHP 8.0 ou superior.
*   MySQL ou MariaDB rodando localmente (Ex: XAMPP, LAMP, Docker, etc).

### Passo 1: Configurar o Banco de Dados
1. Crie um banco de dados vazio ou deixe o script criar para você.
2. Importe o arquivo `database/schema.sql` para o seu SGBD MySQL/MariaDB.
    ```bash
    mysql -u root -p < database/schema.sql
    ```
    *(Este script criará o banco `controle_obras`, todas as tabelas necessárias e inserirá o administrador padrão).*

### Passo 2: Configurar a Conexão no Backend
1. Navegue até o arquivo `backend/config/Database.php`.
2. Se necessário, atualize as credenciais do banco (usuário, senha, porta) conforme o seu ambiente:
    ```php
    private $host = "localhost";
    private $db_name = "controle_obras";
    private $username = "root";
    private $password = "sua_senha_aqui";
    ```

### Passo 3: Iniciar o Servidor Web
Você pode utilizar o servidor embutido do PHP para testes rápidos na pasta raiz do projeto:

```bash
php -S localhost:8000
```

### Passo 4: Acessar a Aplicação
Abra o navegador e acesse a raiz do projeto (ex: `http://localhost:8000/frontend/index.html`).

Faça login utilizando a conta padrão:
*   **Email:** `admin@admin.com`
*   **Senha:** `password`

---

## 📖 Como Utilizar os Módulos

O sistema possui uma navegação lateral (Sidebar) dividida em 9 módulos principais:

1.  **Dashboard:** Visão geral rápida. Exibe o total de obras registradas, a quantidade de obras "Em andamento" e o somatório dos gastos financeiros já pagos.
2.  **Obras:** Cadastro de novos projetos. Permite definir nome, status, endereço, responsável técnico, datas e o percentual de conclusão da obra.
3.  **Etapas:** Detalhamento do projeto. Permite atrelar diferentes fases ou passos a uma obra específica, definindo status de andamento.
4.  **Financeiro:** Controle de fluxo de caixa da obra. Registre movimentações financeiras com base no tipo (`Orçamento` ou `Despesa`) e acompanhe os recebimentos e pagamentos.
5.  **Materiais:** Controle de estoque. Registre as entradas (compras) e saídas (uso) de materiais. **Importante:** O sistema não permite que seja registrada uma saída superior ao saldo disponível (Entradas - Saídas).
6.  **Funcionários:** Cadastro simples de RH para listar as funções, equipes e datas de admissão dos trabalhadores das obras.
7.  **Documentos:** Gestão de arquivos. Faça upload seguro de arquivos (PDF, JPG, PNG, DOCX) atrelados à obra, até um limite de 5MB. Os arquivos podem ser visualizados posteriormente no painel.
8.  **Relatórios:** Geração de balanços dinâmicos baseados no financeiro (despesas por vencimento) e de materiais (entradas vs saídas consolidadas). Permite realizar filtros por Obra e Data de Vencimento e gerar um PDF nativo via botão `Imprimir`.
9.  **Notificações:** Alertas do sistema. As notificações têm estilos visuais diferentes e função de "marcar como lida". A tela possui recarregamento automático nativo a cada 30 segundos para manter tudo atualizado.

---

## 🔒 Segurança Implementada
*   **Senhas:** Utilização de `password_hash()` (bcrypt) para persistência no banco.
*   **Sessões:** Toda a comunicação da API via Frontend inclui as credenciais para verificação da sessão HTTP via PHP nativo (`session_start()`).
*   **Injeção de SQL:** Utilização rígida do PDO (`prepare` e `bindParam`) no backend.
*   **Uploads:** O módulo de Documentos valida rigidamente o `MIME Type` via `finfo_file`, além da checagem de extensões seguras, prevenindo exploração de vulnerabilidades RCE (Remote Code Execution) comumente atreladas ao envio de shells maliciosos (`.php`).