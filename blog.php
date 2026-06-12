<?php
$S = json_decode(file_get_contents(__DIR__ . '/data/settings.json'), true);
$seo = $S['seo']; $biz = $S['business'];
$POSTS = file_exists(__DIR__ . '/data/posts.json') ? (json_decode(file_get_contents(__DIR__ . '/data/posts.json'), true) ?: []) : [];
$published = array_values(array_filter($POSTS, fn($p) => !empty($p['published'])));
function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
$base = rtrim($seo['canonical_url'], '/');

// Single post or index?
$slug = $_GET['slug'] ?? '';
$post = null;
if ($slug) {
  foreach ($published as $p) if ($p['slug'] === $slug) { $post = $p; break; }
  if (!$post) { http_response_code(404); }
}
$page_title = $post ? $post['seo_title'] : 'Blog | ' . $biz['name'] . ' — Wellness Tips & Spa Guides';
$page_desc  = $post ? $post['seo_description'] : 'Wellness tips, Thai massage guides and spa advice from ' . $biz['name'] . ' in Nimman, Chiang Mai.';
$page_url   = $post ? $base . '/blog/' . $post['slug'] : $base . '/blog';
?>
<!DOCTYPE html>
<html lang="<?= e($seo['language']) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title) ?></title>
<meta name="description" content="<?= e($page_desc) ?>">
<?php if ($post && $post['seo_keywords']): ?><meta name="keywords" content="<?= e($post['seo_keywords']) ?>"><?php endif; ?>
<meta name="robots" content="<?= e($seo['robots']) ?>">
<link rel="canonical" href="<?= e($page_url) ?>">
<meta property="og:title" content="<?= e($page_title) ?>">
<meta property="og:description" content="<?= e($page_desc) ?>">
<meta property="og:type" content="<?= $post ? 'article' : 'website' ?>">
<meta property="og:url" content="<?= e($page_url) ?>">
<meta property="og:site_name" content="<?= e($biz['name']) ?>">
<meta property="og:image" content="<?= e($seo['og_image']) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php if ($post): ?>
<script type="application/ld+json">
<?= json_encode(['@context'=>'https://schema.org','@type'=>'BlogPosting','headline'=>$post['title'],'description'=>$post['seo_description'],'datePublished'=>$post['date'],'author'=>['@type'=>'Organization','name'=>$biz['name']],'publisher'=>['@type'=>'Organization','name'=>$biz['name']],'mainEntityOfPage'=>$page_url], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>
</script>
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<style>
:root{--gold:#c9a96e;--gold-dark:#a07840;--dark:#0d1b14;--cream:#faf1e7;--text:#2b2118;--muted:#7d6f5f}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Lato',sans-serif;background:var(--cream);color:var(--text);line-height:1.8}
a{color:var(--gold-dark);text-decoration:none}
.topnav{background:var(--dark);padding:16px 24px;display:flex;align-items:center;justify-content:space-between}
.topnav .brand{font-family:'Cormorant Garamond',serif;font-size:20px;color:#fff}
.topnav .brand span{color:var(--gold)}
.topnav a.back{color:rgba(255,255,255,0.8);font-size:13px;letter-spacing:0.06em;text-transform:uppercase;font-weight:700}
.wrap{max-width:760px;margin:0 auto;padding:56px 24px 80px}
.blog-label{font-size:12px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:var(--gold-dark);text-align:center;margin-bottom:10px}
h1.blog-title{font-family:'Cormorant Garamond',serif;font-size:clamp(30px,5vw,44px);font-weight:500;text-align:center;margin-bottom:14px;line-height:1.25}
.post-meta{text-align:center;color:#a39782;font-size:13px;margin-bottom:36px}
.divider{width:60px;height:2px;background:var(--gold);margin:0 auto 40px}
.post-card{background:#fff;border:1px solid rgba(176,124,62,0.2);border-radius:14px;padding:28px;margin-bottom:20px;transition:transform 0.3s,box-shadow 0.3s;display:block;color:var(--text)}
.post-card:hover{transform:translateY(-4px);box-shadow:0 14px 36px rgba(176,124,62,0.18)}
.post-card h2{font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin-bottom:8px}
.post-card .pc-meta{font-size:12px;color:#a39782;margin-bottom:10px}
.post-card p{color:var(--muted);font-size:14.5px}
.post-card .read{display:inline-block;margin-top:12px;font-weight:700;font-size:12.5px;letter-spacing:0.08em;text-transform:uppercase;color:var(--gold-dark)}
.article{background:#fff;border:1px solid rgba(176,124,62,0.2);border-radius:16px;padding:clamp(24px,5vw,48px)}
.article h2{font-family:'Cormorant Garamond',serif;font-size:26px;margin:28px 0 12px;font-weight:600}
.article p{margin-bottom:16px;color:#4a4036;font-size:15.5px}
.article ul,.article ol{margin:0 0 16px 22px;color:#4a4036;font-size:15.5px}
.article img{max-width:100%;border-radius:10px;margin:10px 0}
.cta{margin-top:36px;background:var(--cream);border:1px solid rgba(176,124,62,0.25);border-radius:12px;padding:24px;text-align:center}
.cta a{display:inline-block;margin-top:10px;background:linear-gradient(135deg,var(--gold-dark),var(--gold));color:#fff;padding:12px 30px;border-radius:8px;font-weight:700;font-size:13px;letter-spacing:0.08em;text-transform:uppercase}
.empty{text-align:center;color:var(--muted);padding:60px 0}
</style>
</head>
<body>
<nav class="topnav">
  <div class="brand">Crystal <span>Aura</span> Massage &amp; Spa</div>
  <a class="back" href="/">← Back to Site</a>
</nav>
<div class="wrap">
<?php if ($post): ?>
  <div class="blog-label">Crystal Aura Blog</div>
  <h1 class="blog-title"><?= e($post['title']) ?></h1>
  <div class="post-meta"><?= e(date('F j, Y', strtotime($post['date']))) ?></div>
  <div class="divider"></div>
  <article class="article">
    <?= $post['content'] /* trusted admin HTML */ ?>
    <div class="cta">
      <div style="font-family:'Cormorant Garamond',serif;font-size:22px">Ready to experience it yourself?</div>
      <a href="/#booking">Book a Treatment</a>
    </div>
  </article>
  <p style="text-align:center;margin-top:28px"><a href="/blog">← All posts</a></p>
<?php elseif ($slug): ?>
  <div class="empty"><h1 class="blog-title">Post not found</h1><p><a href="/blog">← All posts</a></p></div>
<?php else: ?>
  <div class="blog-label">Crystal Aura Blog</div>
  <h1 class="blog-title">Wellness Tips &amp; Spa Guides</h1>
  <div class="divider"></div>
  <?php if (empty($published)): ?>
    <div class="empty">No posts yet — check back soon.</div>
  <?php else: foreach ($published as $p): ?>
    <a class="post-card" href="/blog/<?= e($p['slug']) ?>">
      <h2><?= e($p['title']) ?></h2>
      <div class="pc-meta"><?= e(date('F j, Y', strtotime($p['date']))) ?></div>
      <p><?= e($p['excerpt']) ?></p>
      <span class="read">Read More →</span>
    </a>
  <?php endforeach; endif; ?>
<?php endif; ?>
</div>
</body>
</html>
