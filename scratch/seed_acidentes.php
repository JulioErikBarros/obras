<?php
require_once 'backend/config/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Inserir registro inicial se não houver
    $db->exec("INSERT IGNORE INTO acidentes_historico (id, data_registro, dias_sem_acidentes) VALUES (1, CURDATE(), 0)");
    
    // Inserir ocorrências de teste com data retroativa
    $db->exec("INSERT IGNORE INTO acidentes_historico (id, data_registro, dias_sem_acidentes, houve_acidente, descricao) VALUES 
        (2, DATE_SUB(CURDATE(), INTERVAL 45 DAY), 120, 1, 'Colaborador sofreu escoriação leve no joelho após escorregar em piso úmido na área de fundações. Utilizava todos os EPIs obrigatórios.'),
        (3, DATE_SUB(CURDATE(), INTERVAL 15 DAY), 30, 1, 'Corte superficial no antebraço direito durante manuseio de chapas metálicas de cobertura. Atendimento ambulatorial prestado de forma imediata.')");
        
    echo "Seed de acidentes executado com sucesso!\n";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
