$pages_dir = "c:\Users\jobs\Documents\obras-main\obras-main\frontend"
$files = Get-ChildItem -Path "$pages_dir\pages\*.html", "$pages_dir\js\*.js"

# We define the specific double-encoded string mappings to their proper UTF-8 representation
$mappings = @(
    @{ Pattern = "FuncionÃ¡rios"; Replacement = "Funcionários" },
    @{ Pattern = "funcionÃ¡rios"; Replacement = "funcionários" },
    @{ Pattern = "FuncionÃ¡rio"; Replacement = "Funcionário" },
    @{ Pattern = "funcionÃ¡rio"; Replacement = "funcionário" },
    @{ Pattern = "FunÃ§Ãµes"; Replacement = "Funções" },
    @{ Pattern = "funÃ§Ãµes"; Replacement = "funções" },
    @{ Pattern = "FunÃ§Ã£o"; Replacement = "Função" },
    @{ Pattern = "funÃ§Ã£o"; Replacement = "função" },
    @{ Pattern = "RelatÃ³rios"; Replacement = "Relatórios" },
    @{ Pattern = "relatÃ³rios"; Replacement = "relatórios" },
    @{ Pattern = "RelatÃ³rio"; Replacement = "Relatório" },
    @{ Pattern = "relatÃ³rio"; Replacement = "relatório" },
    @{ Pattern = "NotificaÃ§Ãµes"; Replacement = "Notificações" },
    @{ Pattern = "notificaÃ§Ãµes"; Replacement = "notificações" },
    @{ Pattern = "NotificaÃ§Ã£o"; Replacement = "Notificação" },
    @{ Pattern = "notificaÃ§Ã£o"; Replacement = "notificação" },
    @{ Pattern = "UsuÃ¡rios"; Replacement = "Usuários" },
    @{ Pattern = "usuÃ¡rios"; Replacement = "usuários" },
    @{ Pattern = "UsuÃ¡rio"; Replacement = "Usuário" },
    @{ Pattern = "usuÃ¡rio"; Replacement = "usuário" },
    @{ Pattern = "SeguranÃ§a"; Replacement = "Segurança" },
    @{ Pattern = "seguranÃ§a"; Replacement = "segurança" },
    @{ Pattern = "prevenÃ§Ã£o"; Replacement = "prevenção" },
    @{ Pattern = "PrevenÃ§Ã£o"; Replacement = "Prevenção" },
    @{ Pattern = "OCORRÃŠNCIA"; Replacement = "OCORRÊNCIA" },
    @{ Pattern = "OcorrÃªncia"; Replacement = "Ocorrência" },
    @{ Pattern = "ocorrÃªncia"; Replacement = "ocorrência" },
    @{ Pattern = "MÃªs"; Replacement = "Mês" },
    @{ Pattern = "mÃªs"; Replacement = "mês" },
    @{ Pattern = "MÃŠS"; Replacement = "MÊS" },
    @{ Pattern = "AÃ§Ãµes"; Replacement = "Ações" },
    @{ Pattern = "aÃ§Ãµes"; Replacement = "ações" },
    @{ Pattern = "AÃ§Ã£o"; Replacement = "Ação" },
    @{ Pattern = "aÃ§Ã£o"; Replacement = "ação" },
    @{ Pattern = "GestÃ£o"; Replacement = "Gestão" },
    @{ Pattern = "gestÃ£o"; Replacement = "gestão" },
    @{ Pattern = "AdmissÃ£o"; Replacement = "Admissão" },
    @{ Pattern = "admissÃ£o"; Replacement = "admissão" },
    @{ Pattern = "DemissÃ£o"; Replacement = "Demissão" },
    @{ Pattern = "demissÃ£o"; Replacement = "demissão" },
    @{ Pattern = "OrÃ§amento"; Replacement = "Orçamento" },
    @{ Pattern = "orÃ§amento"; Replacement = "orçamento" },
    @{ Pattern = "ConcluÃ­da"; Replacement = "Concluída" },
    @{ Pattern = "concluÃ­da"; Replacement = "concluída" },
    @{ Pattern = "DescriÃ§Ã£o"; Replacement = "Descrição" },
    @{ Pattern = "descriÃ§Ã£o"; Replacement = "descrição" },
    @{ Pattern = "EndereÃ§o"; Replacement = "Endereço" },
    @{ Pattern = "endereÃ§o"; Replacement = "endereço" },
    @{ Pattern = "ObservaÃ§Ãµes"; Replacement = "Observações" },
    @{ Pattern = "observaÃ§Ãµes"; Replacement = "observações" },
    @{ Pattern = "AdministraÃ§Ã£o"; Replacement = "Administração" },
    @{ Pattern = "administraÃ§Ã£o"; Replacement = "administração" },
    @{ Pattern = "GrÃ¡ficos"; Replacement = "Gráficos" },
    @{ Pattern = "grÃ¡ficos"; Replacement = "gráficos" },
    @{ Pattern = "ConfiguraÃ§Ãµes"; Replacement = "Configurações" },
    @{ Pattern = "configuraÃ§Ãµes"; Replacement = "configurações" },
    @{ Pattern = "PadrÃ£o"; Replacement = "Padrão" },
    @{ Pattern = "padrÃ£o"; Replacement = "padrão" }
)

foreach ($file in $files) {
    # Read raw content using default system encoding to capture original bytes correctly
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::Default)
    
    $modified = $false
    foreach ($map in $mappings) {
        if ($content.Contains($map.Pattern)) {
            $content = $content.Replace($map.Pattern, $map.Replacement)
            $modified = $true
        }
    }
    
    if ($modified) {
        # Save explicitly as UTF-8 so it renders perfectly on any server
        [System.IO.File]::WriteAllText($file.FullName, $content, [System.Text.Encoding]::UTF8)
        Write-Host "Fixed encoding corruptions in $($file.Name)"
    } else {
        Write-Host "No corruptions matched in $($file.Name)"
    }
}
