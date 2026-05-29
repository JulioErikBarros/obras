<?php
class Material {
    private $conn; private $table_name = "materiais";
    public $id, $obra_id, $nome, $quantidade, $unidade_medida, $tipo_movimentacao, $categoria;
    public function __construct($db) { $this->conn = $db; }
    public function read($filtros = []) {
        $query = "SELECT m.*, o.nome as obra_nome FROM " . $this->table_name . " m LEFT JOIN obras o ON m.obra_id = o.id WHERE 1=1";

        if (!empty($filtros['categoria'])) {
            $query .= " AND m.categoria = :categoria";
        }
        if (!empty($filtros['search'])) {
            $query .= " AND m.nome LIKE :search";
        }

        $query .= " ORDER BY m.id DESC";

        $stmt = $this->conn->prepare($query);
        if (!empty($filtros['categoria'])) { $stmt->bindValue(':categoria', $filtros['categoria']); }
        if (!empty($filtros['search'])) { $stmt->bindValue(':search', "%".$filtros['search']."%"); }

        $stmt->execute(); return $stmt;
    }
    public function checkSaldoSuficiente() {
        $query = "SELECT SUM(CASE WHEN tipo_movimentacao = 'entrada' THEN quantidade ELSE 0 END) - SUM(CASE WHEN tipo_movimentacao = 'saida' THEN quantidade ELSE 0 END) as saldo FROM " . $this->table_name . " WHERE obra_id = :obra_id AND nome = :nome";
        $stmt = $this->conn->prepare($query); $stmt->bindParam(":obra_id", $this->obra_id); $stmt->bindParam(":nome", $this->nome); $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC); $saldo = $row['saldo'] ?? 0;
        return $saldo >= $this->quantidade;
    }
    public function create() {
        $this->nome = !empty($this->nome) ? htmlspecialchars(strip_tags($this->nome)) : '';
        if ($this->tipo_movimentacao == 'saida' && !$this->checkSaldoSuficiente()) { return false; }
        $query = "INSERT INTO " . $this->table_name . " SET obra_id=:obra_id, nome=:nome, categoria=:categoria, quantidade=:quantidade, unidade_medida=:unidade_medida, tipo_movimentacao=:tipo_movimentacao";
        $stmt = $this->conn->prepare($query);
        $this->obra_id = !empty($this->obra_id) ? htmlspecialchars(strip_tags($this->obra_id)) : ''; 
        $this->categoria = !empty($this->categoria) ? htmlspecialchars(strip_tags($this->categoria)) : ''; 
        $this->quantidade = !empty($this->quantidade) ? htmlspecialchars(strip_tags($this->quantidade)) : 0; 
        $this->unidade_medida = !empty($this->unidade_medida) ? htmlspecialchars(strip_tags($this->unidade_medida)) : ''; 
        $this->tipo_movimentacao = !empty($this->tipo_movimentacao) ? htmlspecialchars(strip_tags($this->tipo_movimentacao)) : '';
        $stmt->bindParam(":obra_id", $this->obra_id); $stmt->bindParam(":nome", $this->nome); $stmt->bindParam(":categoria", $this->categoria); $stmt->bindParam(":quantidade", $this->quantidade); $stmt->bindParam(":unidade_medida", $this->unidade_medida); $stmt->bindParam(":tipo_movimentacao", $this->tipo_movimentacao);
        return $stmt->execute();
    }
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query); $this->id = htmlspecialchars(strip_tags($this->id)); $stmt->bindParam(1, $this->id); return $stmt->execute();
    }
}
?>
