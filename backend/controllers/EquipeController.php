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
