cat << 'PHP' > backend/models/Funcao.php
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
PHP

cat << 'PHP' > backend/controllers/FuncaoController.php
<?php
class FuncaoController {
    private $db, $funcao;
    public function __construct($db) { $this->db = $db; $this->funcao = new Funcao($db); }
    public function processRequest($method, $id) {
        if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(array("message" => "Não autorizado.")); return; }
        switch ($method) { case 'GET': $this->getFuncoes(); break; case 'POST': $this->createFuncao(); break; case 'PUT': $this->updateFuncao($id); break; default: http_response_code(405); echo json_encode(array("message" => "Método não permitido.")); break; }
    }
    private function getFuncoes() {
        $filtros = ['status' => $_GET['status'] ?? null, 'search' => $_GET['search'] ?? null];
        $stmt = $this->funcao->read($filtros); $funcoes = array(); while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { array_push($funcoes, $row); } http_response_code(200); echo json_encode($funcoes);
    }
    private function createFuncao() {
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->nome)) {
            $this->funcao->nome = $data->nome; $this->funcao->descricao = $data->descricao ?? ''; $this->funcao->salario_base = $data->salario_base ?? 0; $this->funcao->horario_padrao = $data->horario_padrao ?? ''; $this->funcao->setor = $data->setor ?? ''; $this->funcao->permissao_sugerida = $data->permissao_sugerida ?? ''; $this->funcao->status = $data->status ?? 'ativo';
            if ($this->funcao->create()) { http_response_code(201); echo json_encode(["message" => "Criado."]); } else { http_response_code(500); echo json_encode(["message" => "Erro."]); }
        } else { http_response_code(400); echo json_encode(["message" => "Dados incompletos."]); }
    }
    private function updateFuncao($id) {
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($id) && !empty($data->nome)) {
            $this->funcao->id = $id; $this->funcao->nome = $data->nome; $this->funcao->descricao = $data->descricao ?? ''; $this->funcao->salario_base = $data->salario_base ?? 0; $this->funcao->horario_padrao = $data->horario_padrao ?? ''; $this->funcao->setor = $data->setor ?? ''; $this->funcao->permissao_sugerida = $data->permissao_sugerida ?? ''; $this->funcao->status = $data->status ?? 'ativo';
            if ($this->funcao->update()) { http_response_code(200); echo json_encode(["message" => "Atualizado."]); } else { http_response_code(500); echo json_encode(["message" => "Erro."]); }
        } else { http_response_code(400); echo json_encode(["message" => "Dados incompletos."]); }
    }
}
?>
PHP

cat << 'PHP' > backend/models/Equipe.php
<?php
class Equipe {
    private $conn; private $table_name = "equipes";
    public $id, $nome, $responsavel_id, $obra_id, $descricao, $data_criacao, $status;
    public function __construct($db) { $this->conn = $db; }
    public function read($filtros = []) {
        $query = "SELECT e.*, u.nome as responsavel_nome, o.nome as obra_nome FROM " . $this->table_name . " e LEFT JOIN funcionarios u ON e.responsavel_id = u.id LEFT JOIN obras o ON e.obra_id = o.id WHERE 1=1";
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
        $this->data_criacao = htmlspecialchars(strip_tags($this->data_criacao));
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
PHP

cat << 'PHP' > backend/controllers/EquipeController.php
<?php
class EquipeController {
    private $db, $equipe;
    public function __construct($db) { $this->db = $db; $this->equipe = new Equipe($db); }
    public function processRequest($method, $id) {
        if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(array("message" => "Não autorizado.")); return; }
        switch ($method) { case 'GET': $this->getEquipes(); break; case 'POST': $this->createEquipe(); break; case 'PUT': $this->updateEquipe($id); break; default: http_response_code(405); echo json_encode(array("message" => "Método não permitido.")); break; }
    }
    private function getEquipes() {
        $filtros = ['status' => $_GET['status'] ?? null, 'search' => $_GET['search'] ?? null];
        $stmt = $this->equipe->read($filtros); $equipes = array(); while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { array_push($equipes, $row); } http_response_code(200); echo json_encode($equipes);
    }
    private function createEquipe() {
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->nome)) {
            $this->equipe->nome = $data->nome; $this->equipe->responsavel_id = $data->responsavel_id ?? null; $this->equipe->obra_id = $data->obra_id ?? null; $this->equipe->descricao = $data->descricao ?? ''; $this->equipe->status = $data->status ?? 'ativo'; $this->equipe->data_criacao = date('Y-m-d');
            if ($this->equipe->create()) { http_response_code(201); echo json_encode(["message" => "Criado."]); } else { http_response_code(500); echo json_encode(["message" => "Erro."]); }
        } else { http_response_code(400); echo json_encode(["message" => "Dados incompletos."]); }
    }
    private function updateEquipe($id) {
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($id) && !empty($data->nome)) {
            $this->equipe->id = $id; $this->equipe->nome = $data->nome; $this->equipe->responsavel_id = $data->responsavel_id ?? null; $this->equipe->obra_id = $data->obra_id ?? null; $this->equipe->descricao = $data->descricao ?? ''; $this->equipe->status = $data->status ?? 'ativo';
            if ($this->equipe->update()) { http_response_code(200); echo json_encode(["message" => "Atualizado."]); } else { http_response_code(500); echo json_encode(["message" => "Erro."]); }
        } else { http_response_code(400); echo json_encode(["message" => "Dados incompletos."]); }
    }
}
?>
PHP
