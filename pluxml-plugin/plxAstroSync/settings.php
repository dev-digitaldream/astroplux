<?php
// Public JSON endpoint for site settings managed in plugin config
if (!defined('PLX_PLUGINS')) die('Direct access not allowed');

header('Content-Type: application/json; charset=utf-8');

try {
  // Load plugin instance via PluXML runtime
  // When included under PluXML, $plxPlugins is available; otherwise minimal fallback
  if (!isset($plxPlugins)) {
    echo json_encode([ 'error' => 'runtime' ]);
    exit;
  }
  $plxPlugin = $plxPlugins->getInstance('plxAstroSync');
  if (!$plxPlugin) {
    echo json_encode([ 'error' => 'plugin' ]);
    exit;
  }

  $title = $plxPlugin->getParam('site_title') ?? '';
  $description = $plxPlugin->getParam('site_description') ?? '';
  $email = $plxPlugin->getParam('site_email') ?? '';
  $socials_b64 = $plxPlugin->getParam('site_socials_b64') ?? '';
  $theme_primary = $plxPlugin->getParam('theme_primary') ?? '';

  $socials = [];
  if ($socials_b64) {
    $json = base64_decode($socials_b64, true);
    if ($json !== false) {
      $tmp = json_decode($json, true);
      if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
        $socials = $tmp;
      }
    }
  }

  echo json_encode([
    'site_title' => $title,
    'site_description' => $description,
    'email' => $email,
    'socials' => $socials,
    'theme' => [ 'PRIMARY' => $theme_primary ],
  ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([ 'error' => $e->getMessage() ], JSON_UNESCAPED_UNICODE);
}
?>

