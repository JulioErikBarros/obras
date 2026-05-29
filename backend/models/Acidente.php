<?php
class Acidente {
    private $conn; private $table_name = "acidentes_historico";

    public function __construct($db) { $this->conn = $db; }

    public function resetar($descricao = "") {
        $query = "INSERT INTO " . $this->table_name . " SET data_registro = CURDATE(), dias_sem_acidentes = 0, houve_acidente = 1, descricao = :descricao";
        $stmt = $this->conn->prepare($query);
        $descricao = htmlspecialchars(strip_tags($descricao));
        $stmt->bindParam(":descricao", $descricao);
        return $stmt->execute();
    }
}
?>