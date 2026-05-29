<?php
class Funcionario {
    private $conn; private $table_name = "funcionarios";
    public $id, $nome, $funcao_id, $equipe_id, $status, $data_admissao, $data_demissao, $motivo_demissao;
    public function __construct($db) { $this->conn = $db; }
    public function read($filtros = []) {
        $query = "SELECT f.*, func.nome as funcao_nome, eq.nome as equipe_nome FROM " . $this->table_name . " f
                  LEFT JOIN funcoes func ON f.funcao_id = func.id
                  LEFT JOIN equipes eq ON f.equipe_id = eq.id
                  WHERE 1=1";

        if (!empty($filtros['status'])) {
            $query .= " AND f.status = :status";
        }
        if (!empty($filtros['search'])) {
            $query .= " AND f.nome LIKE :search";
        }
        if (!empty($filtros['equipe'])) {
            $query .= " AND eq.nome LIKE :equipe";
        }
        if (!empty($filtros['funcao'])) {
            $query .= " AND func.nome LIKE :funcao";
        }
        if (!empty($filtros['data_admissao'])) {
            $query .= " AND f.data_admissao = :data_admissao";
        }

        $query .= " ORDER BY f.id DESC";

        $stmt = $this->conn->prepare($query);

        if (!empty($filtros['status'])) { $stmt->bindValue(':status', $filtros['status']); }
        if (!empty($filtros['search'])) { $stmt->bindValue(':search', "%".$filtros['search']."%"); }
        if (!empty($filtros['equipe'])) { $stmt->bindValue(':equipe', "%".$filtros['equipe']."%"); }
        if (!empty($filtros['funcao'])) { $stmt->bindValue(':funcao', "%".$filtros['funcao']."%"); }
        if (!empty($filtros['data_admissao'])) { $stmt->bindValue(':data_admissao', $filtros['data_admissao']); }

        $stmt->execute(); return $stmt;
    }
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET nome=:nome, funcao_id=:funcao_id, equipe_id=:equipe_id, data_admissao=:data_admissao, status=:status";
        $stmt = $this->conn->prepare($query);
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->funcao_id = !empty($this->funcao_id) ? htmlspecialchars(strip_tags($this->funcao_id)) : null;
        $this->equipe_id = !empty($this->equipe_id) ? htmlspecialchars(strip_tags($this->equipe_id)) : null;
        $this->data_admissao = !empty($this->data_admissao) ? htmlspecialchars(strip_tags($this->data_admissao)) : null;
        $this->status = htmlspecialchars(strip_tags($this->status));

        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":funcao_id", $this->funcao_id);
        $stmt->bindParam(":equipe_id", $this->equipe_id);
        $stmt->bindParam(":data_admissao", $this->data_admissao);
        $stmt->bindParam(":status", $this->status);
        if ($stmt->execute()) return $this->conn->lastInsertId();
        return false;
    }
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET nome=:nome, funcao_id=:funcao_id, equipe_id=:equipe_id, data_admissao=:data_admissao, status=:status, data_demissao=:data_demissao, motivo_demissao=:motivo_demissao WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->funcao_id = !empty($this->funcao_id) ? htmlspecialchars(strip_tags($this->funcao_id)) : null;
        $this->equipe_id = !empty($this->equipe_id) ? htmlspecialchars(strip_tags($this->equipe_id)) : null;
        $this->data_admissao = !empty($this->data_admissao) ? htmlspecialchars(strip_tags($this->data_admissao)) : null;
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->data_demissao = !empty($this->data_demissao) ? htmlspecialchars(strip_tags($this->data_demissao)) : null;
        $this->motivo_demissao = !empty($this->motivo_demissao) ? htmlspecialchars(strip_tags($this->motivo_demissao)) : null;
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":funcao_id", $this->funcao_id);
        $stmt->bindParam(":equipe_id", $this->equipe_id);
        $stmt->bindParam(":data_admissao", $this->data_admissao);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":data_demissao", $this->data_demissao);
        $stmt->bindParam(":motivo_demissao", $this->motivo_demissao);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query); $this->id = htmlspecialchars(strip_tags($this->id)); $stmt->bindParam(1, $this->id); return $stmt->execute();
    }
}
?>
