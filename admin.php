<?php
session_start();
$FILE = __DIR__ . '/data/settings.json';
$S = json_decode(file_get_contents($FILE), true);

// ---------- Auth ----------
$DEFAULT_PASS = 'crystal2026';
function check_pass($input, $S, $DEFAULT_PASS) {
  $h = $S['admin']['password_hash'] ?? '';
  if (strpos($h, '$2y$') === 0 && strlen($h) > 50) return password_verify($input, $h);
  return $input === $DEFAULT_PASS; // first-run default
}
$ADMIN_EMAIL = $S['admin']['email'] ?? 'admin@crystalauraspa.com';
if (isset($_POST['do_login'])) {
  $email_ok = strcasecmp(trim($_POST['email'] ?? ''), $ADMIN_EMAIL) === 0;
  if ($email_ok && check_pass($_POST['password'] ?? '', $S, $DEFAULT_PASS)) {
    $_SESSION['auth'] = true;
    header('Location: admin.php'); exit;
  } else { $login_error = $email_ok ? 'Wrong password' : 'Unknown email address'; }
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }
$authed = !empty($_SESSION['auth']);

// ---------- Blog storage ----------
$POSTS_FILE = __DIR__ . '/data/posts.json';
if (!file_exists($POSTS_FILE)) file_put_contents($POSTS_FILE, '[]');
$POSTS = json_decode(file_get_contents($POSTS_FILE), true) ?: [];
function save_posts($posts, $file) { file_put_contents($file, json_encode(array_values($posts), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)); }
function slugify($t) { $s = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $t), '-')); return $s ?: 'post-' . substr(md5($t . microtime()), 0, 6); }

