<?php
class ObraController {
    private $db, $obra;
    public function __construct($db) { $this->db = $db; $this->obra = new Obra($db); }
    public function processRequest($method, $id) {
        if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(array("message" => "Não autorizado.")); return; }
        switch ($method) { case 'GET': $this->getObras(); break; case 'POST': $this->createObra(); break; case 'PUT': $this->updateObra($id); break; case 'DELETE': $this->deleteObra($id); break; default: http_response_code(405); echo json_encode(array("message" => "Método não permitido.")); break; }
    }
    private function getObras() {
        $filtros = [
            'status' => $_GET['status'] ?? null,
            'search' => $_GET['search'] ?? null
        ];
        $stmt = $this->obra->read($filtros); $obras = array(); while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { array_push($obras, $row); } http_response_code(200); echo json_encode($obras);
    }
    private function createObra() {
        $data = json_decode(file_get_contents("php://input"));
        if (empty($data->nome)) {
            http_response_code(400); echo json_encode(array("message" => "O nome da obra é obrigatório.")); return;
        }

        try {
            $this->db->beginTransaction();

            $this->obra->nome = $data->nome;
            $this->obra->tipo = $data->tipo ?? 'Outros';
            $this->obra->descricao = $data->descricao ?? '';
            $this->obra->status = $data->status ?? 'Em planejamento';
            $this->obra->endereco = $data->endereco ?? '';
            $this->obra->responsavel_id = $data->responsavel_id ?? null;
            $this->obra->equipe_id = $data->equipe_id ?? null;
            $this->obra->data_inicio = !empty($data->data_inicio) ? $data->data_inicio : null;
            $this->obra->data_fim_prevista = !empty($data->data_fim_prevista) ? $data->data_fim_prevista : null;
            $this->obra->percentual_concluido = $data->percentual_concluido ?? 0;

            $obra_id = $this->obra->create();
            if (!$obra_id) {
                throw new Exception("Erro ao criar a obra.");
            }

            // Gerar etapas predefinidas com busca e validação no banco de dados
            $this->gerarEtapasPredefinidasDb($obra_id, $this->obra->tipo);

            $this->db->commit();

            // Disparar Notificacao (depois do commit bem sucedido)
            $notificacao = new Notificacao($this->db);
            $notificacao->titulo = 'Nova Obra Cadastrada';
            $notificacao->mensagem = "Obra {$this->obra->nome} do tipo {$this->obra->tipo} cadastrada no sistema.";
            $notificacao->tipo = 'informação';
            $notificacao->prioridade = 'media';
            $notificacao->modulo_origem = 'Engenharia';
            $notificacao->usuario_id = 1;
            $notificacao->create();

            http_response_code(201);
            echo json_encode(array("message" => "Obra criada com etapas pré-definidas com sucesso.", "id" => $obra_id));

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(500);
            echo json_encode(array("message" => "Erro ao cadastrar obra: " . $e->getMessage()));
        }
    }

    private function updateObra($id) {
        $data = json_decode(file_get_contents("php://input"));
        if (empty($id) || empty($data->nome)) {
            http_response_code(400); echo json_encode(array("message" => "O nome da obra é obrigatório.")); return;
        }
        $this->obra->id = $id;
        $this->obra->nome = $data->nome;
        $this->obra->tipo = $data->tipo;
        $this->obra->descricao = $data->descricao;
        $this->obra->status = $data->status;
        $this->obra->endereco = $data->endereco;
        $this->obra->responsavel_id = !empty($data->responsavel_id) ? $data->responsavel_id : null;
        $this->obra->equipe_id = !empty($data->equipe_id) ? $data->equipe_id : null;
        $this->obra->data_inicio = !empty($data->data_inicio) ? $data->data_inicio : null;
        $this->obra->data_fim_prevista = !empty($data->data_fim_prevista) ? $data->data_fim_prevista : null;
        $this->obra->percentual_concluido = $data->percentual_concluido ?? 0;
        
        // Apenas atualiza as informações gerais da obra, nunca duplicando ou regenerando etapas
        if ($this->obra->update()) { 
            http_response_code(200); echo json_encode(array("message" => "Atualizado.")); 
        } else { 
            http_response_code(500); echo json_encode(array("message" => "Erro.")); 
        }
    }

    private function gerarEtapasPredefinidasDb($obra_id, $tipo) {
        // 1. Validar se o tipo de obra possui etapas pré-definidas no banco
        $query_tipo = "SELECT id FROM tipos_obra WHERE nome = :nome LIMIT 1";
        $stmt_tipo = $this->db->prepare($query_tipo);
        $stmt_tipo->bindValue(':nome', $tipo);
        $stmt_tipo->execute();
        $tipo_row = $stmt_tipo->fetch(PDO::FETCH_ASSOC);

        if (!$tipo_row) {
            throw new Exception("Tipo de obra '{$tipo}' não possui etapas pré-definidas.");
        }

        $tipo_id = $tipo_row['id'];

        // Buscar as etapas pré-definidas associadas
        $query_pref = "SELECT nome, descricao, ordem FROM etapas_predefinidas WHERE tipo_obra_id = :tipo_id AND status_ativo = 1 ORDER BY ordem ASC";
        $stmt_pref = $this->db->prepare($query_pref);
        $stmt_pref->bindValue(':tipo_id', $tipo_id);
        $stmt_pref->execute();
        $etapas_pref = $stmt_pref->fetchAll(PDO::FETCH_ASSOC);

        if (empty($etapas_pref)) {
            throw new Exception("Nenhuma etapa pré-definida cadastrada para o tipo de obra '{$tipo}'.");
        }

        // Criar automaticamente as etapas vinculadas à obra recém-cadastrada
        $etapaModel = new Etapa($this->db);
        foreach ($etapas_pref as $ep) {
            $etapaModel->obra_id = $obra_id;
            $etapaModel->nome = $ep['nome'];
            $etapaModel->descricao = $ep['descricao'] ?? 'Etapa gerada automaticamente';
            $etapaModel->status = 'Pendente';
            $etapaModel->percentual = 0;
            $etapaModel->data_inicio = null;
            $etapaModel->data_fim_prevista = null;
            $etapaModel->ordem = $ep['ordem'];
            
            if (!$etapaModel->create()) {
                throw new Exception("Erro ao inserir a etapa pré-definida '{$ep['nome']}'.");
            }
        }
    }
    private function deleteObra($id) {
        if (!empty($id)) { $this->obra->id = $id; if ($this->obra->delete()) { http_response_code(200); echo json_encode(array("message" => "Apagado.")); } else { http_response_code(500); echo json_encode(array("message" => "Erro.")); } } else { http_response_code(400); echo json_encode(array("message" => "ID não fornecido.")); }
    }
}
?>
