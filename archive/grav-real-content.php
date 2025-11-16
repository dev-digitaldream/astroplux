<?php
// Script qui lit le vrai contenu Grav depuis les fichiers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Debug : Vérifier si YAML est disponible
if (!function_exists('yaml_parse')) {
    echo json_encode([
        'error' => 'YAML extension not available',
        'solution' => 'Use simple JSON format instead',
        'posts' => [],
        'pages' => [],
        'config' => ['site_title' => 'Site Grav']
    ]);
    exit;
}

$posts = [];
$pages = [];

// Chemins vers les pages Grav
$pagesPath = __DIR__ . '/user/pages';
$blogPath = $pagesPath . '/blog';

// Fonction pour lire une page Grav
function readGravPage($filePath) {
    if (!file_exists($filePath)) return null;
    
    $content = file_get_contents($filePath);
    $parts = explode('---', $content, 3);
    
    if (count($parts) >= 3) {
        $frontmatter = yaml_parse($parts[1]);
        $body = trim($parts[2]);
        
        return [
            'title' => $frontmatter['title'] ?? 'Sans titre',
            'slug' => basename($filePath, '.md'),
            'route' => str_replace([$pagesPath, '.md'], ['', ''], $filePath),
            'date' => $frontmatter['date'] ?? date('c'),
            'tags' => $frontmatter['tags'] ?? [],
            'header' => $frontmatter,
            'html' => $body
        ];
    }
    return null;
}

// Lire les articles du blog
if (is_dir($blogPath)) {
    foreach (glob($blogPath . '/*.md') as $file) {
        $post = readGravPage($file);
        if ($post) {
            $posts[] = $post;
        }
    }
}

// Lire les pages statiques
if (is_dir($pagesPath)) {
    foreach (glob($pagesPath . '/*.md') as $file) {
        if (strpos($file, '/blog/') !== false) continue;
        $page = readGravPage($file);
        if ($page) {
            $pages[] = $page;
        }
    }
}

// Configuration du site
$siteConfig = [
    'site_title' => 'Site Grav',
    'site_description' => 'Description du site',
    'email' => 'contact@alaoui.be'
];

// Essayer de lire la config Grav si disponible
$configFile = __DIR__ . '/user/config/site.yaml';
if (file_exists($configFile)) {
    $config = yaml_parse(file_get_contents($configFile));
    if ($config) {
        $siteConfig['site_title'] = $config['title'] ?? $siteConfig['site_title'];
        $siteConfig['site_description'] = $config['description'] ?? $siteConfig['site_description'];
        $siteConfig['email'] = $config['author']['email'] ?? $siteConfig['email'];
    }
}

$payload = [
    'config' => $siteConfig,
    'posts' => $posts,
    'pages' => $pages,
    'status' => 'success',
    'timestamp' => date('c'),
    'debug' => [
        'pages_path' => $pagesPath,
        'blog_path' => $blogPath,
        'posts_count' => count($posts),
        'pages_count' => count($pages)
    ]
];

echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
