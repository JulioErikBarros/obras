<?php
class Etapa {
    private $conn; private $table_name = "etapas";
    public $id, $obra_id, $nome, $descricao, $observacoes, $status, $percentual, $data_inicio, $data_fim_prevista, $equipe_id, $responsavel_id, $ordem, $created_at, $updated_at;
    public function __construct($db) { $this->conn = $db; }
    public function read() {
        $query = "SELECT e.*, o.nome as obra_nome, eq.nome as equipe_nome, f.nome as responsavel_nome 
                  FROM " . $this->table_name . " e 
                  LEFT JOIN obras o ON e.obra_id = o.id 
                  LEFT JOIN equipes eq ON e.equipe_id = eq.id 
                  LEFT JOIN funcionarios f ON e.responsavel_id = f.id
                  ORDER BY e.ordem ASC, e.id DESC";
        $stmt = $this->conn->prepare($query); $stmt->execute(); return $stmt;
    }
    public function create() {
        // Sincronização de Status e Percentual
        if ($this->status === 'Concluída') {
            $this->percentual = 100;
        } else if (intval($this->percentual) === 100) {
            $this->status = 'Concluída';
        }

        $query = "INSERT INTO " . $this->table_name . " SET obra_id=:obra_id, nome=:nome, descricao=:descricao, observacoes=:observacoes, status=:status, percentual=:percentual, data_inicio=:data_inicio, data_fim_prevista=:data_fim_prevista, equipe_id=:equipe_id, responsavel_id=:responsavel_id, ordem=:ordem";
        $stmt = $this->conn->prepare($query);
        
        $this->obra_id = !empty($this->obra_id) ? htmlspecialchars(strip_tags($this->obra_id)) : null;
        $this->nome = !empty($this->nome) ? htmlspecialchars(strip_tags($this->nome)) : '';
        $this->descricao = !empty($this->descricao) ? htmlspecialchars(strip_tags($this->descricao)) : '';
        $this->observacoes = !empty($this->observacoes) ? htmlspecialchars(strip_tags($this->observacoes)) : '';
        $this->status = !empty($this->status) ? htmlspecialchars(strip_tags($this->status)) : 'Pendente';
        $this->percentual = isset($this->percentual) ? intval($this->percentual) : 0;
        $this->data_inicio = !empty($this->data_inicio) ? htmlspecialchars(strip_tags($this->data_inicio)) : null;
        $this->data_fim_prevista = !empty($this->data_fim_prevista) ? htmlspecialchars(strip_tags($this->data_fim_prevista)) : null;
        $this->equipe_id = !empty($this->equipe_id) ? htmlspecialchars(strip_tags($this->equipe_id)) : null;
        $this->responsavel_id = !empty($this->responsavel_id) ? htmlspecialchars(strip_tags($this->responsavel_id)) : null;
        $this->ordem = isset($this->ordem) ? intval($this->ordem) : 0;

        $stmt->bindParam(":obra_id", $this->obra_id);
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":descricao", $this->descricao);
        $stmt->bindParam(":observacoes", $this->observacoes);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":percentual", $this->percentual);
        $stmt->bindParam(":data_inicio", $this->data_inicio);
        $stmt->bindParam(":data_fim_prevista", $this->data_fim_prevista);
        $stmt->bindParam(":equipe_id", $this->equipe_id);
        $stmt->bindParam(":responsavel_id", $this->responsavel_id);
        $stmt->bindParam(":ordem", $this->ordem);

        if ($stmt->execute()) {
            $new_id = $this->conn->lastInsertId();
            
            // Se a etapa estiver "Em andamento" e tiver equipe vinculada, ativa o vínculo da equipe
            if ($this->status === 'Em andamento' && !empty($this->equipe_id)) {
                $q = "UPDATE equipes SET etapa_id = :etapa_id, obra_id = :obra_id WHERE id = :equipe_id";
                $s = $this->conn->prepare($q);
                $s->bindValue(':etapa_id', $new_id);
                $s->bindValue(':obra_id', $this->obra_id);
                $s->bindValue(':equipe_id', $this->equipe_id);
                $s->execute();
            }
            
            $this->atualizarProgressoObra($this->obra_id);
            return true;
        }
        return false;
    }
    public function update() {
        // Sincronização de Status e Percentual
        if ($this->status === 'Concluída') {
            $this->percentual = 100;
        } else if (intval($this->percentual) === 100) {
            $this->status = 'Concluída';
        }

        $query = "UPDATE " . $this->table_name . " SET obra_id=:obra_id, nome=:nome, descricao=:descricao, observacoes=:observacoes, status=:status, percentual=:percentual, data_inicio=:data_inicio, data_fim_prevista=:data_fim_prevista, equipe_id=:equipe_id, responsavel_id=:responsavel_id, ordem=:ordem WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->obra_id = !empty($this->obra_id) ? htmlspecialchars(strip_tags($this->obra_id)) : null;
        $this->nome = !empty($this->nome) ? htmlspecialchars(strip_tags($this->nome)) : '';
        $this->descricao = !empty($this->descricao) ? htmlspecialchars(strip_tags($this->descricao)) : '';
        $this->observacoes = !empty($this->observacoes) ? htmlspecialchars(strip_tags($this->observacoes)) : '';
        $this->status = !empty($this->status) ? htmlspecialchars(strip_tags($this->status)) : 'Pendente';
        $this->percentual = isset($this->percentual) ? intval($this->percentual) : 0;
        $this->data_inicio = !empty($this->data_inicio) ? htmlspecialchars(strip_tags($this->data_inicio)) : null;
        $this->data_fim_prevista = !empty($this->data_fim_prevista) ? htmlspecialchars(strip_tags($this->data_fim_prevista)) : null;
        $this->equipe_id = !empty($this->equipe_id) ? htmlspecialchars(strip_tags($this->equipe_id)) : null;
        $this->responsavel_id = !empty($this->responsavel_id) ? htmlspecialchars(strip_tags($this->responsavel_id)) : null;
        $this->ordem = isset($this->ordem) ? intval($this->ordem) : 0;

        $stmt->bindParam(":obra_id", $this->obra_id);
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":descricao", $this->descricao);
        $stmt->bindParam(":observacoes", $this->observacoes);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":percentual", $this->percentual);
        $stmt->bindParam(":data_inicio", $this->data_inicio);
        $stmt->bindParam(":data_fim_prevista", $this->data_fim_prevista);
        $stmt->bindParam(":equipe_id", $this->equipe_id);
        $stmt->bindParam(":responsavel_id", $this->responsavel_id);
        $stmt->bindParam(":ordem", $this->ordem);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            // Lógica de Vínculos e Desvinculações de Equipes
            if ($this->status === 'Concluída') {
                $q = "UPDATE equipes SET etapa_id = NULL, obra_id = NULL WHERE etapa_id = :etapa_id";
                $s = $this->conn->prepare($q);
                $s->bindValue(':etapa_id', $this->id);
                $s->execute();
            } else {
                $q = "UPDATE equipes SET etapa_id = NULL, obra_id = NULL WHERE etapa_id = :etapa_id";
                $s = $this->conn->prepare($q);
                $s->bindValue(':etapa_id', $this->id);
                $s->execute();
                
                if ($this->status === 'Em andamento' && !empty($this->equipe_id)) {
                    $q = "UPDATE equipes SET etapa_id = :etapa_id, obra_id = :obra_id WHERE id = :equipe_id";
                    $s = $this->conn->prepare($q);
                    $s->bindValue(':etapa_id', $this->id);
                    $s->bindValue(':obra_id', $this->obra_id);
                    $s->bindValue(':equipe_id', $this->equipe_id);
                    $s->execute();
                }
            }

            $this->atualizarProgressoObra($this->obra_id);
            return true;
        }
        return false;
    }
    public function delete() {
        $query_get = "SELECT obra_id, equipe_id FROM " . $this->table_name . " WHERE id = ?";
        $stmt_get = $this->conn->prepare($query_get);
        $stmt_get->bindParam(1, $this->id);
        $stmt_get->execute();
        $row = $stmt_get->fetch(PDO::FETCH_ASSOC);
        $obra_id = $row['obra_id'] ?? null;

        if ($this->id) {
            $q = "UPDATE equipes SET etapa_id = NULL, obra_id = NULL WHERE etapa_id = ?";
            $s = $this->conn->prepare($q);
            $s->execute([$this->id]);
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query); $this->id = htmlspecialchars(strip_tags($this->id)); $stmt->bindParam(1, $this->id); 
        if ($stmt->execute()) {
            if ($obra_id) {
                $this->atualizarProgressoObra($obra_id);
            }
            return true;
        }
        return false;
    }
    public function atualizarProgressoObra($obra_id) {
        if (empty($obra_id)) return;
        
        $query = "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'Concluída' THEN 100 ELSE COALESCE(percentual, 0) END) as soma_percentual FROM " . $this->table_name . " WHERE obra_id = :obra_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":obra_id", $obra_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $total = $row['total'] ?? 0;
        $soma_percentual = $row['soma_percentual'] ?? 0;
        
        $percentual = 0;
        if ($total > 0) {
            $percentual = round($soma_percentual / $total, 2);
        }
        
        $novo_status = 'Em planejamento';
        if ($total > 0) {
            if ($percentual == 100) {
                $novo_status = 'Concluída';
            } else {
                $query_check = "SELECT COUNT(*) as iniciadas FROM " . $this->table_name . " WHERE obra_id = :obra_id AND (status IN ('Em andamento', 'Concluída') OR percentual > 0)";
                $stmt_check = $this->conn->prepare($query_check);
                $stmt_check->bindParam(":obra_id", $obra_id);
                $stmt_check->execute();
                $row_check = $stmt_check->fetch(PDO::FETCH_ASSOC);
                
                if (($row_check['iniciadas'] ?? 0) > 0) {
                    $novo_status = 'Em andamento';
                } else {
                    $novo_status = 'Em planejamento';
                }
            }
        }
        
        $query_update = "UPDATE obras SET percentual_concluido = :percentual, status = :status WHERE id = :obra_id";
        $stmt_update = $this->conn->prepare($query_update);
        $stmt_update->bindParam(":percentual", $percentual);
        $stmt_update->bindParam(":status", $novo_status);
        $stmt_update->bindParam(":obra_id", $obra_id);
        $stmt_update->execute();
    }
}
?>
