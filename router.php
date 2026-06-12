<?php
// Dev router for `php -S` — mirrors .htaccess rules
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$S = json_decode(@file_get_contents(__DIR__.'/data/settings.json'), true) ?: [];
// 301 redirects from Technical SEO settings
foreach (($S['technical']['redirects'] ?? []) as $r) {
  if (!empty($r['from']) && rtrim($uri,'/') === rtrim($r['from'],'/')) { header('Location: '.$r['to'], true, 301); return true; }
}
if ($uri === '/sitemap.xml') { require __DIR__.'/sitemap.php'; return true; }
if ($uri === '/robots.txt')  { require __DIR__.'/robots.php';  return true; }
if (strpos($uri, '/data/') === 0) { http_response_code(403); return true; }
if ($uri === '/blog' || $uri === '/blog/') { require __DIR__.'/blog.php'; return true; }
if (preg_match('#^/blog/([a-z0-9-]+)/?$#', $uri, $m)) { $_GET['slug'] = $m[1]; require __DIR__.'/blog.php'; return true; }
if ($uri === '/') { require __DIR__.'/index.php'; return true; }
// existing file? serve it; else custom 404
if (is_file(__DIR__ . $uri)) return false;
if (!empty($S['technical']['custom_404'])) { require __DIR__.'/404.php'; return true; }
return false;
