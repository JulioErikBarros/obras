<?php
class DashboardController {
    private $db, $dashboard;
    public function __construct($db) { $this->db = $db; $this->dashboard = new Dashboard($db); }
    public function processRequest($method, $id) {
        if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(array("message" => "Não autorizado.")); return; }
        if ($method === 'GET') { $resumo = $this->dashboard->getResumo(); http_response_code(200); echo json_encode($resumo);
        } else { http_response_code(405); echo json_encode(array("message" => "Método não permitido.")); }
    }
}
?>
