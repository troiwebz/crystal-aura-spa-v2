<?php
$S = json_decode(file_get_contents(__DIR__ . '/data/settings.json'), true);
header('Content-Type: text/plain; charset=utf-8');
if (!$S['seo']['robots_txt_enabled']) { echo "User-agent: *\nAllow: /\n"; exit; }
$url = rtrim($S['seo']['canonical_url'], '/');
echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /admin.php\n";
echo "Disallow: /data/\n\n";
echo "Sitemap: {$url}/sitemap.xml\n";
