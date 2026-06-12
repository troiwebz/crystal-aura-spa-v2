<?php
$S = json_decode(file_get_contents(__DIR__ . '/data/settings.json'), true);
if (!$S['seo']['sitemap_enabled']) { http_response_code(404); exit; }
header('Content-Type: application/xml; charset=utf-8');
$base = rtrim($S['seo']['canonical_url'], '/');
$POSTS = file_exists(__DIR__ . '/data/posts.json') ? (json_decode(file_get_contents(__DIR__ . '/data/posts.json'), true) ?: []) : [];
$published = array_filter($POSTS, fn($p) => !empty($p['published']));
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?= htmlspecialchars($base . '/') ?></loc>
    <lastmod><?= date('Y-m-d', filemtime(__DIR__ . '/index.php')) ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>1.0</priority>
  </url>
<?php if (!empty($published)): ?>
  <url>
    <loc><?= htmlspecialchars($base . '/blog') ?></loc>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
<?php foreach ($published as $p): ?>
  <url>
    <loc><?= htmlspecialchars($base . '/blog/' . $p['slug']) ?></loc>
    <lastmod><?= htmlspecialchars($p['date']) ?></lastmod>
    <changefreq>yearly</changefreq>
    <priority>0.6</priority>
  </url>
<?php endforeach; endif; ?>
</urlset>
