<?php
class AcidenteController {
    private $db, $acidente;
    public function __construct($db) { $this->db = $db; $this->acidente = new Acidente($db); }

    public function processRequest($method, $id) {
        if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(array("message" => "Não autorizado.")); return; }

        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if ($this->acidente->resetar($data->descricao ?? "")) {
                http_response_code(201);
                echo json_encode(["message" => "Contador de acidentes resetado com sucesso."]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Erro ao resetar contador."]);
            }
        } else {
            http_response_code(405); echo json_encode(array("message" => "Método não permitido."));
        }
    }
}
?>