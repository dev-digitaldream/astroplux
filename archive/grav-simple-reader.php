<?php
// Script simple qui lit les pages Grav sans YAML
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$posts = [];
$pages = [];

// Chemins vers les pages Grav
$pagesPath = __DIR__ . '/user/pages';
$blogPath = $pagesPath . '/blog';

// Fonction simple pour lire une page Grav
function readGravPageSimple($filePath) {
    if (!file_exists($filePath)) return null;
    
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    
    $title = 'Sans titre';
    $date = date('c');
    $tags = [];
    
    // Parser simple du frontmatter
    $inFrontmatter = false;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '---') {
            $inFrontmatter = !$inFrontmatter;
            continue;
        }
        if ($inFrontmatter) {
            if (strpos($line, 'title:') === 0) {
                $title = trim(substr($line, 6), ' "');
            } elseif (strpos($line, 'date:') === 0) {
                $date = trim(substr($line, 5), ' "');
            } elseif (strpos($line, 'tags:') === 0) {
                $tags = [trim(substr($line, 5), ' []"')];
            }
        }
    }
    
    // Extraire le contenu HTML
    $parts = explode('---', $content, 3);
    $body = isset($parts[2]) ? trim($parts[2]) : $content;
    
    return [
        'title' => $title,
        'slug' => basename($filePath, '.md'),
        'route' => str_replace([$pagesPath, '.md'], ['', ''], $filePath),
        'date' => $date,
        'tags' => $tags,
        'header' => ['title' => $title, 'date' => $date],
        'html' => $body
    ];
}

// Lire les articles du blog
if (is_dir($blogPath)) {
    foreach (glob($blogPath . '/*.md') as $file) {
        $post = readGravPageSimple($file);
        if ($post) {
            $posts[] = $post;
        }
    }
}

// Lire les pages statiques
if (is_dir($pagesPath)) {
    foreach (glob($pagesPath . '/*.md') as $file) {
        if (strpos($file, '/blog/') !== false) continue;
        $page = readGravPageSimple($file);
        if ($page) {
            $pages[] = $page;
        }
    }
}

$payload = [
    'config' => [
        'site_title' => 'Site Grav',
        'site_description' => 'Description depuis Grav',
        'email' => 'contact@alaoui.be'
    ],
    'posts' => $posts,
    'pages' => $pages,
    'status' => 'success',
    'timestamp' => date('c'),
    'debug' => [
        'posts_count' => count($posts),
        'pages_count' => count($pages),
        'blog_path' => $blogPath
    ]
];

echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
