<?php
class Financeiro {
    private $conn; private $table_name = "financeiro";
    public $id, $obra_id, $tipo, $descricao, $valor, $status, $data_vencimento;
    public function __construct($db) { $this->conn = $db; }
    public function read($filtros = []) {
        $query = "SELECT f.*, o.nome as obra_nome FROM " . $this->table_name . " f LEFT JOIN obras o ON f.obra_id = o.id WHERE 1=1";

        if (!empty($filtros['status'])) {
            $query .= " AND f.status = :status";
        }
        if (!empty($filtros['tipo'])) {
            $query .= " AND f.tipo = :tipo";
        }
        if (!empty($filtros['data_inicio']) && !empty($filtros['data_fim'])) {
            $query .= " AND f.data_vencimento BETWEEN :data_inicio AND :data_fim";
        }

        $query .= " ORDER BY f.id DESC";

        $stmt = $this->conn->prepare($query);
        if (!empty($filtros['status'])) { $stmt->bindValue(':status', $filtros['status']); }
        if (!empty($filtros['tipo'])) { $stmt->bindValue(':tipo', $filtros['tipo']); }
        if (!empty($filtros['data_inicio']) && !empty($filtros['data_fim'])) {
            $stmt->bindValue(':data_inicio', $filtros['data_inicio']);
            $stmt->bindValue(':data_fim', $filtros['data_fim']);
        }

        $stmt->execute(); return $stmt;
    }
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET obra_id=:obra_id, tipo=:tipo, descricao=:descricao, valor=:valor, status=:status, data_vencimento=:data_vencimento";
        $stmt = $this->conn->prepare($query);
        $this->obra_id = htmlspecialchars(strip_tags($this->obra_id)); $this->tipo = htmlspecialchars(strip_tags($this->tipo)); $this->descricao = htmlspecialchars(strip_tags($this->descricao)); $this->valor = htmlspecialchars(strip_tags($this->valor)); $this->status = htmlspecialchars(strip_tags($this->status)); 
        $this->data_vencimento = !empty($this->data_vencimento) ? htmlspecialchars(strip_tags($this->data_vencimento)) : null;
        $stmt->bindParam(":obra_id", $this->obra_id); $stmt->bindParam(":tipo", $this->tipo); $stmt->bindParam(":descricao", $this->descricao); $stmt->bindParam(":valor", $this->valor); $stmt->bindParam(":status", $this->status); $stmt->bindParam(":data_vencimento", $this->data_vencimento);
        return $stmt->execute();
    }
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET obra_id=:obra_id, tipo=:tipo, descricao=:descricao, valor=:valor, status=:status, data_vencimento=:data_vencimento WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $this->obra_id = htmlspecialchars(strip_tags($this->obra_id)); $this->tipo = htmlspecialchars(strip_tags($this->tipo)); $this->descricao = htmlspecialchars(strip_tags($this->descricao)); $this->valor = htmlspecialchars(strip_tags($this->valor)); $this->status = htmlspecialchars(strip_tags($this->status)); 
        $this->data_vencimento = !empty($this->data_vencimento) ? htmlspecialchars(strip_tags($this->data_vencimento)) : null;
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(":obra_id", $this->obra_id); $stmt->bindParam(":tipo", $this->tipo); $stmt->bindParam(":descricao", $this->descricao); $stmt->bindParam(":valor", $this->valor); $stmt->bindParam(":status", $this->status); $stmt->bindParam(":data_vencimento", $this->data_vencimento); $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query); $this->id = htmlspecialchars(strip_tags($this->id)); $stmt->bindParam(1, $this->id); return $stmt->execute();
    }
}
?>
