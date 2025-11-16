<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;

class AstroExportPlugin extends Plugin
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
        ];
    }

    public function onPluginsInitialized(): void
    {
        if ($this->isAdmin()) {
            return;
        }

        $enabled = (bool)$this->config->get('plugins.astro-export.enabled', true);
        if (!$enabled) {
            return;
        }

        // Debug: Log that plugin is loaded
        error_log("AstroExport plugin loaded and enabled");

        $this->enable([
            'onPageInitialized' => ['onPageInitialized', 0],
        ]);
    }

    public function onPageInitialized(): void
    {
        if ($this->isAdmin()) {
            return;
        }

        $cfgRoute = (string)$this->config->get('plugins.astro-export.route', '/astro-export.json');
        
        // Debug: Log route checking
        error_log("AstroExport checking route: " . $cfgRoute);
        
        $normalize = static function ($s) {
            $s = ($s ?? '');
            $s = '/' . ltrim($s, '/');
            return rtrim($s, '/');
        };

        $want = $normalize($cfgRoute);
        $uri  = $this->grav['uri'];
        $path = $normalize($uri->path());
        $route= $normalize($uri->route());

        // Debug: Log paths
        error_log("AstroExport - want: " . $want . ", path: " . $path . ", route: " . $route);

        if ($path === $want || $route === $want) {
            error_log("AstroExport - Route matched! Exporting JSON");
            $this->exportJson();
        }
    }

    private function exportJson(): void
    {
        error_log("AstroExport - Starting JSON export");
        
        $grav = $this->grav;
        $pages = $grav['pages'];
        $site = $grav['config']->get('site', []);
        $blogRoot = (string)$this->config->get('plugins.astro-export.blog_root', '/blog');
        $includeHtml = (bool)$this->config->get('plugins.astro-export.include_html', true);

        // Set CORS headers for Cloudflare Pages
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Accept');
        
        // Handle OPTIONS preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        // Simple test response first
        $payload = [
            'config' => [
                'site_title' => $site['title'] ?? 'Test Site',
                'site_description' => $site['description'] ?? 'Test Description',
                'email' => $site['author']['email'] ?? 'test@example.com',
            ],
            'posts' => [
                [
                    'title' => 'Test Post',
                    'slug' => 'test-post',
                    'route' => '/blog/test-post',
                    'date' => date('c'),
                    'tags' => ['test'],
                    'html' => '<p>Test content</p>'
                ]
            ],
            'pages' => [],
            'debug' => [
                'timestamp' => date('c'),
                'blog_root' => $blogRoot,
                'include_html' => $includeHtml
            ]
        ];

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        error_log("AstroExport - JSON exported successfully");
        exit;
    }
}
