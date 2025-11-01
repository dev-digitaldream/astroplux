<?php
if (!defined('PLX_PLUGINS')) die('Direct access not allowed');

class plxAstroSync extends plxPlugin {

    public function __construct($default_lang) {
        parent::__construct($default_lang);

        // Autoriser l'accès à la page de configuration du plugin
        $this->setConfigProfil(PROFIL_ADMIN);

        // Paramètres gérés via config.php avec setParam/saveParams
        // (pas de addConfigParameter dans PluXML 5.8.x)

        // Hooks post-save (PluXML 5.8.x)
        $this->addHook('AdminArticlePostSave', 'hookPostSaveArticle');
        $this->addHook('AdminStaticPostSave', 'hookPostSaveStatic');
    }

    public function onInstall() {
        // Initialiser des valeurs par défaut si non présentes
        if ($this->getParam('webhook_url') === null) $this->setParam('webhook_url', '', 'cdata'); // Relay URL (trigger-cloudflare.php)
        if ($this->getParam('secret') === null) $this->setParam('secret', '', 'cdata');
        if ($this->getParam('auto_trigger') === null) $this->setParam('auto_trigger', '0', 'cdata');
        if ($this->getParam('cf_webhook_b64') === null) $this->setParam('cf_webhook_b64', '', 'cdata'); // Cloudflare Build Hook (base64)
        $this->saveParams();
        return true;
    }
    public function onUpdate() { return true; }
    public function onDeactivate() { return true; }

    public function hookPostSaveArticle() { $this->maybeTrigger('article'); }
    public function hookPostSaveStatic() { $this->maybeTrigger('static'); }

    private function maybeTrigger($what) {
        $auto = $this->getParam('auto_trigger');
        if ($auto !== '1') return;
        $this->triggerWebhook($what);
    }

    public function adminTriggerNow() { $this->triggerWebhook('manual'); }

    private function triggerWebhook($source = 'manual') {
        // Decode relay and secret if stored base64 (handled by config.js)
        $relay = trim($this->getParam('webhook_url'));
        $secret = trim($this->getParam('secret'));
        $relay = $relay !== '' ? (base64_decode($relay, true) ?: $relay) : $relay;
        $secret = $secret !== '' ? (base64_decode($secret, true) ?: $secret) : $secret;
        $cfWebhook = '';
        $b64 = $this->getParam('cf_webhook_b64');
        if (is_string($b64) && $b64 !== '') {
            $dec = base64_decode($b64, true);
            if ($dec !== false) { $cfWebhook = $dec; }
        }
        if ($relay === '') return;

        $payload = json_encode([
            'source' => 'pluxml',
            'event'  => 'content_update',
            'type'   => $source,
            'ts'     => time(),
            'token'  => $secret,
            // pass Cloudflare Build Hook in body to avoid URL encoding issues in admin
            'webhook'=> $cfWebhook,
        ]);

        $ch = curl_init($relay);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 7);
        $resp = curl_exec($ch);
        $err  = curl_errno($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Option: journaliser (dossier writable requis)
        // @file_put_contents(PLX_ROOT.'data/configuration/plxAstroSync.log', date('c')." code=$code err=$err resp=$resp\n", FILE_APPEND);
    }
}

?>
