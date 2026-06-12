<?php
$S = json_decode(file_get_contents(__DIR__ . '/data/settings.json'), true);
if (!$S['seo']['sitemap_enabled']) { http_response_code(404); exit; }
header('Content-Type: application/xml; charset=utf-8');
$url = rtrim($S['seo']['canonical_url'], '/') . '/';
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?= htmlspecialchars($url) ?></loc>
    <lastmod><?= date('Y-m-d', filemtime(__DIR__ . '/index.php')) ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>1.0</priority>
  </url>
</urlset>
