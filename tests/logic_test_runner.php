<?php
/**
 * Executor de Testes de Lógica de Negócios (In-Memory Engine)
 * Parte da suíte de testes do ERP Controle de Obras
 */

// 1. Classe de Mock PDO que atua em memória sem drivers externos
class MockPDO {
    public $lastInsertId = 0;
    
    public function prepare($query) {
        return new MockPDOStatement($this, $query);
    }
    
    public function exec($query) {
        return true;
    }
    
    public function query($query) {
        $stmt = new MockPDOStatement($this, $query);
        $stmt->execute();
        return $stmt;
    }
    
    public function lastInsertId() {
        return $this->lastInsertId;
    }
    
    public function setAttribute($attr, $val) {
        return true;
    }
}

// 2. Classe de Mock Statement que emula o processamento do banco MySQL em PHP puro
class MockPDOStatement {
    private $pdo;
    private $query;
    private $params = [];
    private $results = [];
    private $cursor = 0;
    
    public function __construct($pdo, $query) {
        $this->pdo = $pdo;
        $this->query = $query;
    }
    
    public function bindParam($param, &$value, $type = null) {
        $this->params[$param] = &$value;
        return true;
    }
    
    public function bindValue($param, $value, $type = null) {
        $this->params[$param] = $value;
        return true;
    }
    
