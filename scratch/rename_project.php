<?php
$dir = __DIR__ . '/../frontend/pages';
$files = glob("$dir/*.html");

foreach ($files as $file) {
    echo "Processing $file...\n";
    $content = file_get_contents($file);
    
    // Literal replacements
    $content = str_replace(' - ERP Obras</title>', ' - Caba Desenrolado ERP</title>', $content);
    $content = str_replace('ERP Obras</h4>', 'Caba Desenrolado ERP</h4>', $content);
    $content = str_replace('Gestor do ERP Obras', 'Gestor do Caba Desenrolado ERP', $content);
    $content = str_replace('ERP Obras &copy;', 'Caba Desenrolado ERP &copy;', $content);
    
    file_put_contents($file, $content);
}
echo "Done!\n";
