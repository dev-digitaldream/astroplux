<?php
// Endpoint pour déclencher la synchro depuis l'admin Grav
// Accessible via : /admin/astro-nano-config/sync

namespace Grav\Plugin;

use Grav\Common\Grav;

class AstroNanoConfigSyncEndpoint
{
    public static function handle()
    {
        $grav = Grav::instance();
        $admin = $grav['admin'];

        // Vérifier que l'utilisateur est authentifié
        if (!$admin->user) {
            return json_encode(['error' => 'Unauthorized', 'status' => 401]);
        }

        // URL du trigger-deploy.php
        $triggerUrl = 'https://alaoui.be/grav/trigger-deploy.php';

        // Appeler le trigger
        $ch = curl_init($triggerUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        return json_encode([
            'status' => $status,
            'error' => $error ?: null,
            'response' => $result,
            'success' => $status === 200 && ($result['response']['success'] ?? false),
        ]);
    }
}
