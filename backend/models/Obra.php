<?php
class Obra {
    private $conn;
    private $table_name = "obras";
    public $id, $nome, $tipo, $descricao, $status, $endereco, $responsavel_id, $equipe_id, $data_inicio, $data_fim_prevista, $percentual_concluido;
    public function __construct($db) { $this->conn = $db; }
    public function read($filtros = []) {
        $query = "SELECT o.*, f.nome as responsavel_nome, e.nome as equipe_nome FROM " . $this->table_name . " o
                  LEFT JOIN funcionarios f ON o.responsavel_id = f.id
                  LEFT JOIN equipes e ON o.equipe_id = e.id
                  WHERE 1=1";

        if (!empty($filtros['status'])) {
            $query .= " AND o.status = :status";
        }
        if (!empty($filtros['search'])) {
            $query .= " AND o.nome LIKE :search";
        }

        $query .= " ORDER BY o.id DESC";

        $stmt = $this->conn->prepare($query);
        if (!empty($filtros['status'])) { $stmt->bindValue(':status', $filtros['status']); }
        if (!empty($filtros['search'])) { $stmt->bindValue(':search', "%".$filtros['search']."%"); }

        $stmt->execute(); return $stmt;
    }
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET nome=:nome, tipo=:tipo, descricao=:descricao, status=:status, endereco=:endereco, responsavel_id=:responsavel_id, data_inicio=:data_inicio, data_fim_prevista=:data_fim_prevista, percentual_concluido=:percentual_concluido";
        $stmt = $this->conn->prepare($query);
        $this->nome = !empty($this->nome) ? htmlspecialchars(strip_tags($this->nome)) : '';
        $this->tipo = !empty($this->tipo) ? htmlspecialchars(strip_tags($this->tipo)) : '';
        $this->descricao = !empty($this->descricao) ? htmlspecialchars(strip_tags($this->descricao)) : '';
        $this->status = !empty($this->status) ? htmlspecialchars(strip_tags($this->status)) : 'Em planejamento';
        $this->endereco = !empty($this->endereco) ? htmlspecialchars(strip_tags($this->endereco)) : '';
        $this->responsavel_id = !empty($this->responsavel_id) ? htmlspecialchars(strip_tags($this->responsavel_id)) : null;
        $this->data_inicio = !empty($this->data_inicio) ? htmlspecialchars(strip_tags($this->data_inicio)) : null;
        $this->data_fim_prevista = !empty($this->data_fim_prevista) ? htmlspecialchars(strip_tags($this->data_fim_prevista)) : null;
        $this->percentual_concluido = isset($this->percentual_concluido) ? htmlspecialchars(strip_tags($this->percentual_concluido)) : 0;

        $stmt->bindParam(":nome", $this->nome); 
        $stmt->bindParam(":tipo", $this->tipo); 
        $stmt->bindParam(":descricao", $this->descricao); 
        $stmt->bindParam(":status", $this->status); 
        $stmt->bindParam(":endereco", $this->endereco); 
        $stmt->bindParam(":responsavel_id", $this->responsavel_id); 
        $stmt->bindParam(":data_inicio", $this->data_inicio); 
        $stmt->bindParam(":data_fim_prevista", $this->data_fim_prevista); 
        $stmt->bindParam(":percentual_concluido", $this->percentual_concluido);
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET nome=:nome, tipo=:tipo, descricao=:descricao, status=:status, endereco=:endereco, responsavel_id=:responsavel_id, data_inicio=:data_inicio, data_fim_prevista=:data_fim_prevista, percentual_concluido=:percentual_concluido WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $this->nome = !empty($this->nome) ? htmlspecialchars(strip_tags($this->nome)) : '';
        $this->tipo = !empty($this->tipo) ? htmlspecialchars(strip_tags($this->tipo)) : '';
        $this->descricao = !empty($this->descricao) ? htmlspecialchars(strip_tags($this->descricao)) : '';
        $this->status = !empty($this->status) ? htmlspecialchars(strip_tags($this->status)) : 'Em planejamento';
        $this->endereco = !empty($this->endereco) ? htmlspecialchars(strip_tags($this->endereco)) : '';
        $this->responsavel_id = !empty($this->responsavel_id) ? htmlspecialchars(strip_tags($this->responsavel_id)) : null;
        $this->data_inicio = !empty($this->data_inicio) ? htmlspecialchars(strip_tags($this->data_inicio)) : null;
        $this->data_fim_prevista = !empty($this->data_fim_prevista) ? htmlspecialchars(strip_tags($this->data_fim_prevista)) : null;
        $this->percentual_concluido = isset($this->percentual_concluido) ? htmlspecialchars(strip_tags($this->percentual_concluido)) : 0;
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":nome", $this->nome); 
        $stmt->bindParam(":tipo", $this->tipo); 
        $stmt->bindParam(":descricao", $this->descricao); 
        $stmt->bindParam(":status", $this->status); 
        $stmt->bindParam(":endereco", $this->endereco); 
        $stmt->bindParam(":responsavel_id", $this->responsavel_id); 
        $stmt->bindParam(":data_inicio", $this->data_inicio); 
        $stmt->bindParam(":data_fim_prevista", $this->data_fim_prevista); 
        $stmt->bindParam(":percentual_concluido", $this->percentual_concluido); 
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query); $this->id = htmlspecialchars(strip_tags($this->id)); $stmt->bindParam(1, $this->id); return $stmt->execute();
    }
}
?>
