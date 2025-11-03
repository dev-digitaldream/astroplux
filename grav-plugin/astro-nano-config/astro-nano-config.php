<?php
namespace Grav\Plugin;

use Grav\Common\Filesystem\Folder;
use Grav\Common\Plugin;

class AstroNanoConfigPlugin extends Plugin
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
        ];
    }

    public function onPluginsInitialized(): void
    {
        $this->exportConfig();
    }

    private function exportConfig(): void
    {
        $config = (array) $this->config->get('plugins.astro-nano-config', []);

        $payload = [
            'site_title' => $config['site_title'] ?? '',
            'site_description' => $config['site_description'] ?? '',
            'email' => $config['email'] ?? '',
            'defaults' => [
                'home_title' => $config['defaults']['home_title'] ?? 'Home',
                'home_description' => $config['defaults']['home_description'] ?? '',
                'blog_title' => $config['defaults']['blog_title'] ?? 'Blog',
                'blog_description' => $config['defaults']['blog_description'] ?? '',
                'work_title' => $config['defaults']['work_title'] ?? 'Work',
                'work_description' => $config['defaults']['work_description'] ?? '',
                'projects_title' => $config['defaults']['projects_title'] ?? 'Projects',
                'projects_description' => $config['defaults']['projects_description'] ?? '',
            ],
            'socials' => $this->normaliseSocials($config['socials'] ?? []),
            'updated_at' => date('c'),
        ];

        $locator = $this->grav['locator'];
        $dir = $locator->findResource('user://data/astro-nano', true, true);
        Folder::create($dir);

        $file = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'config.json';
        file_put_contents($file, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function normaliseSocials($socials): array
    {
        if (!is_array($socials)) {
            return [];
        }

        $normalised = [];
        foreach ($socials as $key => $value) {
            if (is_array($value)) {
                $name = $value['name'] ?? ($value['NAME'] ?? $key);
                $href = $value['href'] ?? ($value['HREF'] ?? '');
            } else {
                $name = is_string($key) ? $key : '';
                $href = is_string($value) ? $value : '';
            }

            if ($name && $href) {
                $normalised[] = [
                    'NAME' => $name,
                    'HREF' => $href,
                ];
            }
        }

        return $normalised;
    }
}
