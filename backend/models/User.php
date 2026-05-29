<?php
class User {
    private $conn;
    private $table_name = "usuarios";
    public $id;
    public $nome;
    public $email;
    public $senha;
    public $nivel_acesso;
    public $status;
    public $tema_preferencia;
    public function __construct($db) { $this->conn = $db; }
    public function login() {
        $query = "SELECT id, nome, email, senha, nivel_acesso, status, tema_preferencia FROM " . $this->table_name . " WHERE email = ? AND status = 'ativo' LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $this->email = htmlspecialchars(strip_tags($this->email));
        $stmt->bindParam(1, $this->email);
        $stmt->execute();
        return $stmt;
    }
    public function read() {
        $query = "SELECT id, nome, email, nivel_acesso, status, tema_preferencia FROM " . $this->table_name . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET nome=:nome, email=:email, senha=:senha, nivel_acesso=:nivel_acesso, status=:status";
        $stmt = $this->conn->prepare($query);
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->senha = password_hash($this->senha, PASSWORD_BCRYPT);
        $this->nivel_acesso = htmlspecialchars(strip_tags($this->nivel_acesso));
        $this->status = htmlspecialchars(strip_tags($this->status));

        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":senha", $this->senha);
        $stmt->bindParam(":nivel_acesso", $this->nivel_acesso);
        $stmt->bindParam(":status", $this->status);
        return $stmt->execute();
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET nome=:nome, email=:email, nivel_acesso=:nivel_acesso, status=:status";
        if (!empty($this->senha)) {
            $query .= ", senha=:senha";
        }
        $query .= " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->nivel_acesso = htmlspecialchars(strip_tags($this->nivel_acesso));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":nivel_acesso", $this->nivel_acesso);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

        if (!empty($this->senha)) {
            $this->senha = password_hash($this->senha, PASSWORD_BCRYPT);
            $stmt->bindParam(":senha", $this->senha);
        }
        return $stmt->execute();
    }

    public function updateTema() {
        $query = "UPDATE " . $this->table_name . " SET tema_preferencia=:tema_preferencia WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $this->tema_preferencia = htmlspecialchars(strip_tags($this->tema_preferencia));
        $stmt->bindParam(":tema_preferencia", $this->tema_preferencia);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }
}
?>
