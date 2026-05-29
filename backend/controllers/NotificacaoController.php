<?php
class NotificacaoController {
    private $db, $notificacao;
    public function __construct($db) { $this->db = $db; $this->notificacao = new Notificacao($db); }
    public function processRequest($method, $id) {
        if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(array("message" => "Não autorizado.")); return; }
        switch ($method) {
            case 'GET':
                if (isset($_GET['count_only'])) { $this->getUnreadCount(); }
                else { $this->getNotificacoes(); }
                break;
            case 'POST':
                if (!empty($id)) {
                    $this->markAsRead($id);
                } else if (isset($_GET['mark_all'])) {
                    $this->markAllAsRead();
                } else {
                    $this->createNotificacao();
                }
                break;
            default: http_response_code(405); echo json_encode(array("message" => "Método não permitido.")); break;
        }
    }
    private function getNotificacoes() {
        $this->notificacao->usuario_id = $_SESSION['user_id'];
        $filtros = [
            'lida' => $_GET['lida'] ?? null,
            'prioridade' => $_GET['prioridade'] ?? null,
            'tipo' => $_GET['tipo'] ?? null
        ];
        $stmt = $this->notificacao->read($filtros);
        $notificacoes = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { array_push($notificacoes, $row); }
        http_response_code(200); echo json_encode($notificacoes);
    }
    private function getUnreadCount() {
        $this->notificacao->usuario_id = $_SESSION['user_id'];
        $count = $this->notificacao->unreadCount();
        http_response_code(200); echo json_encode(["unread" => $count]);
    }
    private function createNotificacao() {
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->usuario_id) && !empty($data->mensagem)) {
            $this->notificacao->usuario_id = $data->usuario_id;
            $this->notificacao->titulo = $data->titulo ?? 'Notificação';
            $this->notificacao->mensagem = $data->mensagem;
            $this->notificacao->tipo = $data->tipo ?? 'info';
            $this->notificacao->prioridade = $data->prioridade ?? 'baixa';
            $this->notificacao->modulo_origem = $data->modulo_origem ?? '';
            $this->notificacao->link = $data->link ?? '';

            if ($this->notificacao->create()) { http_response_code(201); echo json_encode(array("message" => "Criada.")); } else { http_response_code(500); echo json_encode(array("message" => "Erro.")); }
        } else { http_response_code(400); echo json_encode(array("message" => "Dados incompletos.")); }
    }
    private function markAsRead($id) {
        $this->notificacao->id = $id; if ($this->notificacao->markAsRead()) { http_response_code(200); echo json_encode(array("message" => "Lida.")); } else { http_response_code(500); echo json_encode(array("message" => "Erro.")); }
    }
    private function markAllAsRead() {
        $this->notificacao->usuario_id = $_SESSION['user_id'];
        if ($this->notificacao->markAllAsRead()) { http_response_code(200); echo json_encode(["message" => "Todas lidas."]); } else { http_response_code(500); echo json_encode(["message" => "Erro."]); }
    }
}
?>
