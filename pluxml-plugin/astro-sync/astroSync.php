<?php
/**
 * Plugin Name: Astro Sync
 * Description: Triggers a static site rebuild (e.g., Cloudflare Pages) after content is saved. Provides a config page and manual trigger.
 * Author: You
 * Version: 0.1.0
 */

if (!defined('PLX_PLUGINS')) die('Direct access not allowed');

class astroSync extends plxPlugin {

    public function __construct($default_lang) {
        parent::__construct($default_lang);

        // Allow access to the plugin config page
        $this->setConfigProfil(PROFIL_ADMIN);

        // Configuration keys with defaults
        $this->addConfigParameter('webhook_url', '', 'string');
        $this->addConfigParameter('secret', '', 'string');
        $this->addConfigParameter('auto_trigger', '0', 'string');

        // Hooks (placeholders) — confirm exact names on PluXML 5.8.21 and uncomment.
        // $this->addHook('AdminArticlePostSave', 'hookPostSaveArticle');
        // $this->addHook('AdminStaticPostSave', 'hookPostSaveStatic');
    }

    /**
     * Plugin admin configuration UI
     */
    public function onInstall() { return true; }
    public function onUpdate() { return true; }
    public function onDeactivate() { return true; }

    /**
     * Called by hooks after saving an article
     */
    public function hookPostSaveArticle() {
        $this->maybeTrigger('article');
    }

    /**
     * Called by hooks after saving a static page
     */
    public function hookPostSaveStatic() {
        $this->maybeTrigger('static');
    }

    /**
     * Core trigger logic
     */
    private function maybeTrigger($what) {
        $auto = $this->getParam('auto_trigger');
        if ($auto !== '1') return; // only auto when enabled
        $this->triggerWebhook($what);
    }

    /**
     * Manual action from config UI
     */
    public function adminTriggerNow() {
        $this->triggerWebhook('manual');
    }

    private function triggerWebhook($source = 'manual') {
        $webhook = trim($this->getParam('webhook_url'));
        $secret = trim($this->getParam('secret'));
        if ($webhook === '') return;

        $payload = json_encode([
            'source' => 'pluxml',
            'event'  => 'content_update',
            'type'   => $source,
            'ts'     => time(),
            'token'  => $secret,
        ]);

        $ch = curl_init($webhook);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 7);
        $resp = curl_exec($ch);
        $err  = curl_errno($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Optional: log result (requires writable path). Commented by default.
        // @file_put_contents(PLX_ROOT.'data/configuration/astro-sync.log', date('c')." code=$code err=$err resp=$resp\n", FILE_APPEND);
    }
}

?>
