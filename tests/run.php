<?php
/**
 * Ponto de entrada unificado da Suíte de Testes
 * Executa todos os testes de qualidade e lógica de negócios.
 */

// Garante que o PHP exiba erros durante a execução dos testes
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/quality_analyzer.php';
require_once __DIR__ . '/logic_test_runner.php';

echo "============================================================\n";
echo "    INICIANDO SUÍTE DE TESTES: ERP CONTROLE DE OBRAS\n";
echo "============================================================\n\n";

$baseDir = __DIR__ . '/..';

// 1. Executa Testes de Qualidade Estática e Segurança
echo "[1/2] Executando Análises de Qualidade e Segurança...\n";
$qualityAnalyzer = new QualityAnalyzer($baseDir);
$qualityResults = $qualityAnalyzer->run();
echo "-> Concluído!\n\n";

// 2. Executa Testes de Lógica de Negócios (SQLite em Memória)
echo "[2/2] Executando Testes de Lógica de Negócios (In-Memory)...\n";
$logicRunner = new LogicTestRunner();
$logicResults = $logicRunner->run();
echo "-> Concluído!\n\n";

// 3. Consolidação e Formatação do Relatório
echo "============================================================\n";
echo "                 RESULTADO CONSOLIDADO\n";
echo "============================================================\n\n";

$allPassed = true;
$totalAssertions = 0;
$passedAssertions = 0;
$failedAssertions = 0;

// A. Relatório de Qualidade
echo "### 1. ANÁLISE DE QUALIDADE E SEGURANÇA ESTÁTICA\n\n";

// Linter
$linter = $qualityResults['linter'];
$statusLinter = $linter['passed'] ? "PASSOOU" : "FALHOU";
echo "  [Linter de Sintaxe PHP]: {$statusLinter}\n";
echo "    - Total de arquivos checados: {$linter['total_checked']}\n";
echo "    - Arquivos corretos: {$linter['passed_count']}\n";
if (!$linter['passed']) {
    $allPassed = false;
    echo "    - Arquivos com erro de sintaxe:\n";
    foreach ($linter['failed_files'] as $file => $error) {
        echo "      * $file: " . trim($error) . "\n";
    }
}
$totalAssertions++;
if ($linter['passed']) { $passedAssertions++; } else { $failedAssertions++; }

// Segurança
$sec = $qualityResults['security'];
$statusSec = $sec['passed'] ? "PASSOOU" : "FALHOU";
echo "\n  [Segurança e Controles de Entrada]: {$statusSec}\n";
foreach ($sec['checks'] as $key => $check) {
    $cStatus = $check['passed'] ? "[OK]" : "[FALHA]";
    echo "    - $cStatus {$check['name']}\n";
    if (isset($check['details']) && is_array($check['details'])) {
        foreach ($check['details'] as $detailKey => $detailVal) {
            $valStr = is_bool($detailVal) ? ($detailVal ? 'Sim' : 'Não') : (is_array($detailVal) ? implode(', ', $detailVal) : $detailVal);
            if ($detailVal === []) { $valStr = 'Nenhuma'; }
            echo "        * " . str_replace('_', ' ', ucfirst($detailKey)) . ": $valStr\n";
        }
    }
    $totalAssertions++;
    if ($check['passed']) { $passedAssertions++; } else { $failedAssertions++; }
}
if (!$sec['passed']) { $allPassed = false; }

// HTML Semântico
$html = $qualityResults['html_semantics'];
$statusHtml = $html['passed'] ? "PASSOOU" : "FALHOU";
echo "\n  [Estruturas de Frontend HTML]: {$statusHtml}\n";
foreach ($html['details'] as $file => $details) {
    $fStatus = $details['passed'] ? "[OK]" : "[FALHA]";
    echo "    - $fStatus Arquivo: $file\n";
    echo "        * Tags semânticas encontradas: " . implode(', ', $details['elementos_semanticos']) . "\n";
    if (!empty($details['ids_duplicados'])) {
        echo "        * IDs DUPLICADOS DETECTADOS: " . implode(', ', $details['ids_duplicados']) . "\n";
    }
    if ($details['controles_sem_id'] > 0) {
        echo "        * Elementos interativos sem ID (alerta): {$details['controles_sem_id']} elementos\n";
    }
}
$totalAssertions++;
if ($html['passed']) { $passedAssertions++; } else { $failedAssertions++; }
if (!$html['passed']) { $allPassed = false; }


// B. Relatório de Lógica
echo "\n### 2. TESTES DE LÓGICA DE NEGÓCIOS (UNITÁRIOS)\n\n";
$logicTests = $logicResults['tests'];
foreach ($logicTests as $test) {
    $tStatus = $test['passed'] ? "[PASSOU]" : "[FALHOU]";
    echo "  $tStatus {$test['name']}\n";
    echo "    - Relação: {$test['message']}\n";
    
    $totalAssertions++;
    if ($test['passed']) { $passedAssertions++; } else { $failedAssertions++; }
}
if (!$logicResults['passed']) { $allPassed = false; }


// Estatísticas Finais
echo "\n============================================================\n";
echo "                     ESTATÍSTICAS FINAIS\n";
echo "============================================================\n";
echo "  Total de Validações Realizadas : $totalAssertions\n";
echo "  Validações Bem-Sucedidas       : $passedAssertions\n";
echo "  Validações Falhas              : $failedAssertions\n";
echo "============================================================\n";

if ($allPassed) {
    echo "  STATUS FINAL DA SUÍTE: APROVADO [SUCESSO]\n";
} else {
    echo "  STATUS FINAL DA SUÍTE: REPROVADO [FALHAS ENCONTRADAS]\n";
}
echo "============================================================\n";

// Salva o relatório consolidado em JSON para auditoria do agente
$relatorioJson = [
    'timestamp' => date('c'),
    'sucesso_geral' => $allPassed,
    'total_assercoes' => $totalAssertions,
    'passed_assercoes' => $passedAssertions,
    'failed_assercoes' => $failedAssertions,
    'qualidade' => $qualityResults,
    'logica' => $logicResults
];
file_put_contents(__DIR__ . '/relatorio_testes.json', json_encode($relatorioJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
?>
