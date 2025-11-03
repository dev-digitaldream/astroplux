<?php
// Page admin pour déclencher la synchro Astro
// Accessible via : /admin/astro-nano-config/sync

namespace Grav\Plugin\AstroNanoConfig;

class AdminSync
{
    public static function handle()
    {
        // Récupérer l'URL du trigger-deploy.php depuis la config
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

        return [
            'status' => $status,
            'error' => $error ?: null,
            'response' => $result,
            'success' => $status === 200 && ($result['response']['success'] ?? false),
        ];
    }
}
