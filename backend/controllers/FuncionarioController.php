<?php
class FuncionarioController {
    private $db, $funcionario;
    public function __construct($db) { $this->db = $db; $this->funcionario = new Funcionario($db); }
    public function processRequest($method, $id, $resource = 'funcionarios') {
        if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(array("message" => "Não autorizado.")); return; }
        if ($resource === 'demitir' && $method === 'POST') {
            $this->demitirFuncionario($id);
            return;
        }

        switch ($method) { case 'GET': $this->getFuncionarios(); break; case 'POST': $this->createFuncionario(); break; case 'PUT': $this->updateFuncionario($id); break; case 'DELETE': $this->deleteFuncionario($id); break; default: http_response_code(405); echo json_encode(array("message" => "Método não permitido.")); break; }
    }
    private function getFuncionarios() {
        $filtros = [
            'search' => $_GET['search'] ?? null,
            'status' => $_GET['status'] ?? null,
            'equipe' => $_GET['equipe'] ?? null,
            'funcao' => $_GET['funcao'] ?? null,
            'data_admissao' => $_GET['data_admissao'] ?? null
        ];
        $stmt = $this->funcionario->read($filtros); $funcionarios = array(); while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { array_push($funcionarios, $row); } http_response_code(200); echo json_encode($funcionarios);
    }
    private function createFuncionario() {
        $data = json_decode(file_get_contents("php://input"));
        if (empty($data->nome) || empty($data->funcao_id)) {
            http_response_code(400);
            echo json_encode(array("message" => "Dados incompletos. Informe nome e função pré-cadastrada."));
            return;
        }

        $this->funcionario->nome = $data->nome;
        $this->funcionario->funcao_id = $data->funcao_id;
        $this->funcionario->equipe_id = $data->equipe_id ?? null;
        $this->funcionario->data_admissao = !empty($data->data_admissao) ? $data->data_admissao : null;
        $this->funcionario->status = $data->status ?? 'ativo';

        $func_id = $this->funcionario->create();
        if ($func_id) {
            http_response_code(201); echo json_encode(array("message" => "Criado."));
        } else { http_response_code(500); echo json_encode(array("message" => "Erro.")); }
    }
    private function updateFuncionario($id) {
        $data = json_decode(file_get_contents("php://input"));
        if (empty($id) || empty($data->nome) || empty($data->funcao_id)) {
            http_response_code(400);
            echo json_encode(array("message" => "Dados incompletos. Informe os campos obrigatórios."));
            return;
        }

        $this->funcionario->id = $id;
        $this->funcionario->nome = $data->nome;
        $this->funcionario->funcao_id = $data->funcao_id;
        $this->funcionario->equipe_id = $data->equipe_id ?? null;
        $this->funcionario->data_admissao = !empty($data->data_admissao) ? $data->data_admissao : null;
        $this->funcionario->status = $data->status ?? 'ativo';
        $this->funcionario->data_demissao = !empty($data->data_demissao) ? $data->data_demissao : null;
        $this->funcionario->motivo_demissao = !empty($data->motivo_demissao) ? $data->motivo_demissao : null;

        if ($this->funcionario->update()) {
            $this->checkTriggers($this->funcionario);
            http_response_code(200); echo json_encode(array("message" => "Atualizado."));
        } else { http_response_code(500); echo json_encode(array("message" => "Erro.")); }
    }

    private function demitirFuncionario($id) {
        $data = json_decode(file_get_contents("php://input"));
        if (empty($id) || empty($data->data_demissao) || empty($data->motivo_demissao)) {
            http_response_code(400); echo json_encode(["message" => "Dados incompletos. Informe a data e o motivo."]); return;
        }

        $query = "UPDATE funcionarios SET status='demitido', data_demissao=:dd, motivo_demissao=:md WHERE id=:id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":dd", $data->data_demissao);
        $stmt->bindParam(":md", $data->motivo_demissao);
        $stmt->bindParam(":id", $id);

        if ($stmt->execute()) {
            // Pegar o nome para notificação
            $q2 = "SELECT nome FROM funcionarios WHERE id=:id";
            $s2 = $this->db->prepare($q2); $s2->bindParam(":id", $id); $s2->execute();
            $r2 = $s2->fetch(PDO::FETCH_ASSOC);

            $this->funcionario->nome = $r2['nome'] ?? ("ID " . $id);
            $this->funcionario->status = 'demitido';
            $this->funcionario->motivo_demissao = $data->motivo_demissao;
            $this->checkTriggers($this->funcionario);

            http_response_code(200); echo json_encode(["message" => "Funcionário demitido com sucesso."]);
        } else {
            http_response_code(500); echo json_encode(["message" => "Erro ao registrar demissão."]);
        }
    }

    private function checkTriggers($func) {
        $notificacao = new Notificacao($this->db);

        // Se demitido
        if ($func->status === 'demitido') {
            $notificacao->titulo = 'Funcionário Demitido';
            $notificacao->mensagem = "O funcionário {$func->nome} foi registrado como demitido. Motivo: {$func->motivo_demissao}";
            $notificacao->tipo = 'RH';
            $notificacao->prioridade = 'media';
            $notificacao->modulo_origem = 'RH';
            // Notifica admins e RH (simplificado: enviando para um admin especifico ou broadcast no front)
            // Como não temos query fácil pra todos RH, envia pro usuario 1 (admin)
            $notificacao->usuario_id = 1;
            $notificacao->create();
        }
    }
    private function deleteFuncionario($id) {
        if (!empty($id)) { $this->funcionario->id = $id; if ($this->funcionario->delete()) { http_response_code(200); echo json_encode(array("message" => "Apagado.")); } else { http_response_code(500); echo json_encode(array("message" => "Erro.")); } } else { http_response_code(400); echo json_encode(array("message" => "ID não fornecido.")); }
    }
}
?>
