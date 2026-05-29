<?php
class Relatorio {
    private $conn;
    public function __construct($db) { $this->conn = $db; }
    public function getGastos($filtros = []) {
        $query = "SELECT f.descricao, f.valor, f.status, f.data_vencimento, o.nome as obra_nome FROM financeiro f LEFT JOIN obras o ON f.obra_id = o.id WHERE f.tipo = 'despesa'";

        if (!empty($filtros['obra_id'])) { $query .= " AND f.obra_id = :obra_id"; }
        if (!empty($filtros['data_inicio']) && !empty($filtros['data_fim'])) {
            $query .= " AND f.data_vencimento BETWEEN :data_inicio AND :data_fim";
        }

        $query .= " ORDER BY f.data_vencimento DESC";
        $stmt = $this->conn->prepare($query);

        if (!empty($filtros['obra_id'])) { $stmt->bindValue(':obra_id', $filtros['obra_id']); }
        if (!empty($filtros['data_inicio']) && !empty($filtros['data_fim'])) {
            $stmt->bindValue(':data_inicio', $filtros['data_inicio']);
            $stmt->bindValue(':data_fim', $filtros['data_fim']);
        }

        $stmt->execute(); return $stmt;
    }
    public function getEstoque($filtros = []) {
        $query = "SELECT m.nome, m.categoria, m.unidade_medida, SUM(CASE WHEN m.tipo_movimentacao = 'entrada' THEN m.quantidade ELSE 0 END) as entradas, SUM(CASE WHEN m.tipo_movimentacao = 'saida' THEN m.quantidade ELSE 0 END) as saidas, (SUM(CASE WHEN m.tipo_movimentacao = 'entrada' THEN m.quantidade ELSE 0 END) - SUM(CASE WHEN m.tipo_movimentacao = 'saida' THEN m.quantidade ELSE 0 END)) as saldo, o.nome as obra_nome FROM materiais m LEFT JOIN obras o ON m.obra_id = o.id WHERE 1=1";

        if (!empty($filtros['obra_id'])) { $query .= " AND m.obra_id = :obra_id"; }
        if (!empty($filtros['categoria'])) { $query .= " AND m.categoria = :categoria"; }

        $query .= " GROUP BY m.obra_id, m.nome, m.categoria, m.unidade_medida, o.nome";
        $stmt = $this->conn->prepare($query);

        if (!empty($filtros['obra_id'])) { $stmt->bindValue(':obra_id', $filtros['obra_id']); }
        if (!empty($filtros['categoria'])) { $stmt->bindValue(':categoria', $filtros['categoria']); }

        $stmt->execute(); return $stmt;
    }
    public function getRH($filtros = []) {
        $query = "SELECT f.nome, f.status, f.data_admissao, f.data_demissao, f.motivo_demissao, 
                         func.nome as funcao_nome, func.salario_base, func.horario_padrao, 
                         eq.nome as equipe_nome 
                  FROM funcionarios f
                  LEFT JOIN funcoes func ON f.funcao_id = func.id
                  LEFT JOIN equipes eq ON f.equipe_id = eq.id
                  WHERE 1=1";

        if (!empty($filtros['status'])) {
            $query .= " AND f.status = :status";
        }
        if (!empty($filtros['funcao_id'])) {
            $query .= " AND f.funcao_id = :funcao_id";
        }
        if (!empty($filtros['equipe_id'])) {
            $query .= " AND f.equipe_id = :equipe_id";
        }
        if (!empty($filtros['admissao_inicio']) && !empty($filtros['admissao_fim'])) {
            $query .= " AND f.data_admissao BETWEEN :admissao_inicio AND :admissao_fim";
        }
        if (!empty($filtros['demissao_inicio']) && !empty($filtros['demissao_fim'])) {
            $query .= " AND f.data_demissao BETWEEN :demissao_inicio AND :demissao_fim";
        }

        $query .= " ORDER BY f.nome ASC";
        $stmt = $this->conn->prepare($query);

        if (!empty($filtros['status'])) { $stmt->bindValue(':status', $filtros['status']); }
        if (!empty($filtros['funcao_id'])) { $stmt->bindValue(':funcao_id', $filtros['funcao_id']); }
        if (!empty($filtros['equipe_id'])) { $stmt->bindValue(':equipe_id', $filtros['equipe_id']); }
        if (!empty($filtros['admissao_inicio']) && !empty($filtros['admissao_fim'])) {
            $stmt->bindValue(':admissao_inicio', $filtros['admissao_inicio']);
            $stmt->bindValue(':admissao_fim', $filtros['admissao_fim']);
        }
        if (!empty($filtros['demissao_inicio']) && !empty($filtros['demissao_fim'])) {
            $stmt->bindValue(':demissao_inicio', $filtros['demissao_inicio']);
            $stmt->bindValue(':demissao_fim', $filtros['demissao_fim']);
        }

        $stmt->execute();
        return $stmt;
    }
    public function getAcidentes($filtros = []) {
        $query = "SELECT id, data_registro, descricao, dias_sem_acidentes FROM acidentes_historico WHERE houve_acidente = 1";
        
        if (!empty($filtros['data_inicio']) && !empty($filtros['data_fim'])) {
            $query .= " AND data_registro BETWEEN :data_inicio AND :data_fim";
        }
        
        $query .= " ORDER BY data_registro DESC";
        $stmt = $this->conn->prepare($query);
        
        if (!empty($filtros['data_inicio']) && !empty($filtros['data_fim'])) {
            $stmt->bindValue(':data_inicio', $filtros['data_inicio']);
            $stmt->bindValue(':data_fim', $filtros['data_fim']);
        }
        
        $stmt->execute();
        return $stmt;
    }
}
?>
