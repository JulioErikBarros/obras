<?php
class Funcao {
    private $conn; private $table_name = "funcoes";
    public $id, $nome, $descricao, $salario_base, $horario_padrao, $setor, $permissao_sugerida, $status;
    public function __construct($db) { $this->conn = $db; }
    public function read($filtros = []) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE 1=1";
        if (!empty($filtros['status'])) { $query .= " AND status = :status"; }
        if (!empty($filtros['search'])) { $query .= " AND nome LIKE :search"; }
        $query .= " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        if (!empty($filtros['status'])) { $stmt->bindValue(':status', $filtros['status']); }
        if (!empty($filtros['search'])) { $stmt->bindValue(':search', "%".$filtros['search']."%"); }
        $stmt->execute(); return $stmt;
    }
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET nome=:nome, descricao=:descricao, salario_base=:salario_base, horario_padrao=:horario_padrao, setor=:setor, permissao_sugerida=:permissao_sugerida, status=:status";
        $stmt = $this->conn->prepare($query);
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->descricao = htmlspecialchars(strip_tags($this->descricao));
        $this->salario_base = htmlspecialchars(strip_tags($this->salario_base));
        $this->horario_padrao = htmlspecialchars(strip_tags($this->horario_padrao));
        $this->setor = htmlspecialchars(strip_tags($this->setor));
        $this->permissao_sugerida = htmlspecialchars(strip_tags($this->permissao_sugerida));
        $this->status = htmlspecialchars(strip_tags($this->status));

        $stmt->bindParam(":nome", $this->nome); $stmt->bindParam(":descricao", $this->descricao); $stmt->bindParam(":salario_base", $this->salario_base); $stmt->bindParam(":horario_padrao", $this->horario_padrao); $stmt->bindParam(":setor", $this->setor); $stmt->bindParam(":permissao_sugerida", $this->permissao_sugerida); $stmt->bindParam(":status", $this->status);
        return $stmt->execute();
    }
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET nome=:nome, descricao=:descricao, salario_base=:salario_base, horario_padrao=:horario_padrao, setor=:setor, permissao_sugerida=:permissao_sugerida, status=:status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->descricao = htmlspecialchars(strip_tags($this->descricao));
        $this->salario_base = htmlspecialchars(strip_tags($this->salario_base));
        $this->horario_padrao = htmlspecialchars(strip_tags($this->horario_padrao));
        $this->setor = htmlspecialchars(strip_tags($this->setor));
        $this->permissao_sugerida = htmlspecialchars(strip_tags($this->permissao_sugerida));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":nome", $this->nome); $stmt->bindParam(":descricao", $this->descricao); $stmt->bindParam(":salario_base", $this->salario_base); $stmt->bindParam(":horario_padrao", $this->horario_padrao); $stmt->bindParam(":setor", $this->setor); $stmt->bindParam(":permissao_sugerida", $this->permissao_sugerida); $stmt->bindParam(":status", $this->status); $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }
}
?>
