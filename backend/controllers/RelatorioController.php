<?php
class RelatorioController {
    private $db, $relatorio;
    public function __construct($db) { $this->db = $db; $this->relatorio = new Relatorio($db); }
    public function processRequest($method, $id) {
        if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(array("message" => "Não autorizado.")); return; }
        if ($method === 'GET') {
            $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
            $nivel = $_SESSION['user_nivel'] ?? '';

            // Enforce fine-grained security per report type
            if ($tipo === 'gastos') {
                if ($nivel !== 'Administrador' && $nivel !== 'Financeiro') {
                    http_response_code(403);
                    echo json_encode(["message" => "Acesso negado. Você não tem permissão para gerar este relatório."]);
                    return;
                }
                $filtros = [
                    'obra_id' => isset($_GET['obra_id']) && $_GET['obra_id'] !== '' ? $_GET['obra_id'] : null,
                    'data_inicio' => $_GET['data_inicio'] ?? null,
                    'data_fim' => $_GET['data_fim'] ?? null
                ];
                $this->getRelatorioGastos($filtros);
            } else if ($tipo === 'estoque') {
                if ($nivel !== 'Administrador' && $nivel !== 'Almoxarifado') {
                    http_response_code(403);
                    echo json_encode(["message" => "Acesso negado. Você não tem permissão para gerar este relatório."]);
                    return;
                }
                $filtros = [
                    'obra_id' => isset($_GET['obra_id']) && $_GET['obra_id'] !== '' ? $_GET['obra_id'] : null,
                    'categoria' => $_GET['categoria'] ?? null
                ];
                $this->getRelatorioEstoque($filtros);
            } else if ($tipo === 'rh') {
                if ($nivel !== 'Administrador' && $nivel !== 'RH') {
                    http_response_code(403);
                    echo json_encode(["message" => "Acesso negado. Você não tem permissão para gerar este relatório."]);
                    return;
                }
                $filtros = [
                    'status' => $_GET['status'] ?? null,
                    'funcao_id' => isset($_GET['funcao_id']) && $_GET['funcao_id'] !== '' ? $_GET['funcao_id'] : null,
                    'equipe_id' => isset($_GET['equipe_id']) && $_GET['equipe_id'] !== '' ? $_GET['equipe_id'] : null,
                    'admissao_inicio' => $_GET['admissao_inicio'] ?? null,
                    'admissao_fim' => $_GET['admissao_fim'] ?? null,
                    'demissao_inicio' => $_GET['demissao_inicio'] ?? null,
                    'demissao_fim' => $_GET['demissao_fim'] ?? null
                ];
                $this->getRelatorioRH($filtros);
            } else if ($tipo === 'acidentes') {
                if ($nivel !== 'Administrador' && $nivel !== 'RH' && $nivel !== 'Engenharia') {
                    http_response_code(403);
                    echo json_encode(["message" => "Acesso negado. Você não tem permissão para gerar este relatório."]);
                    return;
                }
                $filtros = [
                    'data_inicio' => $_GET['data_inicio'] ?? null,
                    'data_fim' => $_GET['data_fim'] ?? null
                ];
                $this->getRelatorioAcidentes($filtros);
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Tipo de relatório inválido."));
            }
        } else { http_response_code(405); echo json_encode(array("message" => "Método não permitido.")); }
    }
    private function getRelatorioGastos($filtros) { $stmt = $this->relatorio->getGastos($filtros); $dados = array(); while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { array_push($dados, $row); } http_response_code(200); echo json_encode($dados); }
    private function getRelatorioEstoque($filtros) { $stmt = $this->relatorio->getEstoque($filtros); $dados = array(); while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { array_push($dados, $row); } http_response_code(200); echo json_encode($dados); }
    private function getRelatorioRH($filtros) { $stmt = $this->relatorio->getRH($filtros); $dados = array(); while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { array_push($dados, $row); } http_response_code(200); echo json_encode($dados); }
    private function getRelatorioAcidentes($filtros) { $stmt = $this->relatorio->getAcidentes($filtros); $dados = array(); while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { array_push($dados, $row); } http_response_code(200); echo json_encode($dados); }
}
?>