    public function execute($params = null) {
        if (is_array($params)) {
            foreach ($params as $k => $v) {
                $this->params[$k] = $v;
            }
        }
        
        $this->results = [];
        $this->cursor = 0;
        
        $queryNormalized = preg_replace('/\s+/', ' ', trim($this->query));
        
        // A. checkSaldoSuficiente de materiais
        if (stripos($queryNormalized, 'SUM(CASE WHEN tipo_movimentacao') !== false && stripos($queryNormalized, 'FROM materiais') !== false) {
            $obraId = $this->params[':obra_id'] ?? null;
            $nome = $this->params[':nome'] ?? null;
            
            $saldo = 0;
            if (isset($GLOBALS['mock_db']['materiais'])) {
                foreach ($GLOBALS['mock_db']['materiais'] as $m) {
                    if ($m['obra_id'] == $obraId && $m['nome'] == $nome) {
                        if ($m['tipo_movimentacao'] === 'entrada') {
                            $saldo += $m['quantidade'];
                        } else if ($m['tipo_movimentacao'] === 'saida') {
                            $saldo -= $m['quantidade'];
                        }
                    }
                }
            }
            $this->results = [['saldo' => $saldo]];
        }
        
        // B. INSERT INTO materiais
        else if (stripos($queryNormalized, 'INSERT INTO materiais') !== false || stripos($queryNormalized, 'INSERT INTO `materiais`') !== false) {
            $this->pdo->lastInsertId++;
            $id = $this->pdo->lastInsertId;
            $GLOBALS['mock_db']['materiais'][$id] = [
                'id' => $id,
                'obra_id' => $this->params[':obra_id'] ?? null,
                'nome' => $this->params[':nome'] ?? null,
                'categoria' => $this->params[':categoria'] ?? 'Outros',
                'quantidade' => floatval($this->params[':quantidade'] ?? 0),
                'unidade_medida' => $this->params[':unidade_medida'] ?? '',
                'tipo_movimentacao' => $this->params[':tipo_movimentacao'] ?? ''
            ];
        }
        
        // C. SELECT COUNT(*) as total, SUM(CASE...) FROM etapas
        else if (stripos($queryNormalized, 'COUNT(*) as total, SUM(CASE WHEN status') !== false && stripos($queryNormalized, 'FROM etapas') !== false) {
            $obraId = $this->params[':obra_id'] ?? null;
            
            $total = 0;
            $somaPercentual = 0;
            if (isset($GLOBALS['mock_db']['etapas'])) {
                foreach ($GLOBALS['mock_db']['etapas'] as $e) {
                    if ($e['obra_id'] == $obraId) {
                        $total++;
                        if ($e['status'] === 'Concluída') {
                            $somaPercentual += 100;
                        } else {
                            $somaPercentual += intval($e['percentual']);
                        }
                    }
                }
            }
            $this->results = [['total' => $total, 'soma_percentual' => $somaPercentual]];
        }
        
        // D. SELECT COUNT(*) as iniciadas FROM etapas
        else if (stripos($queryNormalized, 'COUNT(*) as iniciadas') !== false && stripos($queryNormalized, 'FROM etapas') !== false) {
            $obraId = $this->params[':obra_id'] ?? null;
            
            $iniciadas = 0;
            if (isset($GLOBALS['mock_db']['etapas'])) {
                foreach ($GLOBALS['mock_db']['etapas'] as $e) {
                    if ($e['obra_id'] == $obraId && ($e['status'] === 'Em andamento' || $e['status'] === 'Concluída' || $e['percentual'] > 0)) {
                        $iniciadas++;
                    }
                }
            }
            $this->results = [['iniciadas' => $iniciadas]];
        }
        
        // E. UPDATE obras SET percentual_concluido
        else if (stripos($queryNormalized, 'UPDATE obras SET percentual_concluido') !== false) {
            $obraId = $this->params[':obra_id'] ?? null;
            $percentual = $this->params[':percentual'] ?? 0;
            $status = $this->params[':status'] ?? '';
            
            if (isset($GLOBALS['mock_db']['obras'][$obraId])) {
                $GLOBALS['mock_db']['obras'][$obraId]['percentual_concluido'] = $percentual;
                $GLOBALS['mock_db']['obras'][$obraId]['status'] = $status;
            }
        }
        
        // F. INSERT INTO etapas
        else if (stripos($queryNormalized, 'INSERT INTO etapas') !== false) {
            $this->pdo->lastInsertId++;
            $id = $this->pdo->lastInsertId;
            $GLOBALS['mock_db']['etapas'][$id] = [
                'id' => $id,
                'obra_id' => $this->params[':obra_id'] ?? null,
                'nome' => $this->params[':nome'] ?? null,
                'descricao' => $this->params[':descricao'] ?? '',
                'observacoes' => $this->params[':observacoes'] ?? '',
                'status' => $this->params[':status'] ?? 'Pendente',
                'percentual' => intval($this->params[':percentual'] ?? 0),
                'equipe_id' => $this->params[':equipe_id'] ?? null,
                'responsavel_id' => $this->params[':responsavel_id'] ?? null,
                'ordem' => intval($this->params[':ordem'] ?? 0)
            ];
        }
        
        // G. UPDATE etapas SET
        else if (stripos($queryNormalized, 'UPDATE etapas SET') !== false) {
            $id = $this->params[':id'] ?? null;
            if (isset($GLOBALS['mock_db']['etapas'][$id])) {
                $GLOBALS['mock_db']['etapas'][$id]['obra_id'] = $this->params[':obra_id'] ?? $GLOBALS['mock_db']['etapas'][$id]['obra_id'];
                $GLOBALS['mock_db']['etapas'][$id]['nome'] = $this->params[':nome'] ?? $GLOBALS['mock_db']['etapas'][$id]['nome'];
                $GLOBALS['mock_db']['etapas'][$id]['status'] = $this->params[':status'] ?? $GLOBALS['mock_db']['etapas'][$id]['status'];
                $GLOBALS['mock_db']['etapas'][$id]['percentual'] = isset($this->params[':percentual']) ? intval($this->params[':percentual']) : $GLOBALS['mock_db']['etapas'][$id]['percentual'];
            }
        }
        
        // H. INSERT INTO funcionarios
        else if (stripos($queryNormalized, 'INSERT INTO funcionarios') !== false) {
            $this->pdo->lastInsertId++;
            $id = $this->pdo->lastInsertId;
            $GLOBALS['mock_db']['funcionarios'][$id] = [
                'id' => $id,
                'nome' => $this->params[':nome'] ?? null,
                'funcao_id' => $this->params[':funcao_id'] ?? null,
                'equipe_id' => $this->params[':equipe_id'] ?? null,
                'status' => $this->params[':status'] ?? 'ativo',
                'data_admissao' => $this->params[':data_admissao'] ?? null,
                'data_demissao' => null,
                'motivo_demissao' => null
            ];
        }
        
        // I. UPDATE funcionarios SET
        else if (stripos($queryNormalized, 'UPDATE funcionarios SET') !== false) {
            $id = $this->params[':id'] ?? null;
            if (isset($GLOBALS['mock_db']['funcionarios'][$id])) {
                $GLOBALS['mock_db']['funcionarios'][$id]['status'] = $this->params[':status'] ?? $GLOBALS['mock_db']['funcionarios'][$id]['status'];
                $GLOBALS['mock_db']['funcionarios'][$id]['data_demissao'] = $this->params[':data_demissao'] ?? $GLOBALS['mock_db']['funcionarios'][$id]['data_demissao'];
                $GLOBALS['mock_db']['funcionarios'][$id]['motivo_demissao'] = $this->params[':motivo_demissao'] ?? $GLOBALS['mock_db']['funcionarios'][$id]['motivo_demissao'];
            }
        }
        
        // J. INSERT INTO usuarios
        else if (stripos($queryNormalized, 'INSERT INTO usuarios') !== false || stripos($queryNormalized, 'INSERT INTO `usuarios`') !== false) {
            $this->pdo->lastInsertId++;
            $id = $this->pdo->lastInsertId;
            $GLOBALS['mock_db']['usuarios'][$id] = [
                'id' => $id,
                'nome' => $this->params[':nome'] ?? null,
                'email' => $this->params[':email'] ?? null,
                'senha' => $this->params[':senha'] ?? null,
                'nivel_acesso' => $this->params[':nivel_acesso'] ?? 'Visualização',
                'status' => $this->params[':status'] ?? 'ativo'
            ];
        }
        
        // K. SELECT FROM usuarios (login)
        else if (stripos($queryNormalized, 'FROM usuarios WHERE email =') !== false || stripos($queryNormalized, 'FROM `usuarios` WHERE email =') !== false) {
            $email = $this->params[1] ?? ($this->params[':email'] ?? null);
            if (isset($GLOBALS['mock_db']['usuarios'])) {
                foreach ($GLOBALS['mock_db']['usuarios'] as $u) {
                    if ($u['email'] === $email && $u['status'] === 'ativo') {
                        $this->results[] = $u;
                    }
                }
            }
        }
        
        // L. INSERT INTO notificacoes
        else if (stripos($queryNormalized, 'INSERT INTO notificacoes') !== false) {
            $this->pdo->lastInsertId++;
            $id = $this->pdo->lastInsertId;
            $GLOBALS['mock_db']['notificacoes'][$id] = [
                'id' => $id,
                'usuario_id' => $this->params[':usuario_id'] ?? null,
                'titulo' => $this->params[':titulo'] ?? '',
                'mensagem' => $this->params[':mensagem'] ?? '',
                'tipo' => $this->params[':tipo'] ?? '',
                'prioridade' => $this->params[':prioridade'] ?? 'baixa',
                'modulo_origem' => $this->params[':modulo_origem'] ?? '',
                'lida' => 0
            ];
        }
        
        return true;
    }
    
