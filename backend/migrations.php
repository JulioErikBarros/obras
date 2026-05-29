<?php
require_once 'config/Database.php';
$db = (new Database())->getConnection();

if (!$db) {
    die("Falha na conexão com o banco de dados.");
}

echo "Iniciando migrações de banco...\n";

// 1. Adicionar etapa_id na tabela equipes
try {
    $db->exec("ALTER TABLE equipes ADD COLUMN etapa_id INT NULL");
    echo "Coluna 'etapa_id' adicionada com sucesso na tabela equipes.\n";
} catch(Exception $e) {
    echo "Aviso (etapa_id equipes): " . $e->getMessage() . "\n";
}

try {
    $db->exec("ALTER TABLE equipes ADD CONSTRAINT fk_equipe_etapa FOREIGN KEY (etapa_id) REFERENCES etapas(id) ON DELETE SET NULL");
    echo "Chave estrangeira fk_equipe_etapa adicionada com sucesso.\n";
} catch(Exception $e) {
    echo "Aviso (fk_equipe_etapa): " . $e->getMessage() . "\n";
}

// 2. Adicionar equipe_id na tabela etapas
try {
    $db->exec("ALTER TABLE etapas ADD COLUMN equipe_id INT NULL");
    echo "Coluna 'equipe_id' adicionada com sucesso na tabela etapas.\n";
} catch(Exception $e) {
    echo "Aviso (equipe_id etapas): " . $e->getMessage() . "\n";
}

try {
    $db->exec("ALTER TABLE etapas ADD CONSTRAINT fk_etapa_equipe FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE SET NULL");
    echo "Chave estrangeira fk_etapa_equipe adicionada com sucesso.\n";
} catch(Exception $e) {
    echo "Aviso (fk_etapa_equipe): " . $e->getMessage() . "\n";
}

