<?php
class Documento {
    private $conn; private $table_name = "documentos";
    public $id, $obra_id, $nome, $tipo, $caminho_arquivo;
    public function __construct($db) { $this->conn = $db; }
    public function read() {
        $query = "SELECT d.*, o.nome as obra_nome FROM " . $this->table_name . " d LEFT JOIN obras o ON d.obra_id = o.id ORDER BY d.id DESC";
        $stmt = $this->conn->prepare($query); $stmt->execute(); return $stmt;
    }
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET obra_id=:obra_id, nome=:nome, tipo=:tipo, caminho_arquivo=:caminho_arquivo";
        $stmt = $this->conn->prepare($query);
        $this->obra_id = htmlspecialchars(strip_tags($this->obra_id)); $this->nome = htmlspecialchars(strip_tags($this->nome)); $this->tipo = htmlspecialchars(strip_tags($this->tipo)); $this->caminho_arquivo = htmlspecialchars(strip_tags($this->caminho_arquivo));
        $stmt->bindParam(":obra_id", $this->obra_id); $stmt->bindParam(":nome", $this->nome); $stmt->bindParam(":tipo", $this->tipo); $stmt->bindParam(":caminho_arquivo", $this->caminho_arquivo);
        return $stmt->execute();
    }
    public function delete() {
        $query_select = "SELECT caminho_arquivo FROM " . $this->table_name . " WHERE id = ?";
        $stmt_sel = $this->conn->prepare($query_select); $stmt_sel->bindParam(1, $this->id); $stmt_sel->execute();
        $row = $stmt_sel->fetch(PDO::FETCH_ASSOC);
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query); $this->id = htmlspecialchars(strip_tags($this->id)); $stmt->bindParam(1, $this->id);
        if ($stmt->execute()) {
            if ($row && file_exists("../frontend/" . $row['caminho_arquivo'])) { unlink("../frontend/" . $row['caminho_arquivo']); }
            return true;
        }
        return false;
    }
}
?>