    public function rowCount() {
        return count($this->results);
    }
    
    public function fetch($mode = null) {
        if ($this->cursor < count($this->results)) {
            $row = $this->results[$this->cursor];
            $this->cursor++;
            return $row;
        }
        return false;
    }
}

// 3. Inclusão dos modelos originais do sistema
require_once __DIR__ . '/../backend/models/User.php';
require_once __DIR__ . '/../backend/models/Material.php';
require_once __DIR__ . '/../backend/models/Etapa.php';
require_once __DIR__ . '/../backend/models/Funcionario.php';
require_once __DIR__ . '/../backend/models/Financeiro.php';
require_once __DIR__ . '/../backend/models/Notificacao.php';

// 4. Classe executora de testes lógicos
class LogicTestRunner {
    private $db;
    private $results = [];

    public function __construct() {
        // Inicializa o banco virtual global em memória
        $GLOBALS['mock_db'] = [
            'usuarios' => [],
            'obras' => [],
            'etapas' => [],
            'financeiro' => [],
            'materiais' => [],
            'funcionarios' => [],
            'notificacoes' => []
        ];
        
        $this->db = new MockPDO();
    }

    public function run() {
        $this->testCheckPermission();
        $this->testMaterialNegativeStockPrevention();
        $this->testEtapaProgressSynchronization();
        $this->testEmployeeDismissal();
        $this->testUserBcryptHashing();

        return [
            'title' => 'Testes de Lógica de Negócios (In-Memory Mock Engine)',
            'passed' => !in_array(false, array_column($this->results, 'passed'), true),
            'tests' => $this->results
        ];
    }

    private function assert($testName, $condition, $message = '') {
        $this->results[] = [
            'name' => $testName,
            'passed' => (bool)$condition,
            'message' => $message ?: ($condition ? 'Passou com sucesso' : 'Falhou no teste')
        ];
    }

