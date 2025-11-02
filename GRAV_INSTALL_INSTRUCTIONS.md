# Installation du plugin Astro Export sur votre Grav CMS

URL de votre Grav : https://alaoui.be/grav/

## Étapes d'installation

### 1. Téléchargez le plugin

Le plugin est déjà prêt dans votre projet local :
```bash
# Dans votre projet local
cd /Volumes/ExtremeSSD/projetcs/nano-front
```

### 2. Installez le plugin sur votre serveur

**Option A : Via FTP/Manager de fichiers cPanel**
1. Connectez-vous à votre cPanel
2. Ouvrez le "File Manager"
3. Naviguez vers : `public_html/grav/user/plugins/`
4. Créez un nouveau dossier nommé `astro-export`
5. Uploadez tous les fichiers depuis `grav-plugin/astro-export/` :
   - astro-export.php
   - astro-export.yaml
   - blueprints.yaml

**Option B : Via SSH**
```bash
# Connectez-vous à votre serveur
cd /chemin/vers/votre/site/grav/user/plugins/
mkdir astro-export
# Uploadez les fichiers du plugin
```

### 3. Activez le plugin

**Via l'admin Grav** :
1. Connectez-vous à https://alaoui.be/grav/admin
2. Allez dans "Plugins" 
3. Trouvez "Astro Export" et cliquez sur "Enable"

**Ou manuellement** :
1. Créez le fichier `user/config/plugins/astro-export.yaml` :
```yaml
enabled: true
route: /astro-export.json
blog_root: /blog
include_html: true
```

### 4. Vérifiez l'installation

Testez l'endpoint :
```bash
curl https://alaoui.be/grav/astro-export.json
```

Devrait retourner du JSON avec votre contenu.

### 5. Configurez Cloudflare Pages

Dans votre dashboard Cloudflare Pages :
1. Allez dans "Settings" → "Environment variables"
2. Ajoutez :
   ```
   GRAV_EXPORT_URL=https://alaoui.be/grav/astro-export.json
   CMS_SOURCE=grav
   ```

### 6. Déclenchez un nouveau build

Le build devrait maintenant synchroniser votre contenu Grav vers Astro.

## Contenu du plugin

Les fichiers à copier dans `user/plugins/astro-export/` :

```php
<?php
// astro-export.php
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
        $path = $normalize($uri->path());
        $route= $normalize($uri->route());

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

        $posts = [];
        $blog = $pages->find($blogRoot);
        if ($blog) {
            foreach ($blog->children() as $child) {
                $posts[] = $this->serializePage($child, $includeHtml);
            }
        }

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
```

## Dépannage

### Erreur 404
- Vérifiez que le plugin est bien dans `user/plugins/astro-export/`
- Confirmez que le plugin est activé dans l'admin Grav
- Vérifiez les permissions des fichiers (755 pour les dossiers, 644 pour les fichiers)

### Erreur 500
- Consultez les logs Grav : `logs/grav.log`
- Vérifiez la version PHP (minimum 7.4)
- Testez la syntaxe PHP

### Pas de contenu
- Assurez-vous d'avoir des pages dans Grav
- Vérifiez la configuration de `blog_root` si vous utilisez un blog
- Le plugin exporte les pages sous `/blog` comme articles et les autres comme pages statiques

Une fois installé, votre site Cloudflare Pages synchronisera automatiquement le contenu !
