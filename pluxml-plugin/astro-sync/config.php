<?php if (!defined('PLX_ROOT')) exit; ?>
<?php
/** @var astroSync $plxPlugin */
$webhook = plxUtils::strCheck(trim($plxPlugin->getParam('webhook_url')));
$secret  = plxUtils::strCheck(trim($plxPlugin->getParam('secret')));
$auto    = $plxPlugin->getParam('auto_trigger') === '1';

if (!empty($_POST) && plxToken::validateFormToken($_POST)) {
  $plxPlugin->setParam('webhook_url', $_POST['webhook_url'], 'string');
  $plxPlugin->setParam('secret', $_POST['secret'], 'string');
  $plxPlugin->setParam('auto_trigger', isset($_POST['auto_trigger']) ? '1' : '0', 'string');
  $plxPlugin->saveParams();
  echo '<p class="ok">Configuration saved.</p>';
}

if (isset($_POST['trigger_now']) && plxToken::validateFormToken($_POST)) {
  $plxPlugin->adminTriggerNow();
  echo '<p class="ok">Build trigger sent.</p>';
}
?>

<h2>Astro Sync</h2>

<form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="post">
  <?php echo plxToken::getTokenPostMethod(); ?>
  <fieldset>
    <p>
      <label for="webhook_url">Webhook URL</label><br />
      <input type="text" id="webhook_url" name="webhook_url" style="width:100%" value="<?php echo $webhook; ?>" />
    </p>
    <p>
      <label for="secret">Secret (optional)</label><br />
      <input type="text" id="secret" name="secret" style="width:100%" value="<?php echo $secret; ?>" />
    </p>
    <p>
      <label>
        <input type="checkbox" name="auto_trigger" <?php echo $auto ? 'checked' : ''; ?> />
        Auto trigger on save (articles/pages)
      </label>
    </p>
    <p>
      <input type="submit" name="save" value="Save" />
      <input type="submit" name="trigger_now" value="Trigger now" />
    </p>
  </fieldset>
</form>

<p>
  Notes:<br />
  - To auto-trigger on save, ensure the plugin hooks match your PluXML 5.8.21 post-save events. Uncomment/add in <code>plugin.php</code> if needed.<br />
  - For Cloudflare Pages, use the Build Hook URL from your project settings.<br />
</p>

