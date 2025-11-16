<?php
// Script simple d'export direct - sans plugin Grav
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuration simple
$payload = [
    'config' => [
        'site_title' => 'Site Grav',
        'site_description' => 'Description du site',
        'email' => 'contact@alaoui.be'
    ],
    'posts' => [
        [
            'title' => 'Article de test',
            'slug' => 'article-test',
            'route' => '/blog/article-test',
            'date' => date('c'),
            'tags' => ['test', 'grav'],
            'html' => '<p>Contenu de test depuis Grav</p>'
        ]
    ],
    'pages' => [
        [
            'title' => 'Page d\'accueil',
            'slug' => 'accueil',
            'route' => '/',
            'html' => '<p>Bienvenue sur notre site</p>'
        ]
    ],
    'status' => 'success',
    'timestamp' => date('c')
];

echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
