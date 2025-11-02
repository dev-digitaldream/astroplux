<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

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

        // Attach to page init to safely intercept routing
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
        $normalize = static function ($s) {
            $s = ($s ?? '');
            $s = '/' . ltrim($s, '/');
            return rtrim($s, '/');
        };

        $want = $normalize($cfgRoute);
        $uri  = $this->grav['uri'];
        $path = $normalize($uri->path());   // with base
        $route= $normalize($uri->route());  // without base

        if ($path === $want || $route === $want) {
            $this->exportJson();
        }
    }

    private function exportJson(): void
    {
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

        // Collect blog posts: all pages under blog_root (adjust as needed)
        $posts = [];
        $blog = $pages->find($blogRoot);
        if ($blog) {
            foreach ($blog->children() as $child) {
                $posts[] = $this->serializePage($child, $includeHtml);
            }
        }

        // Collect top-level pages (excluding /blog)
        $static = [];
        foreach ($pages->root()->children() as $p) {
            if ($p->route() === $blogRoot) continue;
            $static[] = $this->serializePage($p, $includeHtml);
        }

        $payload = [
            'config' => [
                'site_title' => $site['title'] ?? '',
                'site_description' => $site['description'] ?? '',
                'email' => $site['author']['email'] ?? '',
            ],
            'posts' => $posts,
            'pages' => $static,
        ];

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function serializePage($page, bool $withHtml = true): array
    {
        $header = $page->header();
        return [
            'title' => $page->title(),
            'slug' => $page->slug(),
            'route' => $page->route(),
            'date' => $page->date() ? date('c', $page->date()) : null,
            'tags' => $header->taxonomy['tag'] ?? [],
            'header' => $header,
            'html' => $withHtml ? $page->content() : '',
        ];
    }
}
