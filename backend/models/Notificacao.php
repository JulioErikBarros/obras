<?php
class Notificacao {
    private $conn; private $table_name = "notificacoes";
    public $id, $usuario_id, $titulo, $mensagem, $tipo, $prioridade, $modulo_origem, $link, $lida, $data_criacao;
    public function __construct($db) { $this->conn = $db; }
    public function read($filtros = []) {
        $query = "SELECT n.*, u.nome as usuario_nome FROM " . $this->table_name . " n LEFT JOIN usuarios u ON n.usuario_id = u.id WHERE n.usuario_id = :usuario_id";

        if (isset($filtros['lida']) && $filtros['lida'] !== '') {
            $query .= " AND n.lida = :lida";
        }
        if (!empty($filtros['prioridade'])) {
            $query .= " AND n.prioridade = :prioridade";
        }
        if (!empty($filtros['tipo'])) {
            $query .= " AND n.tipo = :tipo";
        }

        $query .= " ORDER BY n.lida ASC, n.id DESC"; // Não lidas primeiro
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":usuario_id", $this->usuario_id);

        if (isset($filtros['lida']) && $filtros['lida'] !== '') { $stmt->bindValue(':lida', $filtros['lida']); }
        if (!empty($filtros['prioridade'])) { $stmt->bindValue(':prioridade', $filtros['prioridade']); }
        if (!empty($filtros['tipo'])) { $stmt->bindValue(':tipo', $filtros['tipo']); }

        $stmt->execute(); return $stmt;
    }

    public function unreadCount() {
        $query = "SELECT COUNT(*) as unread FROM " . $this->table_name . " WHERE usuario_id = :usuario_id AND lida = 0";
        $stmt = $this->conn->prepare($query); $stmt->bindParam(":usuario_id", $this->usuario_id); $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['unread'] ?? 0;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET usuario_id=:usuario_id, titulo=:titulo, mensagem=:mensagem, tipo=:tipo, prioridade=:prioridade, modulo_origem=:modulo_origem, link=:link";
        $stmt = $this->conn->prepare($query);
        $this->usuario_id = !empty($this->usuario_id) ? htmlspecialchars(strip_tags($this->usuario_id)) : null;
        $this->titulo = !empty($this->titulo) ? htmlspecialchars(strip_tags($this->titulo)) : '';
        $this->mensagem = !empty($this->mensagem) ? htmlspecialchars(strip_tags($this->mensagem)) : '';
        $this->tipo = !empty($this->tipo) ? htmlspecialchars(strip_tags($this->tipo)) : '';
        $this->prioridade = !empty($this->prioridade) ? htmlspecialchars(strip_tags($this->prioridade)) : 'baixa';
        $this->modulo_origem = !empty($this->modulo_origem) ? htmlspecialchars(strip_tags($this->modulo_origem)) : '';
        $this->link = !empty($this->link) ? htmlspecialchars(strip_tags($this->link)) : '';

        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":mensagem", $this->mensagem);
        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":prioridade", $this->prioridade);
        $stmt->bindParam(":modulo_origem", $this->modulo_origem);
        $stmt->bindParam(":link", $this->link);
        return $stmt->execute();
    }

    public function markAsRead() {
        $query = "UPDATE " . $this->table_name . " SET lida = 1 WHERE id = :id";
        $stmt = $this->conn->prepare($query); $this->id = htmlspecialchars(strip_tags($this->id)); $stmt->bindParam(":id", $this->id); return $stmt->execute();
    }

    public function markAllAsRead() {
        $query = "UPDATE " . $this->table_name . " SET lida = 1 WHERE usuario_id = :usuario_id";
        $stmt = $this->conn->prepare($query); $stmt->bindParam(":usuario_id", $this->usuario_id); return $stmt->execute();
    }
}
?>