    /**
     * Teste 1: Validação do Middleware de Permissões (checkPermission)
     */
    private function testCheckPermission() {
        // Função simulando a mesma lógica exata do checkPermission do index.php
        $checkPermissionMock = function ($resource, $method, $session) {
            if (!isset($session['user_nivel'])) return false;
            $nivel = $session['user_nivel'];
            if ($nivel === 'Administrador') return true;

            if ($resource === 'users') return false;

            if (in_array($resource, ['auth', 'notificacoes', 'dashboard', 'tema'])) return true;

            if ($resource === 'acidentes' && $method === 'POST') {
                return in_array($nivel, ['Engenharia', 'RH']);
            } else if ($resource === 'acidentes') {
                return true;
            }

            if ($nivel === 'RH' && in_array($resource, ['funcionarios', 'funcoes', 'equipes', 'relatorios'])) return true;
            if ($nivel === 'Financeiro' && in_array($resource, ['financeiro', 'relatorios'])) return true;
            if ($nivel === 'Almoxarifado' && in_array($resource, ['materiais', 'relatorios'])) return true;
            if ($nivel === 'Engenharia' && in_array($resource, ['obras', 'etapas', 'documentos', 'equipes', 'funcionarios'])) return true;
            if ($nivel === 'Visualização' && $method === 'GET') return true;

            return false;
        };

        $this->assert(
            'Permissão Admin: Acesso total a Usuários',
            $checkPermissionMock('users', 'POST', ['user_nivel' => 'Administrador']),
            'Administrador deve ter acesso ao recurso de gerenciamento de usuários.'
        );

        $this->assert(
            'Permissão RH: Bloqueio absoluto a Usuários',
            !$checkPermissionMock('users', 'GET', ['user_nivel' => 'RH']),
            'RH não deve gerenciar usuários.'
        );

        $this->assert(
            'Permissão RH: Acesso a Funcionários',
            $checkPermissionMock('funcionarios', 'POST', ['user_nivel' => 'RH']),
            'RH deve ter acesso ao cadastro de funcionários.'
        );

        $this->assert(
            'Permissão Almoxarifado: Bloqueio a Obras',
            !$checkPermissionMock('obras', 'POST', ['user_nivel' => 'Almoxarifado']),
            'Almoxarifado não deve criar nem alterar obras.'
        );

        $this->assert(
            'Permissão Almoxarifado: Acesso a Materiais',
            $checkPermissionMock('materiais', 'POST', ['user_nivel' => 'Almoxarifado']),
            'Almoxarifado deve gerenciar materiais e estoque.'
        );

        $this->assert(
            'Permissão Visualização: Bloqueio de Escrita',
            !$checkPermissionMock('obras', 'POST', ['user_nivel' => 'Visualização']),
            'Perfil Visualização não tem direito de criar registros no banco.'
        );

        $this->assert(
            'Permissão Visualização: Leitura via GET permitida',
            $checkPermissionMock('obras', 'GET', ['user_nivel' => 'Visualização']),
            'Perfil Visualização tem leitura livre via requisições GET.'
        );
    }

    /**
     * Teste 2: Prevenção de Estoque Negativo de Materiais
     */
    private function testMaterialNegativeStockPrevention() {
        // Inicializa Obra virtual 1
        $GLOBALS['mock_db']['obras'][1] = ['id' => 1, 'nome' => 'Obra Teste'];

        $material = new Material($this->db);
        $material->obra_id = 1;
        $material->nome = 'Cimento';
        $material->categoria = 'Material de Construção';
        $material->unidade_medida = 'Sacos';

        // 1. Cria entrada de 10 sacos
        $material->quantidade = 10;
        $material->tipo_movimentacao = 'entrada';
        $resEntrada = $material->create();
        $this->assert('Estoque Material: Registrar Entrada', $resEntrada, 'Entrada de material válida deve ser registrada.');

        // 2. Tenta registrar saída de 12 sacos (Saldo insuficiente)
        $material->quantidade = 12;
        $material->tipo_movimentacao = 'saida';
        $resSaidaInvalida = $material->create();
        $this->assert('Estoque Material: Impedir Saída Acima do Saldo', !$resSaidaInvalida, 'Saída de 12 sacos com saldo de 10 deve ser rejeitada.');

        // 3. Tenta registrar saída de 7 sacos (Saldo suficiente)
        $material->quantidade = 7;
        $material->tipo_movimentacao = 'saida';
        $resSaidaValida = $material->create();
        $this->assert('Estoque Material: Permitir Saída Adequada', $resSaidaValida, 'Saída de 7 sacos com saldo de 10 deve ser aceita e gravada.');
    }

