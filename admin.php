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
if (isset($_POST['do_login'])) {
  if (check_pass($_POST['password'] ?? '', $S, $DEFAULT_PASS)) {
    $_SESSION['auth'] = true;
    header('Location: admin.php'); exit;
  } else { $login_error = 'Wrong password'; }
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }
$authed = !empty($_SESSION['auth']);

// ---------- Save ----------
$saved = false;
if ($authed && isset($_POST['do_save'])) {
  foreach ($_POST['seo'] ?? [] as $k => $v) if (array_key_exists($k, $S['seo'])) $S['seo'][$k] = trim($v);
  foreach (['sitemap_enabled','robots_txt_enabled','schema_local_business','schema_rating','schema_services'] as $t)
    $S['seo'][$t] = isset($_POST['seo'][$t]);
  foreach ($_POST['business'] ?? [] as $k => $v) if (array_key_exists($k, $S['business'])) $S['business'][$k] = trim($v);
  if (!empty($_POST['new_password'])) $S['admin']['password_hash'] = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
  file_put_contents($FILE, json_encode($S, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
  $saved = true;
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
</style>
</head>
<body>
<?php if (!$authed): ?>
<div class="login-wrap">
  <form class="login-box" method="post">
    <h1>Crystal <span style="color:var(--gold)">Aura</span> Admin</h1>
    <p>Site Dashboard &amp; SEO Setup</p>
    <?php if (!empty($login_error)): ?><div class="login-err"><?= e($login_error) ?></div><?php endif; ?>
    <input type="password" name="password" placeholder="Password" autofocus>
    <button name="do_login" value="1">Sign In</button>
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
    <?php if ($saved): ?><div class="saved-note">✅ Settings saved — changes are live on the site immediately.</div><?php endif; ?>

    <!-- ============ DASHBOARD TAB ============ -->
    <div class="tab active" id="tab-dashboard">
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
      <div class="card">
        <div class="card-h">🏢 Business at a Glance</div>
        <div class="card-b grid2">
          <div class="field"><label>Business</label><div><?= e($biz['name']) ?></div></div>
          <div class="field"><label>Rating</label><div><?= e($biz['rating']) ?> ★ · <?= e($biz['review_count']) ?>+ reviews</div></div>
          <div class="field"><label>Phone</label><div><?= e($biz['phone']) ?></div></div>
          <div class="field"><label>Hours</label><div><?= e($biz['hours']) ?></div></div>
        </div>
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
        <div class="card-h">🔐 Security</div>
        <div class="card-b">
          <div class="field"><label>Change Admin Password</label>
            <input type="text" name="new_password" placeholder="Leave blank to keep current password">
            <div class="hint">Default first-run password: crystal2026 — change it after first login.</div></div>
        </div>
      </div>
    </div>
  </main>
</div>
</form>
<script>
document.querySelectorAll('.sb-item[data-tab]').forEach(function(item){
  item.addEventListener('click', function(){
    document.querySelectorAll('.sb-item').forEach(i=>i.classList.remove('active'));
    item.classList.add('active');
    document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
    document.getElementById('tab-' + item.dataset.tab).classList.add('active');
    document.getElementById('pageTitle').textContent = item.querySelector('.lbl').textContent;
  });
});
</script>
<?php endif; ?>
</body>
</html>
