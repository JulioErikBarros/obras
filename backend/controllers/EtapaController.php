<?php
class EtapaController {
    private $db, $etapa;
    public function __construct($db) { $this->db = $db; $this->etapa = new Etapa($db); }
    public function processRequest($method, $id) {
        if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(array("message" => "Não autorizado.")); return; }
        switch ($method) { case 'GET': $this->getEtapas(); break; case 'POST': $this->createEtapa(); break; case 'PUT': $this->updateEtapa($id); break; case 'DELETE': $this->deleteEtapa($id); break; default: http_response_code(405); echo json_encode(array("message" => "Método não permitido.")); break; }
    }
    private function getEtapas() {
        if (isset($_GET['grouped']) && $_GET['grouped'] === 'true') {
            $this->getObrasGroupedWithEtapas();
        } else {
            $stmt = $this->etapa->read(); $etapas = array(); while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { array_push($etapas, $row); } http_response_code(200); echo json_encode($etapas);
        }
    }
    private function getObrasGroupedWithEtapas() {
        try {
            // Consulta para buscar todas as obras com seu responsável técnico
            $query_obras = "SELECT o.*, f.nome as responsavel_nome 
                            FROM obras o 
                            LEFT JOIN funcionarios f ON o.responsavel_id = f.id 
                            ORDER BY o.id DESC";
            $stmt_obras = $this->db->prepare($query_obras);
            $stmt_obras->execute();
            $obras = $stmt_obras->fetchAll(PDO::FETCH_ASSOC);

            // Consulta para buscar todas as etapas com equipe e gerente de etapa
            $query_etapas = "SELECT e.*, eq.nome as equipe_nome, f.nome as responsavel_nome 
                             FROM etapas e 
                             LEFT JOIN equipes eq ON e.equipe_id = eq.id 
                             LEFT JOIN funcionarios f ON e.responsavel_id = f.id 
                             ORDER BY e.ordem ASC, e.id ASC";
            $stmt_etapas = $this->db->prepare($query_etapas);
            $stmt_etapas->execute();
            $etapas = $stmt_etapas->fetchAll(PDO::FETCH_ASSOC);

            // Agrupar etapas por obra_id
            $etapas_by_obra = [];
            foreach ($etapas as $etapa) {
                $etapas_by_obra[$etapa['obra_id']][] = $etapa;
            }

            // Montar resposta final
            $result = [];
            foreach ($obras as $obra) {
                $obra_id = $obra['id'];
                $obra_etapas = $etapas_by_obra[$obra_id] ?? [];
                
                $total_etapas = count($obra_etapas);
                $concluidas = 0;
                foreach ($obra_etapas as $etapa) {
                    if ($etapa['status'] === 'Concluída') {
                        $concluidas++;
                    }
                }

                $result[] = [
                    'id' => $obra['id'],
                    'nome' => $obra['nome'],
                    'tipo' => $obra['tipo'],
                    'status' => $obra['status'],
                    'percentual_concluido' => floatval($obra['percentual_concluido']),
                    'responsavel_nome' => $obra['responsavel_nome'],
                    'total_etapas' => $total_etapas,
                    'etapas_concluidas' => $concluidas,
                    'etapas' => $obra_etapas
                ];
            }

            http_response_code(200);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Erro ao obter dados agrupados: " . $e->getMessage()));
        }
    }
    private function createEtapa() {
        $data = json_decode(file_get_contents("php://input"));
        if (empty($data->obra_id) || empty($data->nome)) {
            http_response_code(400); echo json_encode(array("message" => "Obra e Nome da etapa são obrigatórios.")); return;
        }
        $this->etapa->obra_id = $data->obra_id;
        $this->etapa->nome = $data->nome;
        $this->etapa->descricao = $data->descricao ?? '';
        $this->etapa->observacoes = $data->observacoes ?? '';
        $this->etapa->status = $data->status ?? 'Pendente';
        $this->etapa->percentual = $data->percentual ?? 0;
        $this->etapa->data_inicio = !empty($data->data_inicio) ? $data->data_inicio : null;
        $this->etapa->data_fim_prevista = !empty($data->data_fim_prevista) ? $data->data_fim_prevista : null;
        $this->etapa->equipe_id = !empty($data->equipe_id) ? $data->equipe_id : null;
        $this->etapa->responsavel_id = !empty($data->responsavel_id) ? $data->responsavel_id : null;
        $this->etapa->ordem = $data->ordem ?? 0;

        if ($this->etapa->create()) { http_response_code(201); echo json_encode(array("message" => "Etapa criada.")); } else { http_response_code(500); echo json_encode(array("message" => "Erro.")); }
    }
    private function updateEtapa($id) {
        $data = json_decode(file_get_contents("php://input"));
        if (empty($id) || empty($data->obra_id) || empty($data->nome)) {
            http_response_code(400); echo json_encode(array("message" => "Obra e Nome da etapa são obrigatórios.")); return;
        }
        $this->etapa->id = $id;
        $this->etapa->obra_id = $data->obra_id;
        $this->etapa->nome = $data->nome;
        $this->etapa->descricao = $data->descricao ?? '';
        $this->etapa->observacoes = $data->observacoes ?? '';
        $this->etapa->status = $data->status;
        $this->etapa->percentual = $data->percentual ?? 0;
        $this->etapa->data_inicio = !empty($data->data_inicio) ? $data->data_inicio : null;
        $this->etapa->data_fim_prevista = !empty($data->data_fim_prevista) ? $data->data_fim_prevista : null;
        $this->etapa->equipe_id = !empty($data->equipe_id) ? $data->equipe_id : null;
        $this->etapa->responsavel_id = !empty($data->responsavel_id) ? $data->responsavel_id : null;
        $this->etapa->ordem = $data->ordem ?? 0;

        if ($this->etapa->update()) {
            // Notificar atraso se a data atual for maior que a final prevista e não estiver concluída
            if ($this->etapa->status !== 'Concluída' && !empty($this->etapa->data_fim_prevista) && strtotime(date('Y-m-d')) > strtotime($this->etapa->data_fim_prevista)) {
                $notificacao = new Notificacao($this->db);
                $notificacao->titulo = 'Etapa Atrasada';
                $notificacao->mensagem = "A etapa {$this->etapa->nome} ultrapassou a data de conclusão prevista.";
                $notificacao->tipo = 'atraso';
                $notificacao->prioridade = 'alta';
                $notificacao->modulo_origem = 'Engenharia';
                $notificacao->usuario_id = 1;
                $notificacao->create();
            }

            http_response_code(200); echo json_encode(array("message" => "Atualizada."));
        } else { http_response_code(500); echo json_encode(array("message" => "Erro.")); }
    }
    private function deleteEtapa($id) {
        if (!empty($id)) { $this->etapa->id = $id; if ($this->etapa->delete()) { http_response_code(200); echo json_encode(array("message" => "Apagada.")); } else { http_response_code(500); echo json_encode(array("message" => "Erro.")); } } else { http_response_code(400); echo json_encode(array("message" => "ID não fornecido.")); }
    }
}
?>
