<?php
/**
 * Analisador de Qualidade Estática e Segurança
 * Parte da suíte de testes do ERP Controle de Obras
 */

class QualityAnalyzer {
    private $baseDir;

    public function __construct($baseDir) {
        $this->baseDir = rtrim($baseDir, '/\\');
    }

    public function run() {
        return [
            'linter' => $this->runPHPSelectionLinter(),
            'security' => $this->runSecurityChecks(),
            'html_semantics' => $this->runHtmlSemanticsAndIdChecks()
        ];
    }

    /**
     * 1. Linter de Sintaxe PHP
     * Varre todos os arquivos PHP do backend e executa php -l neles.
     */
    private function runPHPSelectionLinter() {
        $dirs = [
            $this->baseDir . '/backend',
            $this->baseDir . '/scratch'
        ];

        $files = [];
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $files = array_merge($files, $this->rglob($dir, '*.php'));
            }
        }

        $failedFiles = [];
        $passedCount = 0;

        foreach ($files as $file) {
            // Executa php -l no arquivo
            $output = [];
            $returnVar = 0;
            exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $returnVar);

            if ($returnVar !== 0) {
                $failedFiles[basename($file)] = implode("\n", $output);
            } else {
                $passedCount++;
            }
        }

        return [
            'title' => 'Linter de Sintaxe PHP',
            'passed' => empty($failedFiles),
            'total_checked' => count($files),
            'passed_count' => $passedCount,
            'failed_files' => $failedFiles
        ];
    }

    /**
     * 2. Varreduras de Segurança e Práticas Recomendadas
     */
    private function runSecurityChecks() {
        $checks = [];

        // A. Verificação do DocumentoController (Upload Seguro / Prevenção de RCE)
        $docControllerPath = $this->baseDir . '/backend/controllers/DocumentoController.php';
        if (file_exists($docControllerPath)) {
            $content = file_get_contents($docControllerPath);
            
            // Verifica se valida MIME real via finfo
            $hasFinfo = strpos($content, 'finfo_file') !== false || strpos($content, 'finfo_open') !== false;
            // Verifica limite de 5MB
            $hasSizeLimit = strpos($content, '5 * 1024 * 1024') !== false || strpos($content, '5242880') !== false;
            // Verifica whitelist de extensões
            $hasExtWhitelist = strpos($content, '$allowedExtensions') !== false && strpos($content, 'pdf') !== false;

            $checks['upload_security'] = [
                'name' => 'Segurança de Upload de Arquivos (Prevenção de RCE)',
                'passed' => $hasFinfo && $hasSizeLimit && $hasExtWhitelist,
                'details' => [
                    'valida_mime_real_finfo' => $hasFinfo,
                    'valida_limite_tamanho_5mb' => $hasSizeLimit,
                    'whitelist_extensoes_seguras' => $hasExtWhitelist
                ]
            ];
        } else {
            $checks['upload_security'] = [
                'name' => 'Segurança de Upload de Arquivos',
                'passed' => false,
                'error' => 'Arquivo DocumentoController.php não encontrado.'
            ];
        }

        // B. Verificação de Injeção de SQL (Uso correto de PDO prepare/bindParam)
        $modelDir = $this->baseDir . '/backend/models';
        $sqlInjectionPassed = true;
        $vulnerableModels = [];

        if (is_dir($modelDir)) {
            $modelFiles = $this->rglob($modelDir, '*.php');
            foreach ($modelFiles as $file) {
                $content = file_get_contents($file);
                
                // Padrões vulneráveis comuns: concatenação direta de variáveis em queries do PDO
                // ex: $this->conn->prepare("... WHERE id = " . $this->id)
                // ex: $this->conn->query("... $variable ...")
                // Vamos procurar por prepare/query contendo variáveis concatenadas ou interpoladas diretamente
                $hasDirectConcatenation = preg_match('/->(?:prepare|query|exec)\s*\(\s*["\'].*?\$[a-zA-Z_0-9\-]+.*?["\']\s*\)/i', $content) ||
                                           preg_match('/->(?:prepare|query|exec)\s*\(\s*["\'].*?["\']\s*\.\s*\$[a-zA-Z_0-9\-]+/i', $content);
                
                if ($hasDirectConcatenation) {
                    $sqlInjectionPassed = false;
                    $vulnerableModels[] = basename($file);
                }
            }
        }

        $checks['sql_injection_prevention'] = [
            'name' => 'Prevenção de SQL Injection (PDO Prepared Statements)',
            'passed' => $sqlInjectionPassed,
            'details' => [
                'vulnerabilidades_detectadas' => $vulnerableModels,
                'mensagem' => $sqlInjectionPassed ? 'Todos os modelos utilizam PDO prepare e bindParam/execute corretamente.' : 'Modelos potencialmente inseguros detectados.'
            ]
        ];

        // C. Verificação de Middleware e Sessões (Validação de acesso restrito e autenticação)
        $routerPath = $this->baseDir . '/backend/index.php';
        $sessionChecksPassed = false;
        $detailsSession = [];

        if (file_exists($routerPath)) {
            $content = file_get_contents($routerPath);
            $hasSessionStart = strpos($content, 'session_start(') !== false;
            $hasCheckPermission = strpos($content, 'function checkPermission') !== false;
            $hasPermissionCall = strpos($content, 'checkPermission($resource, $method)') !== false;

            $sessionChecksPassed = $hasSessionStart && $hasCheckPermission && $hasPermissionCall;
            $detailsSession = [
                'inicia_sessao' => $hasSessionStart,
                'possui_middleware_permissao' => $hasCheckPermission,
                'aplica_middleware_rotas' => $hasPermissionCall
            ];
        }

        $checks['authentication_middleware'] = [
            'name' => 'Middleware de Autenticação e Controle de Sessão',
            'passed' => $sessionChecksPassed,
            'details' => $detailsSession
        ];

        return [
            'title' => 'Varredura de Segurança e Controles de Entrada',
            'passed' => !in_array(false, array_column($checks, 'passed'), true),
            'checks' => $checks
        ];
    }

    /**
     * 3. Análise de Semântica HTML e IDs exclusivos no Frontend
     */
    private function runHtmlSemanticsAndIdChecks() {
        $htmlDir = $this->baseDir . '/frontend';
        $files = [];
        
        if (is_dir($htmlDir)) {
            $files[] = $htmlDir . '/index.html';
            $pagesDir = $htmlDir . '/pages';
            if (is_dir($pagesDir)) {
                $files = array_merge($files, $this->rglob($pagesDir, '*.html'));
            }
        }

        $semanticElements = ['header', 'nav', 'main', 'footer', 'article', 'section', 'aside'];
        $report = [];
        $allPassed = true;

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $filename = basename($file);

            // A. Verificação de semântica HTML
            $foundSemantics = [];
            foreach ($semanticElements as $element) {
                if (preg_match("/<{$element}[\s>]/i", $content)) {
                    $foundSemantics[] = $element;
                }
            }

            // B. Verificação de IDs duplicados
            $duplicatedIds = [];
            if (preg_match_all('/id=["\'](.*?)["\']/i', $content, $matches)) {
                $ids = $matches[1];
                $counts = array_count_values($ids);
                foreach ($counts as $id => $count) {
                    if ($count > 1) {
                        $duplicatedIds[] = "$id ($count vezes)";
                    }
                }
            }

            // C. Verificação de IDs em elementos de ação (botões, inputs)
            $inputsWithoutId = 0;
            if (preg_match_all('/<(input|button|select|textarea)([\s>][^>]*?)/i', $content, $elements)) {
                foreach ($elements[2] as $attrs) {
                    if (strpos($attrs, 'type="hidden"') !== false || strpos($attrs, "type='hidden'") !== false) {
                        continue; // ignora inputs ocultos
                    }
                    if (strpos($attrs, 'id=') === false) {
                        $inputsWithoutId++;
                    }
                }
            }

            $passedFile = empty($duplicatedIds) && count($foundSemantics) > 0;
            if (!$passedFile) {
                $allPassed = false;
            }

            $report[$filename] = [
                'passed' => $passedFile,
                'elementos_semanticos' => $foundSemantics,
                'ids_duplicados' => $duplicatedIds,
                'controles_sem_id' => $inputsWithoutId
            ];
        }

        return [
            'title' => 'Semântica HTML e IDs de Teste Unificados',
            'passed' => $allPassed,
            'details' => $report
        ];
    }

    /**
     * Função auxiliar para varredura recursiva de arquivos com padrão glob
     */
    private function rglob($pattern, $globPattern) {
        $files = glob($pattern . '/' . $globPattern);
        $dirs = glob($pattern . '/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $files = array_merge($files, $this->rglob($dir, $globPattern));
        }
        return $files;
    }
}
?>
