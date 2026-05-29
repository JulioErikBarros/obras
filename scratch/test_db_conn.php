<?php
try {
    $p = new PDO('mysql:host=localhost', 'root', '');
    echo "MySQL rodando! Conexão sem senha estabelecida.\n";
    
    // Tenta criar banco de testes
    $p->exec("CREATE DATABASE IF NOT EXISTS controle_obras_test");
    echo "Banco controle_obras_test criado com sucesso!\n";
} catch(Exception $e) {
    echo "Erro de conexão MySQL: " . $e->getMessage() . "\n";
}
?>
