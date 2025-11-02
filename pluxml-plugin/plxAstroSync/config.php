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
  // Site settings
  $plxPlugin->setParam('site_title', $_POST['site_title'] ?? '', 'cdata');
  $plxPlugin->setParam('site_description', $_POST['site_description'] ?? '', 'cdata');
  $plxPlugin->setParam('site_email', $_POST['site_email'] ?? '', 'cdata');
  $plxPlugin->setParam('site_socials_b64', base64_encode($_POST['site_socials'] ?? ''), 'cdata');
  $plxPlugin->setParam('theme_primary', $_POST['theme_primary'] ?? '', 'cdata');
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
$site_title = htmlspecialchars(trim($plxPlugin->getParam('site_title') ?? ''), ENT_QUOTES, 'UTF-8');
$site_description = htmlspecialchars(trim($plxPlugin->getParam('site_description') ?? ''), ENT_QUOTES, 'UTF-8');
$site_email = htmlspecialchars(trim($plxPlugin->getParam('site_email') ?? ''), ENT_QUOTES, 'UTF-8');
$site_socials_b64 = trim($plxPlugin->getParam('site_socials_b64') ?? '');
$site_socials_dec = ($site_socials_b64 !== '' ? base64_decode($site_socials_b64, true) : '');
$site_socials = htmlspecialchars($site_socials_dec !== false ? (string)$site_socials_dec : '', ENT_QUOTES, 'UTF-8');
$theme_primary = htmlspecialchars(trim($plxPlugin->getParam('theme_primary') ?? ''), ENT_QUOTES, 'UTF-8');
?>

<h2>Astro Sync</h2>

<form id="form_config_plugin" action="parametres_plugin.php?p=plxAstroSync" method="post">
  <fieldset>
    <p>
      <label for="webhook_url">Relay URL (trigger-cloudflare.php)</label><br />
      <input type="text" id="webhook_url" name="webhook_url" style="width:100%" value="<?php echo $relay; ?>" />
    </p>
    <hr />
    <h3>Site settings (exportés pour Astro)</h3>
    <p>
      <label for="site_title">Site title</label><br />
      <input type="text" id="site_title" name="site_title" style="width:100%" value="<?php echo $site_title; ?>" />
    </p>
    <p>
      <label for="site_description">Site description</label><br />
      <textarea id="site_description" name="site_description" style="width:100%" rows="2"><?php echo $site_description; ?></textarea>
    </p>
    <p>
      <label for="site_email">Contact email</label><br />
      <input type="text" id="site_email" name="site_email" style="width:100%" value="<?php echo $site_email; ?>" />
    </p>
    <p>
      <label for="site_socials">Socials (JSON array)</label><br />
      <textarea id="site_socials" name="site_socials" style="width:100%" rows="3"><?php echo $site_socials; ?></textarea>
      <span class="help">Ex: [{"NAME":"github","HREF":"https://github.com/dev-digitaldream"}]</span>
    </p>
    <p>
      <label for="theme_primary">Theme primary color (hex)</label><br />
      <input type="text" id="theme_primary" name="theme_primary" style="width:100%" value="<?php echo $theme_primary; ?>" />
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
    const socials = document.getElementById('site_socials');
    if (relay && relay.value) relay.value = btoa(unescape(encodeURIComponent(relay.value)));
    if (secret && secret.value) secret.value = btoa(unescape(encodeURIComponent(secret.value)));
    if (cf && cf.value) cf.value = (function(v){ try { return btoa(unescape(encodeURIComponent(v))); } catch(e) { return v; } })(cf.value);
    if (socials && socials.value) socials.value = (function(v){ try { return v; } catch(e) { return v; } })(socials.value);
  } catch (e) {}
});
</script>
