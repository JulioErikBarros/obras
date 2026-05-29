<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/Dashboard.php';
require_once 'models/Obra.php';
require_once 'models/Etapa.php';
require_once 'models/Financeiro.php';
require_once 'models/Material.php';
require_once 'models/Funcionario.php';
require_once 'models/Documento.php';
require_once 'models/Notificacao.php';
require_once 'models/Relatorio.php';
require_once 'models/Acidente.php';

require_once 'models/Funcao.php';
require_once 'models/Equipe.php';

require_once 'controllers/UserController.php';
require_once 'controllers/DashboardController.php';
require_once 'controllers/AcidenteController.php';
require_once 'controllers/FuncaoController.php';
require_once 'controllers/EquipeController.php';
require_once 'controllers/ObraController.php';
require_once 'controllers/EtapaController.php';
require_once 'controllers/FinanceiroController.php';
require_once 'controllers/MaterialController.php';
require_once 'controllers/FuncionarioController.php';
require_once 'controllers/DocumentoController.php';
require_once 'controllers/NotificacaoController.php';
require_once 'controllers/RelatorioController.php';

$database = new Database();
$db = $database->getConnection();

// Suporte para query string (ex: ?resource=auth&id=1) ou PATH_INFO (ex: /backend/index.php/auth/1)
if (isset($_GET['resource'])) {
    $resource = $_GET['resource'];
    $id = isset($_GET['id']) ? $_GET['id'] : null;
} else {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = explode('/', trim($uri, '/'));

    // Tenta encontrar 'backend' na URI ou 'index.php'
    $baseIndex = array_search('index.php', $uri);
    if ($baseIndex === false) {
        $baseIndex = array_search('backend', $uri);
    }

    if ($baseIndex === false) {
        $resource = isset($uri[0]) ? $uri[0] : null;
        $id = isset($uri[1]) ? $uri[1] : null;
    } else {
        $resource = isset($uri[$baseIndex + 1]) ? $uri[$baseIndex + 1] : null;
        $id = isset($uri[$baseIndex + 2]) ? $uri[$baseIndex + 2] : null;
    }
}

$method = $_SERVER["REQUEST_METHOD"];

// Middleware de Permissões Básicas Restritas
function checkPermission($resource, $method) {
    if (!isset($_SESSION['user_nivel'])) return false;
    $nivel = $_SESSION['user_nivel'];
    if ($nivel === 'Administrador') return true;

    // Restrição absoluta: gerenciamento de usuários é exclusivo do Administrador
    if ($resource === 'users') return false;

    // Acesso comum a todos logados
    if (in_array($resource, ['auth', 'notificacoes', 'dashboard', 'tema'])) return true;

    // Resetar acidentes apenas Admin, Engenharia e RH
    if ($resource === 'acidentes' && $method === 'POST') {
        return in_array($nivel, ['Engenharia', 'RH']);
    } else if ($resource === 'acidentes') {
        return true;
    }

    if ($nivel === 'RH' && in_array($resource, ['funcionarios', 'funcoes', 'equipes', 'relatorios'])) return true;
    if ($nivel === 'Financeiro' && in_array($resource, ['financeiro', 'relatorios'])) return true;
    if ($nivel === 'Almoxarifado' && in_array($resource, ['materiais', 'relatorios'])) return true;
    if ($nivel === 'Engenharia' && in_array($resource, ['obras', 'etapas', 'documentos', 'equipes', 'funcionarios'])) return true;
    if ($nivel === 'Visualização' && $method === 'GET') return true; // Somente leitura

    return false;
}

if ($resource !== 'auth' && !checkPermission($resource, $method)) {
    http_response_code(403);
    echo json_encode(["message" => "Acesso negado. Você não tem permissão para esta ação."]);
    exit();
}

switch ($resource) {
    case 'auth':
    case 'users':
    case 'tema':
        $controller = new UserController($db);
        $controller->processRequest($method, $id, $resource);
        break;
    case 'funcoes':
        $controller = new FuncaoController($db);
        $controller->processRequest($method, $id);
        break;
    case 'equipes':
        $controller = new EquipeController($db);
        $controller->processRequest($method, $id);
        break;
    case 'acidentes':
        $controller = new AcidenteController($db);
        $controller->processRequest($method, $id);
        break;
    case 'dashboard':
        $controller = new DashboardController($db);
        $controller->processRequest($method, $id);
        break;
    case 'obras':
        $controller = new ObraController($db);
        $controller->processRequest($method, $id);
        break;
    case 'etapas':
        $controller = new EtapaController($db);
        $controller->processRequest($method, $id);
        break;
    case 'financeiro':
        $controller = new FinanceiroController($db);
        $controller->processRequest($method, $id);
        break;
    case 'materiais':
        $controller = new MaterialController($db);
        $controller->processRequest($method, $id);
        break;
    case 'funcionarios':
    case 'demitir':
        $controller = new FuncionarioController($db);
        $controller->processRequest($method, $id, $resource);
        break;
    case 'documentos':
        $controller = new DocumentoController($db);
        $controller->processRequest($method, $id);
        break;
    case 'notificacoes':
        $controller = new NotificacaoController($db);
        $controller->processRequest($method, $id);
        break;
    case 'relatorios':
        $controller = new RelatorioController($db);
        $controller->processRequest($method, $id);
        break;
    default:
        http_response_code(404);
        echo json_encode(["message" => "Recurso não encontrado."]);
        break;
}
?>
