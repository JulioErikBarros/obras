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
