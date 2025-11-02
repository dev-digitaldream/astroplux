<?php
// Export Grav content recursively (no Grav bootstrap required)
// Works with typical Grav structure: user/pages/03.blog/01.my-post/default.md
// and pages like user/pages/01.home/default.md
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

$ROOT   = __DIR__;
$PAGES  = $ROOT . '/user/pages';

function strip_num_prefix($name) {
  // Remove leading NN. from folder names like 01.home => home
  return preg_replace('/^\d+\./', '', $name);
}

function find_first_md($dir) {
  // Prefer default.md/item.md, else any .md
  $candidates = ['default.md', 'item.md', 'modular.md', 'blog.md'];
  foreach ($candidates as $f) {
    if (is_file($dir . '/' . $f)) return $dir . '/' . $f;
  }
  foreach (glob($dir . '/*.md') as $f) return $f ?: null;
  return null;
}

function parse_frontmatter($raw) {
  // Minimal parser without yaml extension. Expects ---\nYAML\n---\nBody
  $res = ['header' => [], 'body' => trim($raw)];
  if (strpos($raw, '---') !== 0) return $res;
  $parts = preg_split('/\n-\-\-\n/', substr($raw, 3), 2);
  if (!$parts || count($parts) < 2) return $res;
  $yaml = trim($parts[0]);
  $body = trim($parts[1]);
  $header = [];
  foreach (preg_split('/\r?\n/', $yaml) as $line) {
    if (!strlen(trim($line))) continue;
    if (!strpos($line, ':')) continue;
    [$k, $v] = array_map('trim', explode(':', $line, 2));
    // Remove quotes
    $v = trim($v, " \"'\t");
    // Basic arrays like tags: [a, b]
    if (preg_match('/^\[(.*)\]$/', $v, $m)) {
      $items = array_map('trim', explode(',', $m[1]));
      $items = array_values(array_filter(array_map(function($x){return trim($x, " \"'\t");}, $items), 'strlen'));
      $header[$k] = $items;
    } else {
      $header[$k] = $v;
    }
  }
  $res['header'] = $header;
  $res['body'] = $body;
  return $res;
}

function build_route_from_path($absPath, $pagesRoot) {
  // Convert /user/pages/03.blog/01.my-post to /blog/my-post
  $rel = ltrim(str_replace($pagesRoot, '', $absPath), '/');
  $parts = array_filter(explode('/', $rel));
  $clean = array_map('strip_num_prefix', $parts);
  return '/' . implode('/', $clean);
}

function collect_content($pagesRoot) {
  $posts = [];
  $pages = [];
  $blogRootAbs = null;

  // Detect blog root folder by name containing 'blog'
  foreach (glob($pagesRoot . '/*', GLOB_ONLYDIR) as $dir) {
    $base = basename($dir);
    if (stripos(strip_num_prefix($base), 'blog') !== false) {
      $blogRootAbs = realpath($dir);
      break;
    }
  }

  // Recursive traversal
  $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pagesRoot, FilesystemIterator::SKIP_DOTS));
  foreach ($rii as $file) {
    if (!$file->isFile()) continue;
    if (strtolower($file->getExtension()) !== 'md') continue;

    $filePath = $file->getPathname();
    $dirPath  = dirname($filePath);

    // Only take one .md per folder (the primary one)
    $primary = find_first_md($dirPath);
    if (!$primary || realpath($primary) !== realpath($filePath)) continue;

    $raw = file_get_contents($filePath);
    $fm  = parse_frontmatter($raw);

    $folderRoute = build_route_from_path($dirPath, $pagesRoot);
    $isBlog = $blogRootAbs && str_starts_with(realpath($dirPath) . '/', $blogRootAbs . '/');

    $title = $fm['header']['title'] ?? basename($dirPath);
    $slug  = basename($dirPath);
    $slug  = strip_num_prefix($slug);

    $item = [
      'title'  => $title,
      'slug'   => $slug,
      'route'  => $folderRoute,
      'date'   => $fm['header']['date'] ?? null,
      'tags'   => $fm['header']['tags'] ?? [],
      'header' => $fm['header'],
      'html'   => $fm['body'],
    ];

    if ($isBlog) $posts[] = $item; else $pages[] = $item;
  }

  return [$posts, $pages, $blogRootAbs];
}

$site = ['title' => 'Grav Site', 'description' => '', 'author' => ['email' => ''], 'socials' => []];
$siteYaml = $ROOT . '/user/config/site.yaml';
if (is_file($siteYaml)) {
  $raw = file_get_contents($siteYaml);
  $fm = parse_frontmatter("---\n$raw\n---\n");
  $h = $fm['header'];
  if (!empty($h)) {
    $site['title'] = $h['title'] ?? $site['title'];
    $site['description'] = $h['description'] ?? $site['description'];
    if (isset($h['author']) && is_array($h['author']) && isset($h['author']['email'])) {
      $site['author']['email'] = $h['author']['email'];
    }
    if (isset($h['socials'])) {
      // Expect either array of {name, href} or mapping name->href
      if (is_array($h['socials'])) {
        $out = [];
        // Normalize
        foreach ($h['socials'] as $k => $v) {
          if (is_array($v)) {
            $name = $v['name'] ?? ($v['NAME'] ?? $k);
            $href = $v['href'] ?? ($v['HREF'] ?? '');
            if ($name && $href) $out[] = ['NAME' => $name, 'HREF' => $href];
          } else {
            // key: name, value: href
            $name = is_string($k) ? $k : '';
            $href = is_string($v) ? $v : '';
            if ($name && $href) $out[] = ['NAME' => $name, 'HREF' => $href];
          }
        }
        $site['socials'] = $out;
      }
    }
  }
}

$debug = [
  'pages_root' => $PAGES,
  'exists' => is_dir($PAGES),
];

$posts = $pages = [];
$blogAbs = null;
if (is_dir($PAGES)) {
  [$posts, $pages, $blogAbs] = collect_content($PAGES);
  $debug['blog_root_abs'] = $blogAbs;
  $debug['posts_count'] = count($posts);
  $debug['pages_count'] = count($pages);
}

$payload = [
  'config' => [
    'site_title' => $site['title'] ?? '',
    'site_description' => $site['description'] ?? '',
    'email' => $site['author']['email'] ?? '',
    'socials' => $site['socials'] ?? []
  ],
  'posts' => $posts,
  'pages' => $pages,
  'status' => 'success',
  'timestamp' => date('c'),
  'debug' => $debug,
];

echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
