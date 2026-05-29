<?php
class Dashboard {
    private $conn;
    public function __construct($db) { $this->conn = $db; }
    public function getResumo() {
        $resumo = [];
        $query = "SELECT COUNT(*) as total FROM obras";
        $stmt = $this->conn->prepare($query); $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC); $resumo['total_obras'] = $row['total'];
        $query = "SELECT COUNT(*) as andamento FROM obras WHERE status = 'Em andamento'";
        $stmt = $this->conn->prepare($query); $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC); $resumo['obras_andamento'] = $row['andamento'];

        $query = "SELECT COUNT(*) as atrasadas FROM obras WHERE status = 'Em andamento' AND data_fim_prevista < CURDATE()";
        $stmt = $this->conn->prepare($query); $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC); $resumo['obras_atrasadas'] = $row['atrasadas'];

        $query = "SELECT SUM(valor) as total_gastos FROM financeiro WHERE tipo = 'despesa' AND status = 'Pago' AND MONTH(data_vencimento) = MONTH(CURDATE()) AND YEAR(data_vencimento) = YEAR(CURDATE())";
        $stmt = $this->conn->prepare($query); $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC); $resumo['total_gastos_mes'] = $row['total_gastos'] ?? 0;

        $query = "SELECT COUNT(*) as ativos FROM funcionarios WHERE status = 'ativo'";
        $stmt = $this->conn->prepare($query); $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC); $resumo['funcionarios_ativos'] = $row['ativos'];

        // Get dias sem acidente
        $query = "SELECT dias_sem_acidentes, data_registro FROM acidentes_historico ORDER BY id DESC LIMIT 1";
        $stmt = $this->conn->prepare($query); $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Auto increment days logic loosely handled on fetch for simplicity or handled directly by a chron. Here we fetch the registered value.
        if ($row) {
            $diff = (strtotime(date('Y-m-d')) - strtotime($row['data_registro'])) / (60 * 60 * 24);
            $resumo['dias_sem_acidente'] = $row['dias_sem_acidentes'] + floor($diff);
        } else {
            $resumo['dias_sem_acidente'] = 0;
        }

        return $resumo;
    }
}
?>
