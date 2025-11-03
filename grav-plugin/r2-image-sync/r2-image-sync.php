<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Filesystem\Folder;
use RocketTheme\Toolbox\Event\Event;

class R2ImageSyncPlugin extends Plugin
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onPageContentProcessed' => ['onPageContentProcessed', 0],
        ];
    }

    public function onPluginsInitialized(): void
    {
        // Plugin initialized
    }

    public function onPageContentProcessed(Event $event): void
    {
        $page = $event['page'];
        $content = $page->getRawContent();

        if (!$content) {
            return;
        }

        // Trouver toutes les images locales dans le contenu
        $pattern = '/!\[([^\]]*)\]\((?!https?:\/\/)([^)]+\.(jpg|jpeg|png|gif|webp))\)/i';
        
        if (!preg_match_all($pattern, $content, $matches)) {
            return;
        }

        $updated = false;
        foreach ($matches[0] as $index => $fullMatch) {
            $altText = $matches[1][$index];
            $imagePath = $matches[2][$index];
            $imageExt = strtolower($matches[3][$index]);

            // Construire le chemin absolu de l'image
            $pageDir = dirname($page->getSourceFile());
            $absolutePath = $pageDir . '/' . $imagePath;

            if (!file_exists($absolutePath)) {
                continue;
            }

            // Uploader vers R2
            $cdnUrl = $this->uploadToR2($absolutePath, $imagePath);
            if ($cdnUrl) {
                // Remplacer l'URL locale par l'URL CDN
                $newMatch = "![{$altText}]({$cdnUrl})";
                $content = str_replace($fullMatch, $newMatch, $content);
                $updated = true;
            }
        }

        if ($updated) {
            $page->setRawContent($content);
        }
    }

    private function uploadToR2(string $filePath, string $fileName): ?string
    {
        $config = $this->config->get('plugins.r2-image-sync', []);
        
        $accountId = $config['account_id'] ?? getenv('CLOUDFLARE_ACCOUNT_ID');
        $accessKeyId = $config['access_key_id'] ?? getenv('CLOUDFLARE_R2_ACCESS_KEY_ID');
        $secretAccessKey = $config['secret_access_key'] ?? getenv('CLOUDFLARE_R2_SECRET_ACCESS_KEY');
        $bucketName = $config['bucket_name'] ?? 'astro-nano-images';
        $cdnDomain = $config['cdn_domain'] ?? 'images.alaoui.be';

        if (!$accountId || !$accessKeyId || !$secretAccessKey) {
            return null;
        }

        // Convertir en WebP si nécessaire
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $webpPath = $filePath;
        $uploadName = pathinfo($fileName, PATHINFO_FILENAME) . '.webp';

        if ($ext !== 'webp') {
            $webpPath = tempnam(sys_get_temp_dir(), 'webp_');
            $this->convertToWebp($filePath, $webpPath);
        }

        // Upload vers R2
        $endpoint = "https://{$accountId}.r2.cloudflarestorage.com";
        $uploadUrl = "{$endpoint}/{$bucketName}/{$uploadName}";

        $fileContent = file_get_contents($webpPath);
        $ch = curl_init($uploadUrl);
        curl_setopt_array($ch, [
            CURLOPT_PUT => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_BINARYTRANSFER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_AWS4,
            CURLOPT_USERPWD => "{$accessKeyId}:{$secretAccessKey}",
            CURLOPT_POSTFIELDS => $fileContent,
            CURLOPT_HTTPHEADER => [
                'Content-Type: image/webp',
                'Content-Length: ' . strlen($fileContent),
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Nettoyer le fichier temporaire
        if ($ext !== 'webp' && file_exists($webpPath)) {
            unlink($webpPath);
        }

        if ($httpCode === 200) {
            return "https://{$cdnDomain}/{$bucketName}/{$uploadName}";
        }

        return null;
    }

    private function convertToWebp(string $sourcePath, string $destPath): bool
    {
        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

        // Utiliser ImageMagick ou GD si disponible
        if (extension_loaded('imagick')) {
            try {
                $image = new \Imagick($sourcePath);
                $image->setImageFormat('webp');
                $image->setImageCompressionQuality(80);
                $image->writeImage($destPath);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }

        // Fallback : copier le fichier original
        return copy($sourcePath, $destPath);
    }
}
