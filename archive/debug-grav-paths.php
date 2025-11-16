<?php
// Script de debug pour vérifier les chemins Grav
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$debug = [];

// Chemin de base
$basePath = __DIR__;
$debug['base_path'] = $basePath;

// Vérifier les dossiers
$pagesPath = $basePath . '/user/pages';
$blogPath = $pagesPath . '/blog';

$debug['pages_path'] = $pagesPath;
$debug['pages_exists'] = is_dir($pagesPath);
$debug['blog_path'] = $blogPath;
$debug['blog_exists'] = is_dir($blogPath);

// Lister les fichiers si les dossiers existent
if (is_dir($pagesPath)) {
    $debug['pages_files'] = glob($pagesPath . '/*.md');
    $debug['pages_count'] = count($debug['pages_files']);
}

if (is_dir($blogPath)) {
    $debug['blog_files'] = glob($blogPath . '/*.md');
    $debug['blog_count'] = count($debug['blog_files']);
}

// Vérifier le dossier user
$userPath = $basePath . '/user';
$debug['user_path'] = $userPath;
$debug['user_exists'] = is_dir($userPath);

if (is_dir($userPath)) {
    $debug['user_contents'] = scandir($userPath);
}

// Test de lecture d'un fichier exemple
if (!empty($debug['blog_files'])) {
    $testFile = $debug['blog_files'][0];
    $debug['test_file'] = $testFile;
    $debug['test_file_exists'] = file_exists($testFile);
    if (file_exists($testFile)) {
        $debug['test_file_content'] = substr(file_get_contents($testFile), 0, 200);
    }
}

echo json_encode($debug, JSON_PRETTY_PRINT);
?>
