<?php
class UserController {
    private $db, $user;
    public function __construct($db) { $this->db = $db; $this->user = new User($db); }
    public function processRequest($method, $id, $resource) {
        if ($resource === 'auth') {
            if ($method === 'POST') { $this->login(); } else if ($method === 'DELETE') { $this->logout(); } else if ($method === 'GET') { $this->checkSession(); }
        } else if ($resource === 'users') {
            if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(array("message" => "Não autorizado.")); return; }
            if ($method === 'GET') { $this->getUsers(); }
            else if ($method === 'POST') { $this->createUser(); }
            else if ($method === 'PUT') { $this->updateUser($id); }
        } else if ($resource === 'tema') {
            if ($method === 'POST') { $this->updateTema(); }
        }
    }
    private function login() {
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->email) && !empty($data->senha)) {
            $this->user->email = $data->email; $stmt = $this->user->login();
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($data->senha, $row['senha'])) {
                    $_SESSION['user_id'] = $row['id']; $_SESSION['user_nome'] = $row['nome']; $_SESSION['user_nivel'] = $row['nivel_acesso'];
                    http_response_code(200); echo json_encode(array("message" => "Login bem-sucedido.", "user" => array("id" => $row['id'], "nome" => $row['nome'], "nivel_acesso" => $row['nivel_acesso'], "tema" => $row['tema_preferencia']))); return;
                }
            }
        }
        http_response_code(401); echo json_encode(array("message" => "Email ou senha incorretos."));
    }
    private function logout() { session_unset(); session_destroy(); http_response_code(200); echo json_encode(array("message" => "Logout bem-sucedido.")); }
    private function checkSession() {
        if (isset($_SESSION['user_id'])) { http_response_code(200); echo json_encode(array("user" => array("id" => $_SESSION['user_id'], "nome" => $_SESSION['user_nome'], "nivel_acesso" => $_SESSION['user_nivel'])));
        } else { http_response_code(401); echo json_encode(array("message" => "Não autorizado.")); }
    }
    private function getUsers() {
        $stmt = $this->user->read(); $users = array(); while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { array_push($users, $row); } http_response_code(200); echo json_encode($users);
    }
    private function createUser() {
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->nome) && !empty($data->email) && !empty($data->senha) && !empty($data->nivel_acesso)) {
            $this->user->nome = $data->nome; $this->user->email = $data->email; $this->user->senha = $data->senha; $this->user->nivel_acesso = $data->nivel_acesso; $this->user->status = $data->status ?? 'ativo';
            if ($this->user->create()) { http_response_code(201); echo json_encode(["message" => "Usuário criado."]); } else { http_response_code(500); echo json_encode(["message" => "Erro ao criar usuário."]); }
        } else { http_response_code(400); echo json_encode(["message" => "Dados incompletos."]); }
    }
    private function updateUser($id) {
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($id) && !empty($data->nome) && !empty($data->email) && !empty($data->nivel_acesso)) {
            $this->user->id = $id; $this->user->nome = $data->nome; $this->user->email = $data->email; $this->user->nivel_acesso = $data->nivel_acesso; $this->user->status = $data->status; $this->user->senha = !empty($data->senha) ? $data->senha : null;
            if ($this->user->update()) { http_response_code(200); echo json_encode(["message" => "Usuário atualizado."]); } else { http_response_code(500); echo json_encode(["message" => "Erro ao atualizar usuário."]); }
        } else { http_response_code(400); echo json_encode(["message" => "Dados incompletos."]); }
    }
    private function updateTema() {
        if (!isset($_SESSION['user_id'])) return;
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->tema)) {
            $this->user->id = $_SESSION['user_id'];
            $this->user->tema_preferencia = $data->tema;
            if ($this->user->updateTema()) { http_response_code(200); echo json_encode(["message" => "Tema atualizado."]); }
        }
    }
}
?>
