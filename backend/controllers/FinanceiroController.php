<?php
class FinanceiroController {
    private $db, $financeiro;
    public function __construct($db) { $this->db = $db; $this->financeiro = new Financeiro($db); }
    public function processRequest($method, $id) {
        if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(array("message" => "Não autorizado.")); return; }
        switch ($method) { case 'GET': $this->getFinanceiro(); break; case 'POST': $this->createFinanceiro(); break; case 'PUT': $this->updateFinanceiro($id); break; case 'DELETE': $this->deleteFinanceiro($id); break; default: http_response_code(405); echo json_encode(array("message" => "Método não permitido.")); break; }
    }
    private function getFinanceiro() {
        $filtros = [
            'status' => $_GET['status'] ?? null,
            'tipo' => $_GET['tipo'] ?? null,
            'data_inicio' => $_GET['data_inicio'] ?? null,
            'data_fim' => $_GET['data_fim'] ?? null
        ];
        $stmt = $this->financeiro->read($filtros); $registros = array(); while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { array_push($registros, $row); } http_response_code(200); echo json_encode($registros);
    }
    private function createFinanceiro() {
        $data = json_decode(file_get_contents("php://input"));
        if (empty($data->obra_id) || empty($data->tipo) || empty($data->descricao) || !isset($data->valor) || $data->valor === '') {
            http_response_code(400); echo json_encode(array("message" => "Obra, Tipo, Descrição e Valor são obrigatórios.")); return;
        }
        if (floatval($data->valor) <= 0) {
            http_response_code(400); echo json_encode(array("message" => "O valor do lançamento deve ser maior que zero.")); return;
        }
            $this->financeiro->obra_id = $data->obra_id; $this->financeiro->tipo = $data->tipo; $this->financeiro->descricao = $data->descricao; $this->financeiro->valor = $data->valor; $this->financeiro->status = $data->status ?? 'Pendente'; $this->financeiro->data_vencimento = !empty($data->data_vencimento) ? $data->data_vencimento : null;
            if ($this->financeiro->create()) {

                // Despesa alta notifica
                if ($this->financeiro->tipo === 'despesa' && $this->financeiro->valor > 5000) {
                    $notificacao = new Notificacao($this->db);
                    $notificacao->titulo = 'Alerta de Alta Despesa';
                    $notificacao->mensagem = "Uma despesa de R$ {$this->financeiro->valor} foi lançada com a descrição: {$this->financeiro->descricao}.";
                    $notificacao->tipo = 'financeiro';
                    $notificacao->prioridade = 'alta';
                    $notificacao->modulo_origem = 'Financeiro';
                    $notificacao->usuario_id = 1;
                    $notificacao->create();
                }

            http_response_code(201); echo json_encode(array("message" => "Criado."));
        } else { http_response_code(500); echo json_encode(array("message" => "Erro.")); }
    }
    private function updateFinanceiro($id) {
        $data = json_decode(file_get_contents("php://input"));
        if (empty($id) || empty($data->obra_id) || empty($data->tipo) || empty($data->descricao) || !isset($data->valor) || $data->valor === '') {
            http_response_code(400); echo json_encode(array("message" => "Obra, Tipo, Descrição e Valor são obrigatórios.")); return;
        }
        if (floatval($data->valor) <= 0) {
            http_response_code(400); echo json_encode(array("message" => "O valor do lançamento deve ser maior que zero.")); return;
        }
            $this->financeiro->id = $id; $this->financeiro->obra_id = $data->obra_id; $this->financeiro->tipo = $data->tipo; $this->financeiro->descricao = $data->descricao; $this->financeiro->valor = $data->valor; $this->financeiro->status = $data->status; $this->financeiro->data_vencimento = !empty($data->data_vencimento) ? $data->data_vencimento : null;
        if ($this->financeiro->update()) { http_response_code(200); echo json_encode(array("message" => "Atualizado.")); } else { http_response_code(500); echo json_encode(array("message" => "Erro.")); }
    }
    private function deleteFinanceiro($id) {
        if (!empty($id)) { $this->financeiro->id = $id; if ($this->financeiro->delete()) { http_response_code(200); echo json_encode(array("message" => "Apagado.")); } else { http_response_code(500); echo json_encode(array("message" => "Erro.")); } } else { http_response_code(400); echo json_encode(array("message" => "ID não fornecido.")); }
    }
}
?>
