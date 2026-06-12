<?php
// Dev router for `php -S` — mirrors .htaccess rules
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($uri === '/sitemap.xml') { require __DIR__.'/sitemap.php'; return true; }
if ($uri === '/robots.txt')  { require __DIR__.'/robots.php';  return true; }
if (strpos($uri, '/data/') === 0) { http_response_code(403); return true; }
if ($uri === '/') { require __DIR__.'/index.php'; return true; }
return false; // serve files as-is
