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
$DATA   = $ROOT . '/user/data';

if (!function_exists('str_starts_with')) {
  function str_starts_with($haystack, $needle) {
    return $needle === '' || strpos($haystack, $needle) === 0;
  }
}

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
      'raw'    => $raw,
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

function get_root_segment($route) {
  $segments = array_values(array_filter(explode('/', trim($route, '/'))));
  return $segments[0] ?? '';
}

function normalise_project($item) {
  $header = $item['header'] ?? [];
  return [
    'title' => $item['title'],
    'slug' => $item['slug'],
    'route' => $item['route'],
    'date' => $header['date'] ?? $item['date'],
    'description' => $header['description'] ?? ($header['summary'] ?? ''),
    'excerpt' => $header['excerpt'] ?? '',
    'tags' => $header['tags'] ?? $item['tags'] ?? [],
    'demo_url' => $header['demo_url'] ?? ($header['demo'] ?? ''),
    'repo_url' => $header['repo_url'] ?? ($header['repository'] ?? ''),
    'draft' => isset($header['published']) ? !$header['published'] : ($header['draft'] ?? false),
    'header' => $header,
    'html' => $item['html'],
  ];
}

function normalise_work($item) {
  $header = $item['header'] ?? [];
  $dateStart = $header['date_start'] ?? ($header['from'] ?? null);
  $dateEnd = $header['date_end'] ?? ($header['to'] ?? null);
  return [
    'title' => $item['title'],
    'slug' => $item['slug'],
    'route' => $item['route'],
    'company' => $header['company'] ?? $item['title'],
    'role' => $header['role'] ?? ($header['position'] ?? ''),
    'location' => $header['location'] ?? '',
    'date_start' => $dateStart,
    'date_end' => $dateEnd,
    'header' => $header,
    'html' => $item['html'],
  ];
}

function load_plugin_config($dataRoot)
{
  $file = rtrim($dataRoot, DIRECTORY_SEPARATOR) . '/astro-nano/config.json';
  if (is_file($file)) {
    $payload = json_decode(file_get_contents($file), true);
    if (is_array($payload)) {
      return $payload;
    }
  }
  return [];
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

$projects = [];
$work = [];
$staticPages = [];
$home = null;

foreach ($pages as $page) {
  $root = get_root_segment($page['route']);

  if ($root === '' || $root === 'home') {
    if ($home === null) {
      $home = $page;
    }
    continue;
  }

  if (str_starts_with($root, 'project')) {
    $projects[] = normalise_project($page);
    continue;
  }

  if (str_starts_with($root, 'work')) {
    $work[] = normalise_work($page);
    continue;
  }

  $staticPages[] = $page;
}

$pluginConfig = load_plugin_config($DATA);

$configPayload = [
  'site_title' => $pluginConfig['site_title'] ?? ($site['title'] ?? ''),
  'site_description' => $pluginConfig['site_description'] ?? ($site['description'] ?? ''),
  'email' => $pluginConfig['email'] ?? ($site['author']['email'] ?? ''),
  'home_title' => $pluginConfig['defaults']['home_title'] ?? 'Home',
  'home_description' => $pluginConfig['defaults']['home_description'] ?? '',
  'blog_title' => $pluginConfig['defaults']['blog_title'] ?? 'Blog',
  'blog_description' => $pluginConfig['defaults']['blog_description'] ?? '',
  'work_title' => $pluginConfig['defaults']['work_title'] ?? 'Work',
  'work_description' => $pluginConfig['defaults']['work_description'] ?? '',
  'projects_title' => $pluginConfig['defaults']['projects_title'] ?? 'Projects',
  'projects_description' => $pluginConfig['defaults']['projects_description'] ?? '',
  'socials' => $pluginConfig['socials'] ?? ($site['socials'] ?? []),
];

$debug['projects_count'] = count($projects);
$debug['work_count'] = count($work);
$debug['home'] = $home ? $home['route'] : null;

$payload = [
  'config' => $configPayload,
  'home' => $home,
  'posts' => $posts,
  'projects' => $projects,
  'work' => $work,
  'pages' => $staticPages,
  'status' => 'success',
  'timestamp' => date('c'),
  'debug' => $debug,
];

echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
