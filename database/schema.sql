CREATE DATABASE IF NOT EXISTS controle_obras CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE controle_obras;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel_acesso ENUM('Administrador', 'RH', 'Financeiro', 'Almoxarifado', 'Engenharia', 'Visualização') NOT NULL DEFAULT 'Visualização',
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    tema_preferencia ENUM('claro', 'escuro') DEFAULT 'claro'
);

INSERT IGNORE INTO usuarios (nome, email, senha, nivel_acesso, status) VALUES (
    'Administrador',
    'admin@admin.com',
    '$2y$10$Y9krFwWc6LE/BQlZoALbWeohe7ui1gZapFSKmfnfTcYOLlDwlNw.e',
    'Administrador',
    'ativo'
);

CREATE TABLE IF NOT EXISTS funcoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    salario_base DECIMAL(10,2) DEFAULT 0.00,
    horario_padrao VARCHAR(100),
    setor VARCHAR(100),
    permissao_sugerida VARCHAR(50),
    status ENUM('ativo', 'inativo') DEFAULT 'ativo'
);

CREATE TABLE IF NOT EXISTS equipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    responsavel_id INT NULL,
    obra_id INT NULL,
    descricao TEXT,
    data_criacao DATE,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo'
);

CREATE TABLE IF NOT EXISTS tipos_obra (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS etapas_predefinidas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_obra_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    ordem INT NOT NULL DEFAULT 0,
    status_ativo TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (tipo_obra_id) REFERENCES tipos_obra(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS obras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    tipo ENUM('Casa', 'Apartamento', 'Reforma', 'Condomínio', 'Comercial', 'Industrial', 'Galpão', 'Outros') DEFAULT 'Outros',
    descricao TEXT,
    status ENUM('Em planejamento', 'Em andamento', 'Concluída', 'Paralisada', 'Cancelada') DEFAULT 'Em planejamento',
    endereco TEXT,
    responsavel_id INT NULL,
    equipe_id INT NULL,
    data_inicio DATE,
    data_fim_prevista DATE,
    percentual_concluido DECIMAL(5,2) DEFAULT 0.00,
    FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS etapas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obra_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    observacoes TEXT NULL,
    status ENUM('Pendente', 'Em andamento', 'Concluída') DEFAULT 'Pendente',
    percentual INT DEFAULT 0,
    data_inicio DATE NULL,
    data_fim_prevista DATE NULL,
    equipe_id INT NULL,
    responsavel_id INT NULL,
    ordem INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE CASCADE,
    FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE SET NULL,
    FOREIGN KEY (responsavel_id) REFERENCES funcionarios(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS financeiro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obra_id INT NOT NULL,
    tipo ENUM('orcamento', 'despesa') NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(15,2) NOT NULL,
    status ENUM('Pendente', 'Pago', 'Recebido', 'Cancelado') DEFAULT 'Pendente',
    data_vencimento DATE,
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS materiais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obra_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    categoria ENUM('EPI', 'Ferramentas', 'Material de Construção', 'Elétrica', 'Hidráulica', 'Escritório', 'Outros') DEFAULT 'Outros',
    quantidade DECIMAL(10,2) NOT NULL,
    unidade_medida VARCHAR(20) NOT NULL,
    tipo_movimentacao ENUM('entrada', 'saida') NOT NULL,
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS funcionarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    funcao VARCHAR(100) NULL, -- legacy
    funcao_id INT NULL,
    equipe VARCHAR(100) NULL, -- legacy
    equipe_id INT NULL,
    ausente BOOLEAN DEFAULT FALSE,
    status ENUM('ativo', 'afastado', 'demitido') DEFAULT 'ativo',
    data_admissao DATE,
    data_demissao DATE NULL,
    motivo_demissao TEXT NULL,
    FOREIGN KEY (funcao_id) REFERENCES funcoes(id) ON DELETE SET NULL,
    FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obra_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    caminho_arquivo VARCHAR(255) NOT NULL,
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(100) NULL,
    mensagem TEXT NOT NULL,
    tipo VARCHAR(50) DEFAULT 'info',
    prioridade ENUM('baixa', 'media', 'alta') DEFAULT 'baixa',
    modulo_origem VARCHAR(50) NULL,
    link VARCHAR(255) NULL,
    lida BOOLEAN DEFAULT FALSE,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS acidentes_historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_registro DATE NOT NULL,
    dias_sem_acidentes INT NOT NULL DEFAULT 0,
    houve_acidente BOOLEAN DEFAULT FALSE,
    descricao TEXT NULL
);

-- Inserir registro base para os dias sem acidentes se não houver
INSERT IGNORE INTO acidentes_historico (id, data_registro, dias_sem_acidentes) VALUES (1, CURDATE(), 0);

-- Inserir registros históricos de acidentes para fins demonstrativos e relatórios
INSERT IGNORE INTO acidentes_historico (id, data_registro, dias_sem_acidentes, houve_acidente, descricao) VALUES 
(2, DATE_SUB(CURDATE(), INTERVAL 45 DAY), 120, 1, 'Colaborador sofreu escoriação leve no joelho após escorregar em piso úmido na área de fundações. Utilizava todos os EPIs obrigatórios.'),
(3, DATE_SUB(CURDATE(), INTERVAL 15 DAY), 30, 1, 'Corte superficial no antebraço direito durante manuseio de chapas metálicas de cobertura. Atendimento ambulatorial prestado de forma imediata.');

-- Comandos ALTER TABLE caso o banco já exista localmente
-- Comandos ALTER TABLE para adicionar colunas em bancos locais existentes
-- Para evitar erros caso você já tenha o banco criado, execute os comandos abaixo manualmente no seu SGBD se necessário:
-- ALTER TABLE usuarios MODIFY COLUMN nivel_acesso ENUM('Administrador', 'RH', 'Financeiro', 'Almoxarifado', 'Engenharia', 'Visualização') NOT NULL DEFAULT 'Visualização';
-- ALTER TABLE usuarios ADD COLUMN status ENUM('ativo', 'inativo') DEFAULT 'ativo';
-- ALTER TABLE usuarios ADD COLUMN tema_preferencia ENUM('claro', 'escuro') DEFAULT 'claro';
-- ALTER TABLE materiais ADD COLUMN categoria ENUM('EPI', 'Ferramentas', 'Material de Construção', 'Elétrica', 'Hidráulica', 'Escritório', 'Outros') DEFAULT 'Outros';
-- ALTER TABLE funcionarios ADD COLUMN status ENUM('ativo', 'afastado', 'demitido') DEFAULT 'ativo';
-- ALTER TABLE funcionarios ADD COLUMN data_demissao DATE NULL;
-- ALTER TABLE funcionarios ADD COLUMN motivo_demissao TEXT NULL;
-- ALTER TABLE obras ADD COLUMN tipo ENUM('Casa', 'Apartamento', 'Reforma', 'Condomínio', 'Comercial', 'Industrial', 'Galpão', 'Outros') DEFAULT 'Outros';
-- ALTER TABLE obras ADD COLUMN descricao TEXT NULL;
-- ALTER TABLE obras ADD COLUMN responsavel_id INT NULL;
-- ALTER TABLE obras ADD COLUMN equipe_id INT NULL;
-- ALTER TABLE etapas ADD COLUMN descricao TEXT NULL;
-- ALTER TABLE etapas ADD COLUMN observacoes TEXT NULL;
-- ALTER TABLE funcionarios ADD COLUMN funcao_id INT NULL;
-- ALTER TABLE funcionarios ADD COLUMN equipe_id INT NULL;
-- ALTER TABLE funcionarios ADD COLUMN ausente BOOLEAN DEFAULT FALSE;
-- ALTER TABLE notificacoes ADD COLUMN titulo VARCHAR(100) NULL;
-- ALTER TABLE notificacoes ADD COLUMN prioridade ENUM('baixa', 'media', 'alta') DEFAULT 'baixa';
-- ALTER TABLE notificacoes ADD COLUMN modulo_origem VARCHAR(50) NULL;
-- ALTER TABLE notificacoes ADD COLUMN link VARCHAR(255) NULL;
-- ALTER TABLE notificacoes ADD COLUMN data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP;
