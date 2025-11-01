<?php
// Lightweight endpoint to trigger Cloudflare Pages rebuilds.
// Place this file on your PluXML host (e.g., /pluxml/trigger-cloudflare.php)
// and set the WEBHOOK_URL + SECRET below, or pass them via env/GET.

// Configuration
$input = file_get_contents('php://input');
$json = null;
if ($input) {
  $tmp = json_decode($input, true);
  if (json_last_error() === JSON_ERROR_NONE) {
    $json = $tmp;
  }
}

$WEBHOOK_URL = getenv('CF_PAGES_WEBHOOK')
  ?: ($json['webhook'] ?? null)
  ?: ($_GET['webhook'] ?? '');
$SECRET = getenv('SYNC_SECRET')
  ?: ($json['token'] ?? null)
  ?: ($_GET['secret'] ?? '');

// Optional: restrict allowed origin(s)
$ALLOWED_ORIGINS = [
  // 'https://alaoui.be',
];

header('Content-Type: application/json; charset=utf-8');

function error_exit($code, $message) {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
  exit;
}

// Basic origin check (optional)
if (!empty($ALLOWED_ORIGINS)) {
  $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
  if ($origin && !in_array($origin, $ALLOWED_ORIGINS, true)) {
    error_exit(403, 'Origin not allowed');
  }
}

// Require a shared secret to avoid abuse
$provided = $json['token'] ?? ($_GET['token'] ?? ($_POST['token'] ?? ''));
if ($SECRET && $provided !== $SECRET) {
  error_exit(401, 'Invalid token');
}

if (!$WEBHOOK_URL) {
  error_exit(400, 'Missing CF_PAGES_WEBHOOK');
}

// Optional payload to include context like article ID/slug
$payload = [
  'source' => 'pluxml',
  'event' => 'content_update',
  'ts' => time(),
  'ref' => $_GET['ref'] ?? ($_POST['ref'] ?? ''),
];

// Fire webhook
$ch = curl_init($WEBHOOK_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$errno = curl_errno($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($errno) {
  log_line(['ok'=>false,'error'=>'curl','errno'=>$errno]);
  error_exit(502, 'cURL error: ' . $errno);
}

if ($status < 200 || $status >= 300) {
  log_line(['ok'=>false,'status'=>$status,'response'=>$response]);
  error_exit($status ?: 502, 'Webhook failed with status ' . $status);
}

http_response_code(200);
echo json_encode(['ok' => true, 'status' => $status, 'response' => $response], JSON_UNESCAPED_UNICODE);
log_line(['ok'=>true,'status'=>$status]);
exit;
?>

<?php
// Append JSON log line if LOG_FILE env or ?log=/path is provided
function log_line($data) {
  $file = getenv('LOG_FILE') ?: ($_GET['log'] ?? '');
  if (!$file) return;
  $row = [
    'ts' => date('c'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    'data' => $data,
  ];
  @file_put_contents($file, json_encode($row, JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
}
?>
