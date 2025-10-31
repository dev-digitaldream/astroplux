<?php if (!defined('PLX_ROOT')) exit; ?>
<?php
// Page de configuration du plugin plxAstroSync (conforme au squelette PluXML)
/** @var plxAstroSync $plxPlugin */

// Valide le token systématiquement comme les exemples officiels
plxToken::validateFormToken($_POST);

if (!empty($_POST)) {
  // Sauvegarde des paramètres puis redirection vers la page du plugin (pattern officiel)
  $plxPlugin->setParam('webhook_url', $_POST['webhook_url'] ?? '', 'cdata');
  $plxPlugin->setParam('secret', $_POST['secret'] ?? '', 'cdata');
  $raw = $_POST['cf_webhook'] ?? '';
  $plxPlugin->setParam('cf_webhook_b64', base64_encode($raw), 'cdata');
  $plxPlugin->setParam('auto_trigger', isset($_POST['auto_trigger']) ? '1' : '0', 'cdata');
  $plxPlugin->saveParams();

  if (isset($_POST['trigger_now'])) {
    $plxPlugin->adminTriggerNow();
  }

  header('Location: parametres_plugin.php?p=plxAstroSync');
  exit;
}

$relayRaw = trim($plxPlugin->getParam('webhook_url') ?? '');
$secretRaw= trim($plxPlugin->getParam('secret') ?? '');
$relay   = htmlspecialchars(($relayRaw !== '' ? (base64_decode($relayRaw, true) ?: $relayRaw) : ''), ENT_QUOTES, 'UTF-8');
$secret  = htmlspecialchars(($secretRaw !== '' ? (base64_decode($secretRaw, true) ?: $secretRaw) : ''), ENT_QUOTES, 'UTF-8');
$cf_b64  = trim($plxPlugin->getParam('cf_webhook_b64') ?? '');
$cf_dec  = ($cf_b64 !== '' ? base64_decode($cf_b64, true) : '');
$cfhook  = htmlspecialchars($cf_dec !== false ? (string)$cf_dec : '', ENT_QUOTES, 'UTF-8');
$auto    = ($plxPlugin->getParam('auto_trigger') === '1');
?>

<h2>Astro Sync</h2>

<form id="form_config_plugin" action="parametres_plugin.php?p=plxAstroSync" method="post">
  <fieldset>
    <p>
      <label for="webhook_url">Relay URL (trigger-cloudflare.php)</label><br />
      <input type="text" id="webhook_url" name="webhook_url" style="width:100%" value="<?php echo $relay; ?>" />
    </p>
    <p>
      <label for="secret">Secret (optionnel)</label><br />
      <input type="text" id="secret" name="secret" style="width:100%" value="<?php echo $secret; ?>" />
    </p>
    <p>
      <label for="cf_webhook">Cloudflare Build Hook URL</label><br />
      <textarea id="cf_webhook" name="cf_webhook" style="width:100%" rows="3"><?php echo $cfhook; ?></textarea>
    </p>
    <p>
      <label>
        <input type="checkbox" name="auto_trigger" <?php echo $auto ? 'checked' : ''; ?> />
        Déclenchement automatique après sauvegarde (articles/pages)
      </label>
    </p>
    <p class="in-action-bar">
      <?php echo plxToken::getTokenPostMethod() ?>
      <input type="submit" name="submit" value="Enregistrer" />
      <input type="submit" name="trigger_now" value="Déclencher maintenant" />
    </p>
  </fieldset>
  <p class="help">Les valeurs sont conservées en CDATA et encodées en base64 à l'enregistrement pour éviter toute altération.</p>
</form>

<script>
// Encode en base64 avant soumission pour éviter la modification par l'admin/hébergeur
document.getElementById('form_config_plugin')?.addEventListener('submit', function() {
  try {
    const relay = document.getElementById('webhook_url');
    const secret = document.getElementById('secret');
    const cf = document.getElementById('cf_webhook');
    if (relay && relay.value) relay.value = btoa(unescape(encodeURIComponent(relay.value)));
    if (secret && secret.value) secret.value = btoa(unescape(encodeURIComponent(secret.value)));
    if (cf && cf.value) cf.value = (function(v){ try { return btoa(unescape(encodeURIComponent(v))); } catch(e) { return v; } })(cf.value);
  } catch (e) {}
});
</script>
