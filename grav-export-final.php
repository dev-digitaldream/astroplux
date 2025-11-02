<?php
// Script final d'export Grav pour Cloudflare Pages
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Essayons de charger Grav pour récupérer le vrai contenu
$gravLoaded = false;
$posts = [];
$pages = [];

try {
    // Tentative de chargement de Grav
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require __DIR__ . '/vendor/autoload.php';
        use Grav\Common\Grav;
        
        $grav = Grav::instance();
        if (isset($grav['pages'])) {
            $pagesObj = $grav['pages'];
            $site = $grav['config']->get('site', []);
            $blogRoot = '/blog';
            
            // Récupérer les articles du blog
            $blog = $pagesObj->find($blogRoot);
            if ($blog) {
                foreach ($blog->children() as $child) {
                    $header = $child->header();
                    $posts[] = [
                        'title' => $child->title(),
                        'slug' => $child->slug(),
                        'route' => $child->route(),
                        'date' => $child->date() ? date('c', $child->date()) : null,
                        'tags' => $header->taxonomy['tag'] ?? [],
                        'header' => $header,
                        'html' => $child->content()
                    ];
                }
            }
            
            // Récupérer les pages statiques
            foreach ($pagesObj->root()->children() as $p) {
                if ($p->route() === $blogRoot) continue;
                $header = $p->header();
                $pages[] = [
                    'title' => $p->title(),
                    'slug' => $p->slug(),
                    'route' => $p->route(),
                    'header' => $header,
                    'html' => $p->content()
                ];
            }
            
            $gravLoaded = true;
        }
    }
} catch (Exception $e) {
    // Grav non disponible, utilisation des données de test
    error_log("Grav export: " . $e->getMessage());
}

// Si Grav n'est pas chargé, utilisons des données de test
if (!$gravLoaded) {
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
                'header' => ['date' => date('Y-m-d')],
                'html' => '<p>Contenu de test depuis Grav</p>'
            ]
        ],
        'pages' => [
            [
                'title' => 'Page d\'accueil',
                'slug' => 'accueil',
                'route' => '/',
                'header' => [],
                'html' => '<p>Bienvenue sur notre site</p>'
            ]
        ],
        'status' => 'test_data',
        'timestamp' => date('c')
    ];
} else {
    $payload = [
        'config' => [
            'site_title' => $site['title'] ?? 'Site Grav',
            'site_description' => $site['description'] ?? 'Description du site',
            'email' => $site['author']['email'] ?? 'contact@alaoui.be'
        ],
        'posts' => $posts,
        'pages' => $pages,
        'status' => 'success',
        'timestamp' => date('c')
    ];
}

echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
