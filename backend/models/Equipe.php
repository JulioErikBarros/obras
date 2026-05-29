<?php
class Equipe {
    private $conn; private $table_name = "equipes";
    public $id, $nome, $responsavel_id, $obra_id, $descricao, $data_criacao, $status, $etapa_id;
    public function __construct($db) { $this->conn = $db; }
    public function read($filtros = []) {
        $query = "SELECT e.*, u.nome as responsavel_nome, o.nome as obra_nome, et.nome as etapa_nome, et.status as etapa_status 
                  FROM " . $this->table_name . " e 
                  LEFT JOIN funcionarios u ON e.responsavel_id = u.id 
                  LEFT JOIN obras o ON e.obra_id = o.id 
                  LEFT JOIN etapas et ON e.etapa_id = et.id 
                  WHERE 1=1";
        if (!empty($filtros['status'])) { $query .= " AND e.status = :status"; }
        if (!empty($filtros['search'])) { $query .= " AND e.nome LIKE :search"; }
        $query .= " ORDER BY e.id DESC";
        $stmt = $this->conn->prepare($query);
        if (!empty($filtros['status'])) { $stmt->bindValue(':status', $filtros['status']); }
        if (!empty($filtros['search'])) { $stmt->bindValue(':search', "%".$filtros['search']."%"); }
        $stmt->execute(); return $stmt;
    }
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET nome=:nome, responsavel_id=:responsavel_id, obra_id=:obra_id, descricao=:descricao, data_criacao=:data_criacao, status=:status";
        $stmt = $this->conn->prepare($query);
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->responsavel_id = !empty($this->responsavel_id) ? htmlspecialchars(strip_tags($this->responsavel_id)) : null;
        $this->obra_id = !empty($this->obra_id) ? htmlspecialchars(strip_tags($this->obra_id)) : null;
        $this->descricao = htmlspecialchars(strip_tags($this->descricao));
        $this->data_criacao = !empty($this->data_criacao) ? htmlspecialchars(strip_tags($this->data_criacao)) : null;
        $this->status = htmlspecialchars(strip_tags($this->status));

        $stmt->bindParam(":nome", $this->nome); $stmt->bindParam(":responsavel_id", $this->responsavel_id); $stmt->bindParam(":obra_id", $this->obra_id); $stmt->bindParam(":descricao", $this->descricao); $stmt->bindParam(":data_criacao", $this->data_criacao); $stmt->bindParam(":status", $this->status);
        return $stmt->execute();
    }
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET nome=:nome, responsavel_id=:responsavel_id, obra_id=:obra_id, descricao=:descricao, status=:status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->responsavel_id = !empty($this->responsavel_id) ? htmlspecialchars(strip_tags($this->responsavel_id)) : null;
        $this->obra_id = !empty($this->obra_id) ? htmlspecialchars(strip_tags($this->obra_id)) : null;
        $this->descricao = htmlspecialchars(strip_tags($this->descricao));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":nome", $this->nome); $stmt->bindParam(":responsavel_id", $this->responsavel_id); $stmt->bindParam(":obra_id", $this->obra_id); $stmt->bindParam(":descricao", $this->descricao); $stmt->bindParam(":status", $this->status); $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }
}
?>