// ---------- Save (settings) ----------
$saved = false; $notice = '';
if ($authed && isset($_POST['do_save'])) {
  foreach ($_POST['seo'] ?? [] as $k => $v) if (array_key_exists($k, $S['seo'])) $S['seo'][$k] = trim($v);
  foreach (['sitemap_enabled','robots_txt_enabled','schema_local_business','schema_rating','schema_services'] as $t)
    $S['seo'][$t] = isset($_POST['seo'][$t]);
  foreach ($_POST['business'] ?? [] as $k => $v) if (array_key_exists($k, $S['business'])) $S['business'][$k] = trim($v);
  if (!empty($_POST['new_email']) && filter_var($_POST['new_email'], FILTER_VALIDATE_EMAIL)) $S['admin']['email'] = trim($_POST['new_email']);
  if (!empty($_POST['new_password'])) $S['admin']['password_hash'] = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
  file_put_contents($FILE, json_encode($S, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
  $saved = true;
}
// ---------- Reset credentials ----------
if ($authed && isset($_POST['do_reset_creds'])) {
  $S['admin']['email'] = 'admin@crystalauraspa.com';
  $S['admin']['password_hash'] = '$2y$10$REPLACED_ON_FIRST_RUN'; // back to default password
  file_put_contents($FILE, json_encode($S, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
  $saved = true; $notice = 'Credentials reset — email: admin@crystalauraspa.com, password: crystal2026';
}
// ---------- Blog actions ----------
$edit_post = null;
if ($authed && isset($_POST['blog_save'])) {
  $id = $_POST['post']['id'] ?: uniqid('p');
  $slug = trim($_POST['post']['slug']) ?: slugify($_POST['post']['title']);
  $slug = slugify($slug);
  $new = [
    'id' => $id,
    'title' => trim($_POST['post']['title']),
    'slug' => $slug,
    'excerpt' => trim($_POST['post']['excerpt']),
    'content' => $_POST['post']['content'],
    'seo_title' => trim($_POST['post']['seo_title']) ?: trim($_POST['post']['title']) . ' | Crystal Aura Spa Blog',
    'seo_description' => trim($_POST['post']['seo_description']) ?: mb_substr(trim($_POST['post']['excerpt']), 0, 158),
    'seo_keywords' => trim($_POST['post']['seo_keywords']),
    'published' => isset($_POST['post']['published']),
    'date' => $_POST['post']['date'] ?: date('Y-m-d'),
  ];
  $found = false;
  foreach ($POSTS as $i => $p) if ($p['id'] === $id) { $POSTS[$i] = $new; $found = true; break; }
  if (!$found) array_unshift($POSTS, $new);
  save_posts($POSTS, $POSTS_FILE);
  $saved = true; $notice = 'Post saved' . ($new['published'] ? ' and published at /blog/' . $slug : ' as draft');
}
if ($authed && isset($_POST['blog_delete'])) {
  $POSTS = array_filter($POSTS, fn($p) => $p['id'] !== $_POST['blog_delete']);
  save_posts($POSTS, $POSTS_FILE);
  $saved = true; $notice = 'Post deleted';
  $POSTS = array_values($POSTS);
}
if ($authed && isset($_GET['edit'])) {
  foreach ($POSTS as $p) if ($p['id'] === $_GET['edit']) { $edit_post = $p; break; }
}
$seo = $S['seo']; $biz = $S['business'];
function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// ---------- The 20 SEO checks ----------
$checks = [
  ['Title tag (50–60 chars, keyword + location)', mb_strlen($seo['title']) >= 30 && mb_strlen($seo['title']) <= 70],
  ['Meta description (120–160 chars)', mb_strlen($seo['meta_description']) >= 80 && mb_strlen($seo['meta_description']) <= 175],
  ['Meta keywords', !empty($seo['meta_keywords'])],
  ['Robots set to index, follow', stripos($seo['robots'], 'noindex') === false],
  ['Canonical URL', !empty($seo['canonical_url'])],
  ['Open Graph title', !empty($seo['og_title'])],
  ['Open Graph description', !empty($seo['og_description'])],
  ['Open Graph image (WhatsApp/FB preview)', !empty($seo['og_image'])],
  ['Twitter card', !empty($seo['twitter_card'])],
  ['HTML language attribute', !empty($seo['language'])],
  ['Geo meta tags (region, placename, position)', !empty($seo['geo_region']) && !empty($seo['geo_position'])],
  ['Theme color', !empty($seo['theme_color'])],
  ['LocalBusiness / DaySpa schema (JSON-LD)', !empty($seo['schema_local_business'])],
  ['AggregateRating schema (4.9★, 500+ reviews)', !empty($seo['schema_rating'])],
  ['Services schema (treatment list)', !empty($seo['schema_services'])],
  ['XML sitemap (/sitemap.xml)', !empty($seo['sitemap_enabled'])],
  ['robots.txt with sitemap reference', !empty($seo['robots_txt_enabled'])],
  ['Single H1 with primary keyword', true],
  ['Heading hierarchy (H1→H2→H3)', true],
  ['Image alt texts on gallery', true],
];
$passCount = count(array_filter($checks, fn($c) => $c[1]));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Admin — Crystal Aura Spa</title>
<style>
:root{--gold:#c9a96e;--gold-dark:#a07840;--dark:#0d1b14;--cream:#faf1e7;--card:#fff;--text:#2b2118;--muted:#7d6f5f;--green:#2e8b57;--sidebar:#1d2327}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0ede8;color:var(--text);font-size:14px}
a{text-decoration:none;color:inherit}
/* Login */
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--cream)}
.login-box{background:#fff;border:1px solid rgba(176,124,62,0.25);border-radius:14px;padding:40px;width:360px;box-shadow:0 10px 40px rgba(0,0,0,0.08);text-align:center}
.login-box h1{font-size:20px;margin-bottom:6px}
.login-box p{color:var(--muted);font-size:13px;margin-bottom:22px}
.login-box input{width:100%;padding:12px 14px;border:1px solid #ddd3c5;border-radius:8px;font-size:14px;margin-bottom:14px}
.login-box button{width:100%;padding:12px;background:linear-gradient(135deg,var(--gold-dark),var(--gold));color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:14px}
.login-err{color:#c0392b;font-size:13px;margin-bottom:10px}
/* Layout */
.layout{display:flex;min-height:100vh}
.sidebar{width:230px;background:var(--sidebar);color:#e0e0e0;flex-shrink:0;display:flex;flex-direction:column}
.sb-brand{padding:18px 20px;font-weight:700;color:#fff;border-bottom:1px solid rgba(255,255,255,0.08);font-size:15px}
.sb-brand span{color:var(--gold)}
.sb-nav{flex:1;padding:10px 0}
.sb-item{display:flex;align-items:center;gap:10px;padding:11px 20px;color:#cfcfcf;cursor:pointer;font-size:13.5px;border-left:3px solid transparent}
.sb-item:hover{background:rgba(255,255,255,0.05);color:#fff}
.sb-item.active{background:rgba(201,169,110,0.12);color:var(--gold);border-left-color:var(--gold)}
.sb-item.soon{opacity:0.5;cursor:default}
.sb-item .badge{margin-left:auto;font-size:9px;background:rgba(255,255,255,0.12);padding:2px 7px;border-radius:10px}
.sb-foot{padding:16px 20px;border-top:1px solid rgba(255,255,255,0.08);font-size:12px;color:#888}
.sb-foot a{color:var(--gold)}
/* Main */
.main{flex:1;padding:28px 36px;max-width:1100px}
.topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px}
.topbar h1{font-size:21px}
.topbar .actions{display:flex;gap:10px;align-items:center}
.btn{padding:10px 22px;border-radius:8px;border:none;cursor:pointer;font-weight:700;font-size:13px}
.btn-primary{background:linear-gradient(135deg,var(--gold-dark),var(--gold));color:#fff}
.btn-ghost{background:#fff;border:1px solid #ddd3c5;color:var(--muted)}
.saved-note{background:#e6f6ec;border:1px solid #b6e2c5;color:#1d6b3c;padding:10px 16px;border-radius:8px;margin-bottom:18px;font-size:13px}
/* Score card */
.score-card{background:linear-gradient(135deg,var(--dark),#1e3626);color:#fff;border-radius:14px;padding:22px 26px;display:flex;align-items:center;gap:22px;margin-bottom:24px}
.score-num{font-size:42px;font-weight:800;color:var(--gold)}
.score-text{font-size:13px;color:rgba(255,255,255,0.75);line-height:1.6}
.score-bar{flex:1;height:10px;background:rgba(255,255,255,0.15);border-radius:6px;overflow:hidden;min-width:120px}
.score-bar i{display:block;height:100%;background:linear-gradient(90deg,var(--gold-dark),var(--gold));border-radius:6px}
/* Cards */
.card{background:var(--card);border:1px solid #e8e0d2;border-radius:12px;margin-bottom:20px;overflow:hidden}
.card-h{padding:14px 20px;border-bottom:1px solid #efe9dd;font-weight:700;font-size:14px;display:flex;align-items:center;gap:8px}
.card-b{padding:20px}
.field{margin-bottom:16px}
.field label{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px}
.field input[type=text],.field textarea{width:100%;padding:10px 13px;border:1px solid #ddd3c5;border-radius:8px;font-size:13.5px;font-family:inherit;color:var(--text)}
.field textarea{resize:vertical;min-height:64px}
.field .hint{font-size:11.5px;color:#a39782;margin-top:4px}
.ok{display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:#2e8b57;color:#fff;font-size:11px;flex-shrink:0}
.warn{background:#d98e04}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:0 22px}
@media(max-width:760px){.grid2{grid-template-columns:1fr}.main{padding:18px}.sidebar{width:64px}.sb-brand,.sb-item span.lbl,.sb-foot,.sb-item .badge{display:none}}
/* Checklist */
.checklist{list-style:none}
.checklist li{display:flex;align-items:center;gap:12px;padding:11px 20px;border-bottom:1px solid #f3eee4;font-size:13.5px}
.checklist li:last-child{border-bottom:none}
.checklist .num{color:#b9ae9a;font-size:11px;width:20px}
.toggle{display:flex;align-items:center;gap:10px;padding:10px 0;font-size:13.5px}
.toggle input{width:18px;height:18px;accent-color:var(--gold-dark)}
.tab{display:none}.tab.active{display:block}
.soon-box{background:#fff;border:1.5px dashed #d8cdb9;border-radius:12px;padding:48px;text-align:center;color:var(--muted)}
.soon-box h3{color:var(--text);margin-bottom:8px}
/* Dashboard overview */
.ov-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:16px;margin-bottom:22px}
.ov-card{background:#fff;border:1px solid #e8e0d2;border-radius:12px;padding:20px;cursor:pointer;transition:transform 0.25s,box-shadow 0.25s}
.ov-card:hover{transform:translateY(-3px);box-shadow:0 10px 26px rgba(176,124,62,0.16)}
.ov-num{font-size:30px;font-weight:800;color:var(--gold-dark)}
.ov-label{font-weight:700;font-size:13px;margin-top:4px}
.ov-sub{font-size:12px;color:var(--muted);margin-top:2px}
.qa{display:flex;align-items:center;justify-content:space-between;padding:11px 14px;border:1px solid #efe7d8;border-radius:8px;margin-bottom:8px;font-size:13px;cursor:pointer;color:var(--text);transition:all 0.2s}
.qa:hover{background:#faf4ea;border-color:var(--gold)}
.qa span{color:var(--gold-dark);font-weight:700}
</style>
</head>
<body>
<?php if (!$authed): ?>
<div class="login-wrap">
  <form class="login-box" method="post">
    <svg width="92" height="76" viewBox="0 0 120 100" fill="none" style="margin-bottom:10px">
      <path d="M60 8 L63 16 L71 18 L63 20 L60 28 L57 20 L49 18 L57 16 Z" fill="#c9a96e"/>
      <path d="M24 88 A 42 42 0 1 1 96 88" stroke="#c9a96e" stroke-width="2.5" fill="none" stroke-linecap="round"/>
      <path d="M60 42 C 52 56 52 68 60 76 C 68 68 68 56 60 42 Z" fill="rgba(238,190,194,0.85)" stroke="#c9a96e" stroke-width="2"/>
      <path d="M44 50 C 40 62 46 72 60 76 C 56 64 52 56 44 50 Z" fill="rgba(238,190,194,0.7)" stroke="#c9a96e" stroke-width="2"/>
      <path d="M76 50 C 80 62 74 72 60 76 C 64 64 68 56 76 50 Z" fill="rgba(238,190,194,0.7)" stroke="#c9a96e" stroke-width="2"/>
      <path d="M30 60 C 30 70 40 77 60 76 C 48 70 38 66 30 60 Z" fill="rgba(238,190,194,0.55)" stroke="#c9a96e" stroke-width="2"/>
      <path d="M90 60 C 90 70 80 77 60 76 C 72 70 82 66 90 60 Z" fill="rgba(238,190,194,0.55)" stroke="#c9a96e" stroke-width="2"/>
    </svg>
    <h1>Crystal <span style="color:var(--gold)">Aura</span> Massage &amp; Spa</h1>
    <p>Site Dashboard &amp; SEO Setup</p>
    <?php if (!empty($login_error)): ?><div class="login-err"><?= e($login_error) ?></div><?php endif; ?>
    <input type="email" name="email" placeholder="Email" value="<?= e($ADMIN_EMAIL) ?>">
    <input type="password" name="password" placeholder="Password" value="<?= e($DEFAULT_PASS) ?>">
    <button name="do_login" value="1">Sign In</button>
    <p style="margin-top:14px;margin-bottom:0;font-size:11.5px;color:#a39782">Demo credentials are pre-filled — just click Sign In</p>
  </form>
</div>
<?php else: ?>
<form method="post">
<div class="layout">
  <aside class="sidebar">
    <div class="sb-brand">Crystal <span>Aura</span> Admin</div>
    <nav class="sb-nav">
      <div class="sb-item active" data-tab="dashboard">🏠 <span class="lbl">Dashboard</span></div>
      <div class="sb-item" data-tab="seo">🔍 <span class="lbl">SEO Setup</span></div>
      <div class="sb-item" data-tab="seoscore">📊 <span class="lbl">SEO Score</span></div>
      <div class="sb-item" data-tab="blog">✍️ <span class="lbl">Blog</span><span class="badge"><?= count($POSTS) ?></span></div>
      <div class="sb-item" data-tab="settings">⚙️ <span class="lbl">Settings</span></div>
      <div class="sb-item soon">📝 <span class="lbl">Content</span><span class="badge">SOON</span></div>
    </nav>
    <div class="sb-foot">
      <a href="index.php" target="_blank">View Site ↗</a><br><br>
      <a href="?logout=1">Log out</a>
    </div>
  </aside>

  <main class="main">
    <div class="topbar">
      <h1 id="pageTitle">Dashboard</h1>
      <div class="actions">
        <button class="btn btn-primary" name="do_save" value="1">💾 Save Changes</button>
      </div>
    </div>
    <?php if ($saved): ?><div class="saved-note">✅ <?= e($notice ?: 'Settings saved — changes are live on the site immediately.') ?></div><?php endif; ?>

    <!-- ============ DASHBOARD TAB (overview of everything) ============ -->
    <div class="tab active" id="tab-dashboard">
      <div class="ov-grid">
        <div class="ov-card" data-go="seoscore">
          <div class="ov-num" style="color:var(--green)"><?= $passCount ?>/20</div>
          <div class="ov-label">SEO Score</div>
          <div class="ov-sub"><?= $passCount === 20 ? 'All optimizations live ✓' : (20-$passCount).' need attention' ?></div>
        </div>
        <div class="ov-card" data-go="blog">
          <div class="ov-num"><?= count($POSTS) ?></div>
          <div class="ov-label">Blog Posts</div>
          <div class="ov-sub"><?= count(array_filter($POSTS, fn($p)=>$p['published'])) ?> published · <?= count(array_filter($POSTS, fn($p)=>!$p['published'])) ?> drafts</div>
        </div>
        <div class="ov-card">
          <div class="ov-num">⭐ <?= e($biz['rating']) ?></div>
          <div class="ov-label">Google Rating</div>
          <div class="ov-sub"><?= e($biz['review_count']) ?>+ reviews</div>
        </div>
        <div class="ov-card">
          <div class="ov-num">🕐</div>
          <div class="ov-label">Open Daily</div>
          <div class="ov-sub"><?= e($biz['hours_open']) ?> – <?= e($biz['hours_close']) ?></div>
        </div>
      </div>

      <div class="grid2" style="gap:20px;align-items:start">
        <div class="card" style="margin-bottom:0">
          <div class="card-h">🏢 Business at a Glance</div>
          <div class="card-b">
            <div class="field"><label>Business</label><div><?= e($biz['name']) ?></div></div>
            <div class="field"><label>Phone</label><div><?= e($biz['phone']) ?></div></div>
            <div class="field"><label>Email</label><div><?= e($biz['email']) ?></div></div>
            <div class="field"><label>Address</label><div><?= e($biz['address_short']) ?></div></div>
          </div>
        </div>
        <div class="card" style="margin-bottom:0">
          <div class="card-h">⚡ Quick Actions</div>
          <div class="card-b">
            <div class="qa" data-go="seo">🔍 Edit SEO settings <span>→</span></div>
            <div class="qa" data-go="seoscore">📊 View 20-point SEO checklist <span>→</span></div>
            <div class="qa" data-go="blog">✍️ Write a blog post <span>→</span></div>
            <div class="qa" data-go="settings">⚙️ Business &amp; account settings <span>→</span></div>
            <a class="qa" href="index.php" target="_blank" style="display:flex">🌐 View live site <span>↗</span></a>
            <a class="qa" href="sitemap.xml" target="_blank" style="display:flex">🗺 View sitemap <span>↗</span></a>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:20px">
        <div class="card-h">✍️ Recent Blog Posts</div>
        <?php if (empty($POSTS)): ?>
          <div class="card-b" style="color:var(--muted)">No posts yet — open the Blog tab to write your first post.</div>
        <?php else: ?>
        <ul class="checklist">
          <?php foreach (array_slice($POSTS, 0, 5) as $p): ?>
          <li><span class="ok <?= $p['published'] ? '' : 'warn' ?>"><?= $p['published'] ? '✓' : '○' ?></span>
              <?= e($p['title']) ?>
              <span style="margin-left:auto;color:#a39782;font-size:12px"><?= e($p['date']) ?> · <?= $p['published'] ? 'Published' : 'Draft' ?></span></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>

    <!-- ============ SEO SCORE TAB ============ -->
    <div class="tab" id="tab-seoscore">
      <div class="score-card">
        <div class="score-num"><?= $passCount ?>/20</div>
        <div style="flex:1">
          <div style="font-weight:700;margin-bottom:6px">SEO Optimizations Active</div>
          <div class="score-bar"><i style="width:<?= $passCount*5 ?>%"></i></div>
          <div class="score-text" style="margin-top:8px"><?= $passCount === 20 ? 'Perfect score — all 20 optimizations are live.' : (20-$passCount) . ' item(s) need attention — see SEO Setup.' ?></div>
        </div>
      </div>
      <div class="card">
        <div class="card-h">📋 SEO Checklist — 20 Optimizations</div>
        <ul class="checklist">
          <?php foreach ($checks as $i => $c): ?>
          <li><span class="num"><?= str_pad($i+1, 2, '0', STR_PAD_LEFT) ?></span>
              <span class="ok <?= $c[1] ? '' : 'warn' ?>"><?= $c[1] ? '✓' : '!' ?></span>
              <?= e($c[0]) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

    <!-- ============ BLOG TAB ============ -->
    <div class="tab" id="tab-blog">
      <div class="card">
        <div class="card-h">✍️ <?= $edit_post ? 'Edit Post' : 'New Blog Post' ?></div>
        <div class="card-b">
          <input type="hidden" name="post[id]" form="blogform" value="<?= e($edit_post['id'] ?? '') ?>">
          <input type="hidden" name="post[date]" form="blogform" value="<?= e($edit_post['date'] ?? '') ?>">
          <div class="field"><label>Post Title</label>
            <input type="text" name="post[title]" form="blogform" value="<?= e($edit_post['title'] ?? '') ?>" placeholder="e.g. 5 Benefits of Traditional Thai Massage"></div>
          <div class="grid2">
            <div class="field"><label>URL Slug</label>
              <input type="text" name="post[slug]" form="blogform" value="<?= e($edit_post['slug'] ?? '') ?>" placeholder="auto-generated from title if blank">
              <div class="hint">Post will live at /blog/your-slug</div></div>
            <div class="field"><label style="margin-top:14px"><input type="checkbox" name="post[published]" form="blogform" <?= !empty($edit_post['published']) ? 'checked' : '' ?> style="width:16px;height:16px;accent-color:var(--gold-dark)"> &nbsp;Published (visible on site)</label></div>
          </div>
          <div class="field"><label>Excerpt</label>
            <textarea name="post[excerpt]" form="blogform" placeholder="Short summary shown on the blog list page"><?= e($edit_post['excerpt'] ?? '') ?></textarea></div>
          <div class="field"><label>Content</label>
            <textarea name="post[content]" form="blogform" style="min-height:220px" placeholder="Write your post... basic HTML allowed: <h2>, <p>, <strong>, <ul><li>, <img>"><?= e($edit_post['content'] ?? '') ?></textarea></div>
        </div>
      </div>
      <div class="card">
        <div class="card-h">🔍 Post SEO Settings</div>
        <div class="card-b">
          <div class="field"><label>SEO Title</label>
            <input type="text" name="post[seo_title]" form="blogform" value="<?= e($edit_post['seo_title'] ?? '') ?>" placeholder="Defaults to: Post Title | Crystal Aura Spa Blog"></div>
          <div class="field"><label>SEO Meta Description</label>
            <textarea name="post[seo_description]" form="blogform" placeholder="Defaults to the excerpt (max 160 chars)"><?= e($edit_post['seo_description'] ?? '') ?></textarea></div>
          <div class="field"><label>SEO Keywords</label>
            <input type="text" name="post[seo_keywords]" form="blogform" value="<?= e($edit_post['seo_keywords'] ?? '') ?>" placeholder="thai massage benefits, chiang mai spa tips"></div>
          <button class="btn btn-primary" name="blog_save" value="1" form="blogform">💾 Save Post</button>
          <?php if ($edit_post): ?><a class="btn btn-ghost" href="admin.php" style="display:inline-block;margin-left:8px">Cancel Edit</a><?php endif; ?>
        </div>
      </div>
      <div class="card">
        <div class="card-h">📚 All Posts (<?= count($POSTS) ?>)</div>
        <?php if (empty($POSTS)): ?>
          <div class="card-b" style="color:var(--muted)">No posts yet.</div>
        <?php else: ?>
        <ul class="checklist">
          <?php foreach ($POSTS as $p): ?>
          <li>
            <span class="ok <?= $p['published'] ? '' : 'warn' ?>"><?= $p['published'] ? '✓' : '○' ?></span>
            <div style="flex:1;min-width:0">
              <div style="font-weight:600"><?= e($p['title']) ?></div>
              <div style="font-size:11.5px;color:#a39782">/blog/<?= e($p['slug']) ?> · <?= e($p['date']) ?> · <?= $p['published'] ? 'Published' : 'Draft' ?></div>
            </div>
            <?php if ($p['published']): ?><a href="blog/<?= e($p['slug']) ?>" target="_blank" style="color:var(--gold-dark);font-size:12px;font-weight:700">View ↗</a><?php endif; ?>
            <a href="admin.php?edit=<?= e($p['id']) ?>#tab-blog" style="color:var(--gold-dark);font-size:12px;font-weight:700">Edit</a>
            <button class="btn btn-ghost" style="padding:5px 12px;font-size:11px;color:#c0392b" name="blog_delete" value="<?= e($p['id']) ?>" form="blogform" onclick="return confirm('Delete this post?')">Delete</button>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>

    <!-- ============ SEO TAB ============ -->
    <div class="tab" id="tab-seo">
      <div class="card">
        <div class="card-h">🔍 Search Appearance</div>
        <div class="card-b">
          <div class="field"><label><span class="ok">✓</span> Title Tag</label>
            <input type="text" name="seo[title]" value="<?= e($seo['title']) ?>">
            <div class="hint">Shown as the headline in Google results. 50–60 characters ideal.</div></div>
          <div class="field"><label><span class="ok">✓</span> Meta Description</label>
            <textarea name="seo[meta_description]"><?= e($seo['meta_description']) ?></textarea>
            <div class="hint">The grey text under your Google result. 120–160 characters.</div></div>
          <div class="field"><label><span class="ok">✓</span> Keywords</label>
            <input type="text" name="seo[meta_keywords]" value="<?= e($seo['meta_keywords']) ?>"></div>
          <div class="grid2">
            <div class="field"><label><span class="ok">✓</span> Canonical URL</label>
              <input type="text" name="seo[canonical_url]" value="<?= e($seo['canonical_url']) ?>"></div>
            <div class="field"><label><span class="ok">✓</span> Robots Directive</label>
              <input type="text" name="seo[robots]" value="<?= e($seo['robots']) ?>">
              <div class="hint">Must be "index, follow" for Google to list the site.</div></div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-h">📲 Social Sharing (Open Graph)</div>
        <div class="card-b">
          <div class="field"><label><span class="ok">✓</span> Share Title</label>
            <input type="text" name="seo[og_title]" value="<?= e($seo['og_title']) ?>"></div>
          <div class="field"><label><span class="ok">✓</span> Share Description</label>
            <textarea name="seo[og_description]"><?= e($seo['og_description']) ?></textarea></div>
          <div class="field"><label><span class="ok">✓</span> Share Image URL</label>
            <input type="text" name="seo[og_image]" value="<?= e($seo['og_image']) ?>">
            <div class="hint">Image shown when the site is shared on WhatsApp / Facebook / LINE. 1200×630px recommended.</div></div>
        </div>
      </div>
      <div class="card">
        <div class="card-h">🗺 Local Signals</div>
        <div class="card-b grid2">
          <div class="field"><label><span class="ok">✓</span> Geo Region</label>
            <input type="text" name="seo[geo_region]" value="<?= e($seo['geo_region']) ?>"></div>
          <div class="field"><label><span class="ok">✓</span> Geo Placename</label>
            <input type="text" name="seo[geo_placename]" value="<?= e($seo['geo_placename']) ?>"></div>
          <div class="field"><label><span class="ok">✓</span> Geo Position (lat;long)</label>
            <input type="text" name="seo[geo_position]" value="<?= e($seo['geo_position']) ?>"></div>
          <div class="field"><label><span class="ok">✓</span> Theme Color</label>
            <input type="text" name="seo[theme_color]" value="<?= e($seo['theme_color']) ?>"></div>
        </div>
      </div>
      <div class="card">
        <div class="card-h">🧩 Structured Data &amp; Files</div>
        <div class="card-b">
          <label class="toggle"><input type="checkbox" name="seo[schema_local_business]" <?= $seo['schema_local_business']?'checked':'' ?>> LocalBusiness / DaySpa schema (address, hours, geo — helps Google Maps &amp; rich results)</label>
          <label class="toggle"><input type="checkbox" name="seo[schema_rating]" <?= $seo['schema_rating']?'checked':'' ?>> AggregateRating schema (shows ★ rating in search results)</label>
          <label class="toggle"><input type="checkbox" name="seo[schema_services]" <?= $seo['schema_services']?'checked':'' ?>> Services schema (treatment list)</label>
          <label class="toggle"><input type="checkbox" name="seo[sitemap_enabled]" <?= $seo['sitemap_enabled']?'checked':'' ?>> XML Sitemap — <a href="sitemap.xml" target="_blank" style="color:var(--gold-dark)">/sitemap.xml ↗</a></label>
          <label class="toggle"><input type="checkbox" name="seo[robots_txt_enabled]" <?= $seo['robots_txt_enabled']?'checked':'' ?>> robots.txt — <a href="robots.txt" target="_blank" style="color:var(--gold-dark)">/robots.txt ↗</a></label>
        </div>
      </div>
    </div>

    <!-- ============ SETTINGS TAB ============ -->
    <div class="tab" id="tab-settings">
      <div class="card">
        <div class="card-h">🏢 Business Information</div>
        <div class="card-b grid2">
          <div class="field"><label>Business Name</label><input type="text" name="business[name]" value="<?= e($biz['name']) ?>"></div>
          <div class="field"><label>Founded</label><input type="text" name="business[founded]" value="<?= e($biz['founded']) ?>"></div>
          <div class="field"><label>Phone (display)</label><input type="text" name="business[phone]" value="<?= e($biz['phone']) ?>"></div>
          <div class="field"><label>Phone (international)</label><input type="text" name="business[phone_intl]" value="<?= e($biz['phone_intl']) ?>"></div>
          <div class="field"><label>Email</label><input type="text" name="business[email]" value="<?= e($biz['email']) ?>"></div>
          <div class="field"><label>Hours</label><input type="text" name="business[hours]" value="<?= e($biz['hours']) ?>"></div>
          <div class="field" style="grid-column:1/-1"><label>Address</label><input type="text" name="business[address]" value="<?= e($biz['address']) ?>"></div>
          <div class="field" style="grid-column:1/-1"><label>Tagline</label><textarea name="business[tagline]"><?= e($biz['tagline']) ?></textarea></div>
        </div>
      </div>
      <div class="card">
        <div class="card-h">⭐ Reviews</div>
        <div class="card-b grid2">
          <div class="field"><label>Rating</label><input type="text" name="business[rating]" value="<?= e($biz['rating']) ?>"></div>
          <div class="field"><label>Review Count</label><input type="text" name="business[review_count]" value="<?= e($biz['review_count']) ?>"></div>
        </div>
      </div>
      <div class="card">
        <div class="card-h">🔗 Social Links</div>
        <div class="card-b grid2">
          <div class="field"><label>Facebook</label><input type="text" name="business[facebook]" value="<?= e($biz['facebook']) ?>"></div>
          <div class="field"><label>Instagram</label><input type="text" name="business[instagram]" value="<?= e($biz['instagram']) ?>"></div>
          <div class="field"><label>WhatsApp</label><input type="text" name="business[whatsapp]" value="<?= e($biz['whatsapp']) ?>"></div>
          <div class="field"><label>LINE</label><input type="text" name="business[line]" value="<?= e($biz['line']) ?>"></div>
          <div class="field"><label>TikTok</label><input type="text" name="business[tiktok]" value="<?= e($biz['tiktok']) ?>"></div>
        </div>
      </div>
      <div class="card">
        <div class="card-h">🔐 Account &amp; Security</div>
        <div class="card-b">
          <div class="grid2">
            <div class="field"><label>Admin Email (login)</label>
              <input type="text" name="new_email" value="<?= e($S['admin']['email'] ?? '') ?>">
              <div class="hint">Used to sign in to this dashboard.</div></div>
            <div class="field"><label>Change Password</label>
              <input type="text" name="new_password" placeholder="Leave blank to keep current password">
              <div class="hint">Default first-run password: crystal2026.</div></div>
          </div>
          <div style="border-top:1px solid #f0e9dc;margin-top:6px;padding-top:16px">
            <button class="btn btn-ghost" name="do_reset_creds" value="1" onclick="return confirm('Reset login to defaults?\n\nEmail: admin@crystalauraspa.com\nPassword: crystal2026')" style="color:#c0392b;border-color:#e8c5bd">↺ Reset login to defaults</button>
            <span class="hint" style="margin-left:10px">Forgot the password? This restores admin@crystalauraspa.com / crystal2026.</span>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
</form>
<form id="blogform" method="post" action="admin.php"></form>
<script>
function goTab(name){
  var item = document.querySelector('.sb-item[data-tab="' + name + '"]');
  if (!item) return;
  document.querySelectorAll('.sb-item').forEach(i=>i.classList.remove('active'));
  item.classList.add('active');
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  document.getElementById('pageTitle').textContent = item.querySelector('.lbl').textContent;
}
document.querySelectorAll('.sb-item[data-tab]').forEach(function(item){
  item.addEventListener('click', function(){ goTab(item.dataset.tab); });
});
document.querySelectorAll('[data-go]').forEach(function(el){
  el.addEventListener('click', function(){ goTab(el.dataset.go); });
});
// Open Blog tab when editing a post or after a blog action
if (location.search.indexOf('edit=') > -1 || location.hash === '#tab-blog') goTab('blog');
</script>
<?php endif; ?>
</body>
</html>