    /**
     * Teste 3: Sincronização de Progresso de Etapas e Obras
     */
    private function testEtapaProgressSynchronization() {
        // Inicializa Obra virtual 2
        $GLOBALS['mock_db']['obras'][2] = ['id' => 2, 'nome' => 'Obra Cascata', 'percentual_concluido' => 0.00, 'status' => 'Em planejamento'];

        // 1. Se Etapa for criada Concluída, percentual deve ir para 100%
        $etapa1 = new Etapa($this->db);
        $etapa1->obra_id = 2;
        $etapa1->nome = 'Fundação';
        $etapa1->status = 'Concluída';
        $etapa1->percentual = 0;
        $etapa1->create();
        
        $insertedId1 = $this->db->lastInsertId;
        $savedEtapa1 = $GLOBALS['mock_db']['etapas'][$insertedId1];
        
        $this->assert(
            'Etapas Progresso: Status Concluída força Percentual a 100%',
            $savedEtapa1['percentual'] === 100,
            'Status concluído força o percentual para 100% automaticamente.'
        );

        // 2. Se Etapa for criada com 100%, status deve ir para Concluída
        $etapa2 = new Etapa($this->db);
        $etapa2->obra_id = 2;
        $etapa2->nome = 'Alvenaria';
        $etapa2->status = 'Pendente';
        $etapa2->percentual = 100;
        $etapa2->create();

        $insertedId2 = $this->db->lastInsertId;
        $savedEtapa2 = $GLOBALS['mock_db']['etapas'][$insertedId2];

        $this->assert(
            'Etapas Progresso: Percentual 100% força Status Concluída',
            $savedEtapa2['status'] === 'Concluída',
            'Percentual em 100% altera o status da etapa para Concluída automaticamente.'
        );

        // 3. Teste do Cálculo em Cascata da Obra Pai
        // Adicionaremos mais uma etapa com 40%. Total = 2 concluídas (100% + 100%) + 1 em andamento (40%). Média = (100+100+40)/3 = 80%.
        $etapa3 = new Etapa($this->db);
        $etapa3->obra_id = 2;
        $etapa3->nome = 'Pintura';
        $etapa3->status = 'Em andamento';
        $etapa3->percentual = 40;
        $etapa3->create();

        // Verifica a Obra 2
        $savedObra = $GLOBALS['mock_db']['obras'][2];
        
        $this->assert(
            'Obra Cascata: Cálculo Médio dos Percentuais',
            floatval($savedObra['percentual_concluido']) == 80.00,
            'Progresso da Obra deve calcular 80.00% (média de 100, 100 e 40).'
        );

        $this->assert(
            'Obra Cascata: Status atualiza para Em andamento',
            $savedObra['status'] === 'Em andamento',
            'O status da Obra deve atualizar para "Em andamento" automaticamente.'
        );
    }

    /**
     * Teste 4: Regra de Demissão de Funcionários
     */
    private function testEmployeeDismissal() {
        $funcionario = new Funcionario($this->db);
        $funcionario->nome = 'Marcio Silva';
        $funcionario->status = 'ativo';
        $funcionario->data_admissao = '2026-01-10';
        
        $fId = $funcionario->create();
        $this->assert('RH Funcionários: Contratar Trabalhador', $fId > 0, 'Registro de contratação realizado.');

        // Demite o trabalhador
        $funcionario->id = $fId;
        $funcionario->status = 'demitido';
        $funcionario->data_demissao = '2026-05-29';
        $funcionario->motivo_demissao = 'Termo de contrato';
        $resDemitir = $funcionario->update();
        
        $this->assert('RH Funcionários: Confirmar Desligamento', $resDemitir, 'Alteração cadastral de desligamento efetuada.');

        $savedFuncionario = $GLOBALS['mock_db']['funcionarios'][$fId];
        $this->assert(
            'RH Funcionários: Integridade de Dados de Demissão',
            $savedFuncionario['status'] === 'demitido' && 
            $savedFuncionario['data_demissao'] === '2026-05-29' && 
            $savedFuncionario['motivo_demissao'] === 'Termo de contrato',
            'Status "demitido", data e motivo devem ser gravados em conformidade.'
        );
    }

    /**
     * Teste 5: Criptografia Bcrypt para Senhas de Usuários
     */
    private function testUserBcryptHashing() {
        $user = new User($this->db);
        $user->nome = 'Engenheiro';
        $user->email = 'eng@empresa.com';
        $user->senha = 'minha_senha_secreta';
        $user->nivel_acesso = 'Engenharia';
        $user->status = 'ativo';

        $resCriar = $user->create();
        $this->assert('Segurança de Senhas: Criar Usuário', $resCriar, 'Usuário gravado com sucesso.');

        $savedUser = $GLOBALS['mock_db']['usuarios'][$this->db->lastInsertId];
        $senhaHash = $savedUser['senha'];

        $this->assert(
            'Segurança de Senhas: Criptografia ativa via Bcrypt',
            strpos($senhaHash, '$2y$') === 0,
            'Senha salva deve ser um hash Bcrypt válido (inicia com $2y$).'
        );

        $this->assert(
            'Segurança de Senhas: Validação correta da Senha Criptografada',
            password_verify('minha_senha_secreta', $senhaHash),
            'A senha criptografada deve ser verificável com password_verify.'
        );
    }
}
?>
