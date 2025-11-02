<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;

class TestPlugin extends Plugin
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

        // Simple test route
        $uri = $this->grav['uri'];
        if ($uri->path() === '/test-plugin') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'Plugin works!', 'timestamp' => date('c')]);
            exit;
        }
    }
}
