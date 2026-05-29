<?php
class DocumentoController {
    private $db, $documento;
    public function __construct($db) { $this->db = $db; $this->documento = new Documento($db); }
    public function processRequest($method, $id) {
        if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(array("message" => "Não autorizado.")); return; }
        switch ($method) { case 'GET': $this->getDocumentos(); break; case 'POST': $this->uploadDocumento(); break; case 'DELETE': $this->deleteDocumento($id); break; default: http_response_code(405); echo json_encode(array("message" => "Método não permitido.")); break; }
    }
    private function getDocumentos() {
        $stmt = $this->documento->read(); $documentos = array(); while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { array_push($documentos, $row); } http_response_code(200); echo json_encode($documentos);
    }
    private function uploadDocumento() {
        if (isset($_POST['obra_id']) && isset($_POST['nome']) && isset($_POST['tipo']) && isset($_FILES['arquivo'])) {
            $obra_id = $_POST['obra_id']; $nome = $_POST['nome']; $tipo = $_POST['tipo']; $arquivo = $_FILES['arquivo'];
            if ($arquivo['error'] === UPLOAD_ERR_OK) {
                // Validação de tamanho (5MB)
                $maxSize = 5 * 1024 * 1024;
                if ($arquivo['size'] > $maxSize) {
                    http_response_code(400);
                    echo json_encode(array("message" => "Tamanho do arquivo excede o limite de 5MB."));
                    return;
                }

                // Validação de extensão / tipo MIME (Prevenção contra RCE)
                $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $arquivo['tmp_name']);
                finfo_close($finfo);

                $fileExt = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];

                if (!in_array($mime, $allowedMimeTypes) || !in_array($fileExt, $allowedExtensions)) {
                    http_response_code(400);
                    echo json_encode(array("message" => "Tipo de arquivo não permitido."));
                    return;
                }

                $upload_dir = '../frontend/uploads/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                $filename = time() . '_' . basename($arquivo['name']); $target_path = $upload_dir . $filename;
                if (move_uploaded_file($arquivo['tmp_name'], $target_path)) {
                    $this->documento->obra_id = $obra_id; $this->documento->nome = $nome; $this->documento->tipo = $tipo; $this->documento->caminho_arquivo = 'uploads/' . $filename;
                    if ($this->documento->create()) { http_response_code(201); echo json_encode(array("message" => "Documento enviado.")); } else { http_response_code(500); echo json_encode(array("message" => "Erro ao salvar no banco.")); }
                } else { http_response_code(500); echo json_encode(array("message" => "Erro ao mover arquivo.")); }
            } else { http_response_code(400); echo json_encode(array("message" => "Erro upload.")); }
        } else { http_response_code(400); echo json_encode(array("message" => "Dados incompletos.")); }
    }
    private function deleteDocumento($id) {
        if (!empty($id)) { $this->documento->id = $id; if ($this->documento->delete()) { http_response_code(200); echo json_encode(array("message" => "Apagado.")); } else { http_response_code(500); echo json_encode(array("message" => "Erro.")); } } else { http_response_code(400); echo json_encode(array("message" => "ID não fornecido.")); }
    }
}
?>