// 3. Criar tabela tipos_obra
try {
    $db->exec("CREATE TABLE IF NOT EXISTS tipos_obra (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Tabela 'tipos_obra' criada ou já existente.\n";
} catch(Exception $e) {
    echo "Erro ao criar tabela 'tipos_obra': " . $e->getMessage() . "\n";
}

// 4. Criar tabela etapas_predefinidas
try {
    $db->exec("CREATE TABLE IF NOT EXISTS etapas_predefinidas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tipo_obra_id INT NOT NULL,
        nome VARCHAR(255) NOT NULL,
        descricao TEXT NULL,
        ordem INT NOT NULL DEFAULT 0,
        status_ativo TINYINT(1) NOT NULL DEFAULT 1,
        FOREIGN KEY (tipo_obra_id) REFERENCES tipos_obra(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Tabela 'etapas_predefinidas' criada ou já existente.\n";
} catch(Exception $e) {
    echo "Erro ao criar tabela 'etapas_predefinidas': " . $e->getMessage() . "\n";
}

// 5. Inserir tipos de obra e etapas pré-definidas se vazias
try {
    $count = $db->query("SELECT COUNT(*) FROM tipos_obra")->fetchColumn();
    if ($count == 0) {
        $db->exec("INSERT INTO tipos_obra (id, nome) VALUES 
            (1, 'Casa'),
            (2, 'Apartamento'),
            (3, 'Reforma'),
            (4, 'Condomínio'),
            (5, 'Comercial'),
            (6, 'Industrial'),
            (7, 'Galpão'),
            (8, 'Outros')");
        echo "Tipos de obra padrão inseridos.\n";
        
        $db->exec("INSERT INTO etapas_predefinidas (tipo_obra_id, nome, descricao, ordem) VALUES
            -- Casa
            (1, 'Fundação', 'Fundação e base da estrutura', 1),
            (1, 'Estrutura', 'Pilares, vigas e lajes', 2),
            (1, 'Alvenaria', 'Paredes e fechamentos', 3),
            (1, 'Cobertura', 'Telhado e impermeabilização', 4),
            (1, 'Elétrica', 'Instalações elétricas', 5),
            (1, 'Hidráulica', 'Instalações hidráulicas e sanitárias', 6),
            (1, 'Acabamento', 'Revestimentos e gesso', 7),
            (1, 'Pintura', 'Pintura interna e externa', 8),
            (1, 'Entrega', 'Limpeza final e entrega das chaves', 9),

            -- Apartamento
            (2, 'Fundação', 'Fundação e base da estrutura', 1),
            (2, 'Estrutura', 'Pilares, vigas e lajes', 2),
            (2, 'Alvenaria', 'Paredes e fechamentos', 3),
            (2, 'Cobertura', 'Telhado e impermeabilização', 4),
            (2, 'Elétrica', 'Instalações elétricas', 5),
            (2, 'Hidráulica', 'Instalações hidráulicas e sanitárias', 6),
            (2, 'Acabamento', 'Revestimentos e gesso', 7),
            (2, 'Pintura', 'Pintura interna e externa', 8),
            (2, 'Entrega', 'Limpeza final e entrega das chaves', 9),

            -- Reforma
            (3, 'Avaliação inicial', 'Visita técnica e orçamento detalhado', 1),
            (3, 'Demolição', 'Remoção de paredes e revestimentos antigos', 2),
            (3, 'Adequações', 'Modificações estruturais leves', 3),
            (3, 'Elétrica', 'Instalações elétricas e iluminação', 4),
            (3, 'Hidráulica', 'Instalações hidráulicas', 5),
            (3, 'Acabamento', 'Revestimentos e gesso', 6),
            (3, 'Limpeza final', 'Remoção de entulho e limpeza do local', 7),
            (3, 'Entrega', 'Vistoria final e entrega do ambiente', 8),

            -- Condomínio
            (4, 'Fundação', 'Fundação e base da estrutura', 1),
            (4, 'Estrutura', 'Pilares, vigas e lajes', 2),
            (4, 'Alvenaria', 'Paredes e fechamentos', 3),
            (4, 'Cobertura', 'Telhado e impermeabilização', 4),
            (4, 'Elétrica', 'Instalações elétricas', 5),
            (4, 'Hidráulica', 'Instalações hidráulicas e sanitárias', 6),
            (4, 'Acabamento', 'Revestimentos e gesso', 7),
            (4, 'Pintura', 'Pintura interna e externa', 8),
            (4, 'Entrega', 'Limpeza final e entrega das chaves', 9),

            -- Comercial
            (5, 'Terraplanagem', 'Preparação do terreno', 1),
            (5, 'Fundação', 'Estacas e blocos de fundação', 2),
            (5, 'Estrutura Metálica/Concreto', 'Montagem da estrutura principal', 3),
            (5, 'Fechamento', 'Paredes externas e vidros', 4),
            (5, 'Piso Industrial', 'Concretagem e polimento do piso', 5),
            (5, 'Instalações', 'Elétrica, hidráulica e ar condicionado', 6),
            (5, 'Acabamento', 'Divisórias, pintura e detalhes corporativos', 7),
            (5, 'Entrega', 'Vistoria e habite-se', 8),

            -- Industrial
            (6, 'Terraplanagem', 'Preparação do terreno', 1),
            (6, 'Fundação', 'Estacas e blocos de fundação', 2),
            (6, 'Estrutura Metálica/Concreto', 'Montagem da estrutura principal', 3),
            (6, 'Fechamento', 'Paredes externas e vidros', 4),
            (6, 'Piso Industrial', 'Concretagem e polimento do piso', 5),
            (6, 'Instalações', 'Elétrica, hidráulica e ar condicionado', 6),
            (6, 'Acabamento', 'Divisórias, pintura e detalhes corporativos', 7),
            (6, 'Entrega', 'Vistoria e habite-se', 8),

            -- Galpão
            (7, 'Terraplanagem', 'Preparação do terreno', 1),
            (7, 'Fundação', 'Estacas e blocos de fundação', 2),
            (7, 'Estrutura Metálica/Concreto', 'Montagem da estrutura principal', 3),
            (7, 'Fechamento', 'Paredes externas e vidros', 4),
            (7, 'Piso Industrial', 'Concretagem e polimento do piso', 5),
            (7, 'Instalações', 'Elétrica, hidráulica e ar condicionado', 6),
            (7, 'Acabamento', 'Divisórias, pintura e detalhes corporativos', 7),
            (7, 'Entrega', 'Vistoria e habite-se', 8),

            -- Outros
            (8, 'Planejamento', 'Definição de metas e escopo', 1),
            (8, 'Execução', 'Desenvolvimento e acompanhamento', 2),
            (8, 'Finalização', 'Entrega e encerramento', 3)");
        echo "Etapas pré-definidas inseridas.\n";
    }
} catch(Exception $e) {
    echo "Erro ao semear tipos e etapas pré-definidas: " . $e->getMessage() . "\n";
}

// 6. Atualizar a tabela etapas com novos campos
try {
    $db->exec("ALTER TABLE etapas ADD COLUMN percentual INT DEFAULT 0");
    echo "Coluna 'percentual' adicionada com sucesso na tabela etapas.\n";
} catch(Exception $e) {
    echo "Aviso (percentual etapas): " . $e->getMessage() . "\n";
}

try {
    $db->exec("ALTER TABLE etapas ADD COLUMN data_fim_prevista DATE NULL");
    echo "Coluna 'data_fim_prevista' adicionada com sucesso na tabela etapas.\n";
    
    // Migrar dados da antiga data_conclusao se aplicável
    try {
        $db->exec("UPDATE etapas SET data_fim_prevista = data_conclusao WHERE data_fim_prevista IS NULL AND data_conclusao IS NOT NULL");
        echo "Dados de 'data_conclusao' migrados para 'data_fim_prevista'.\n";
    } catch(Exception $e) {
        // Ignora erro se data_conclusao não existir
    }
} catch(Exception $e) {
    echo "Aviso (data_fim_prevista etapas): " . $e->getMessage() . "\n";
}

try {
    $db->exec("ALTER TABLE etapas ADD COLUMN responsavel_id INT NULL");
    echo "Coluna 'responsavel_id' adicionada com sucesso na tabela etapas.\n";
} catch(Exception $e) {
    echo "Aviso (responsavel_id etapas): " . $e->getMessage() . "\n";
}

try {
    $db->exec("ALTER TABLE etapas ADD CONSTRAINT fk_etapas_responsavel FOREIGN KEY (responsavel_id) REFERENCES funcionarios(id) ON DELETE SET NULL");
    echo "Chave estrangeira fk_etapas_responsavel adicionada com sucesso.\n";
} catch(Exception $e) {
    echo "Aviso (fk_etapas_responsavel): " . $e->getMessage() . "\n";
}

try {
    $db->exec("ALTER TABLE etapas ADD COLUMN ordem INT DEFAULT 0");
    echo "Coluna 'ordem' adicionada com sucesso na tabela etapas.\n";
} catch(Exception $e) {
    echo "Aviso (ordem etapas): " . $e->getMessage() . "\n";
}

try {
    $db->exec("ALTER TABLE etapas ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "Coluna 'created_at' adicionada com sucesso na tabela etapas.\n";
} catch(Exception $e) {
    echo "Aviso (created_at etapas): " . $e->getMessage() . "\n";
}

try {
    $db->exec("ALTER TABLE etapas ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    echo "Coluna 'updated_at' adicionada com sucesso na tabela etapas.\n";
} catch(Exception $e) {
    echo "Aviso (updated_at etapas): " . $e->getMessage() . "\n";
}

// 7. Sincronizar o progresso existente das etapas de obras
try {
    // Se a etapa está concluída, garantir que percentual está em 100%
    $db->exec("UPDATE etapas SET percentual = 100 WHERE status = 'Concluída' AND percentual < 100");
    echo "Sincronização de etapas concluídas concluída.\n";
} catch(Exception $e) {
    echo "Aviso (sincronizar progresso etapas): " . $e->getMessage() . "\n";
}

echo "Migrações concluídas!\n";
?>
