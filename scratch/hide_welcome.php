<?php
$dir = __DIR__ . '/../frontend/pages';
$files = glob("$dir/*.html");

foreach ($files as $file) {
    if (basename($file) === 'dashboard.html') {
        echo "Skipping dashboard.html...\n";
        continue;
    }
    
    echo "Processing $file...\n";
    $content = file_get_contents($file);
    
    // Replace the welcome small tag including any spacing/newlines around it
    // Standard format: <small>Bem-vindo, <span id="userName" class="fw-bold"></span></small>
    // Note: Some files might have different characters like Funcionários instead of Funcionarios in path but we glob all.
    
    $targetLine = '<small>Bem-vindo, <span id="userName" class="fw-bold"></span></small>';
    $content = str_replace($targetLine, '', $content);
    
    file_put_contents($file, $content);
}
echo "Done!\n";
