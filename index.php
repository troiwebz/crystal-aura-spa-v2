<?php
$S = json_decode(file_get_contents(__DIR__ . '/data/settings.json'), true);
$seo = $S['seo']; $biz = $S['business'];
$schema = $S['schema'] ?? []; $perf = $S['performance'] ?? []; $tech = $S['technical'] ?? [];
$imgcfg = $S['images'] ?? []; $reviews = $S['reviews'] ?? []; $paa = $S['paa'] ?? []; $links = $S['links'] ?? [];
function e($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
// ---- SEO post-processor (settings-driven: performance, image alts, link rels, PAA section) ----
ob_start(function($html) use ($perf, $imgcfg, $links, $paa, $S) {
  if (!empty($perf['lazy_images'])) {
    $html = preg_replace('/<img(?![^>]*loading=)/i', '<img loading="lazy" decoding="async"', $html);
  }
  if (!empty($perf['defer_videos'])) {
    $html = preg_replace('/<video(?![^>]*preload=)/i', '<video preload="metadata"', $html);
  }
  if (!empty($perf['strip_comments'])) {
    $html = preg_replace('/<!--(?!\[if).*?-->/s', '', $html);
  }
  // Image SEO — per-image alt overrides (keyed by md5 of src), then default alt for any img still missing one
  $html = preg_replace_callback('/<img\b[^>]*>/i', function($m) use ($imgcfg, $S) {
    $tag = $m[0];
    if (preg_match('/src="([^"]+)"/i', $tag, $sm)) {
      $key = md5($sm[1]);
      if (!empty($imgcfg['alt_overrides'][$key])) {
        $alt = htmlspecialchars($imgcfg['alt_overrides'][$key], ENT_QUOTES, 'UTF-8');
        $tag = preg_match('/alt="[^"]*"/i', $tag) ? preg_replace('/alt="[^"]*"/i', 'alt="'.$alt.'"', $tag) : str_replace('<img', '<img alt="'.$alt.'"', $tag);
        return $tag;
      }
    }
    if (!empty($imgcfg['apply_default_alt']) && !preg_match('/alt="[^"]+"/i', $tag)) {
      $alt = htmlspecialchars($S['onpage']['alt_default'] ?? 'Crystal Aura Massage & Spa', ENT_QUOTES, 'UTF-8');
      $tag = preg_match('/alt=""/i', $tag) ? preg_replace('/alt=""/i', 'alt="'.$alt.'"', $tag) : str_replace('<img', '<img alt="'.$alt.'"', $tag);
    }
    return $tag;
  }, $html);
  // Link SEO — external links get rel="noopener nofollow"
  if (!empty($links['noopener_external']) || !empty($links['nofollow_external'])) {
    $rel = trim((!empty($links['noopener_external']) ? 'noopener ' : '') . (!empty($links['nofollow_external']) ? 'nofollow' : ''));
    $html = preg_replace_callback('/<a\b[^>]*href="https?:\/\/[^"]*"[^>]*>/i', function($m) use ($rel) {
      $tag = $m[0];
      if (preg_match('/rel="/i', $tag)) return $tag;
      return str_replace('<a ', '<a rel="'.$rel.'" ', $tag);
    }, $html);
  }
  // PAA — visible FAQ section injected before the footer
  if (!empty($paa['visible_section']) && !empty($paa['items'])) {
    $faqHtml = '<section id="faq" style="background:#fff;padding:80px 24px"><div style="max-width:820px;margin:0 auto">'
      . '<div style="text-align:center;font-size:12px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#b07c3e;margin-bottom:10px">Good to Know</div>'
      . '<h2 style="font-family:\'Cormorant Garamond\',serif;font-size:clamp(28px,4vw,38px);font-weight:500;text-align:center;color:#2b2118;margin-bottom:8px">Frequently Asked <em style="font-style:italic;color:#b07c3e">Questions</em></h2>'
      . '<div style="width:60px;height:2px;background:#c9a96e;margin:18px auto 36px"></div>';
    foreach ($paa['items'] as $i => $f) {
      $faqHtml .= '<details style="background:#faf1e7;border:1px solid rgba(176,124,62,0.22);border-radius:12px;margin-bottom:12px;padding:0;overflow:hidden"'.($i===0?' open':'').'>'
        . '<summary style="cursor:pointer;padding:18px 22px;font-weight:700;font-size:15px;color:#2b2118;list-style:none;display:flex;justify-content:space-between;align-items:center">'
        . htmlspecialchars($f['q'], ENT_QUOTES, 'UTF-8') . '<span style="color:#b07c3e;font-size:20px;flex-shrink:0;margin-left:12px">+</span></summary>'
        . '<div style="padding:0 22px 18px;color:#5d5246;font-size:14.5px;line-height:1.8">' . htmlspecialchars($f['a'], ENT_QUOTES, 'UTF-8') . '</div></details>';
    }
    $faqHtml .= '</div></section>';
    $html = preg_replace('/<!-- FOOTER -->|<footer\b/i', $faqHtml . '$0', $html, 1);
  }
  return $html;
});
?><!DOCTYPE html>
<html lang="<?= e($seo['language']) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- ===== SEO (managed via /admin.php) ===== -->
<title><?= e($seo['title']) ?></title>
<meta name="description" content="<?= e($seo['meta_description']) ?>">
<meta name="keywords" content="<?= e($seo['meta_keywords']) ?>">
<meta name="robots" content="<?= e($seo['robots']) ?>">
<link rel="canonical" href="<?= e($seo['canonical_url']) ?>">
<meta name="theme-color" content="<?= e($seo['theme_color']) ?>">
<meta name="geo.region" content="<?= e($seo['geo_region']) ?>">
<meta name="geo.placename" content="<?= e($seo['geo_placename']) ?>">
<meta name="geo.position" content="<?= e($seo['geo_position']) ?>">
<meta property="og:title" content="<?= e($seo['og_title']) ?>">
<meta property="og:description" content="<?= e($seo['og_description']) ?>">
<meta property="og:image" content="<?= e($seo['og_image']) ?>">
<meta property="og:type" content="<?= e($seo['og_type']) ?>">
<meta property="og:url" content="<?= e($seo['canonical_url']) ?>">
<meta property="og:site_name" content="<?= e($biz['name']) ?>">
<meta name="twitter:card" content="<?= e($seo['twitter_card']) ?>">
<meta name="twitter:title" content="<?= e($seo['og_title']) ?>">
<meta name="twitter:description" content="<?= e($seo['og_description']) ?>">
<meta name="twitter:image" content="<?= e($seo['og_image']) ?>">
<?php if ($seo['schema_local_business']): ?>
<script type="application/ld+json">
<?= json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'DaySpa',
  'name' => $biz['name'],
  'description' => $biz['tagline'],
  'url' => $seo['canonical_url'],
  'telephone' => $biz['phone_intl'],
  'email' => $biz['email'],
  'priceRange' => $biz['price_range'],
  'foundingDate' => $biz['founded'],
  'address' => ['@type'=>'PostalAddress','streetAddress'=>$biz['address'],'addressLocality'=>'Chiang Mai','addressRegion'=>'Chiang Mai','postalCode'=>'50200','addressCountry'=>'TH'],
  'geo' => ['@type'=>'GeoCoordinates','latitude'=>$biz['latitude'],'longitude'=>$biz['longitude']],
  'openingHoursSpecification' => ['@type'=>'OpeningHoursSpecification','dayOfWeek'=>['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'],'opens'=>$biz['hours_open'],'closes'=>$biz['hours_close']],
  'sameAs' => [$biz['facebook'],$biz['instagram'],$biz['tiktok'],$biz['line']],
] + ($seo['schema_rating'] ? ['aggregateRating'=>['@type'=>'AggregateRating','ratingValue'=>$biz['rating'],'reviewCount'=>$biz['review_count'],'bestRating'=>'5']] : [])
  + (!empty($reviews['items']) ? ['review'=>array_map(fn($r)=>['@type'=>'Review','author'=>['@type'=>'Person','name'=>$r['author']],'reviewRating'=>['@type'=>'Rating','ratingValue'=>$r['rating'],'bestRating'=>'5'],'datePublished'=>$r['date'],'reviewBody'=>$r['text']], $reviews['items'])] : []), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) ?>
</script>
<?php endif; ?>
<?php if ($seo['schema_services'] && !empty($schema['services'])): ?>
<script type="application/ld+json">
<?= json_encode(['@context'=>'https://schema.org','@type'=>'ItemList','name'=>'Spa Treatments','itemListElement'=>array_map(function($s,$i) use ($schema,$seo) {
  return ['@type'=>'ListItem','position'=>$i+1,'item'=>[
    '@type'=>'Service','name'=>$s['name'],
    'provider'=>['@type'=>$schema['business_type'] ?? 'DaySpa','name'=>'Crystal Aura Massage & Spa'],
    'offers'=>['@type'=>'Offer','price'=>$s['price'],'priceCurrency'=>'THB','url'=>$seo['canonical_url'].'#pricing'],
  ]];
}, $schema['services'], array_keys($schema['services']))], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>
</script>
<?php endif; ?>
<?php $allFaqs = array_merge($schema['faqs'] ?? [], $paa['items'] ?? []); if (!empty($allFaqs)): ?>
<script type="application/ld+json">
<?= json_encode(['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>array_map(fn($f)=>['@type'=>'Question','name'=>$f['q'],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f['a']]], $allFaqs)], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>
</script>
<?php endif; ?>
<?php if (!empty($schema['breadcrumbs'])): ?>
<script type="application/ld+json">
<?= json_encode(['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>[['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>$seo['canonical_url']],['@type'=>'ListItem','position'=>2,'name'=>'Treatments & Rates','item'=>$seo['canonical_url'].'#pricing'],['@type'=>'ListItem','position'=>3,'name'=>'Book','item'=>$seo['canonical_url'].'#booking']]], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>
</script>
<?php endif; ?>
<?php if (!empty($tech['hreflang'])): ?>
<link rel="alternate" hreflang="<?= e($tech['hreflang']) ?>" href="<?= e($seo['canonical_url']) ?>">
<link rel="alternate" hreflang="x-default" href="<?= e($seo['canonical_url']) ?>">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<style>
:root {
  --gold: #c9a96e;
  --gold-light: #e8d5b0;
  --gold-dark: #a07840;
  --dark: #0d1b14;
  --dark2: #111f18;
  --cream: #f9f5ef;
  --cream2: #f2ece2;
  --text: #333;
  --text-light: #666;
  --white: #fff;
  --font-serif: 'Cormorant Garamond', Georgia, serif;
  --font-sans: 'Lato', sans-serif;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--font-sans);color:var(--text);background:#fff;overflow-x:hidden}
img{max-width:100%;display:block}
a{text-decoration:none;color:inherit}
ul{list-style:none}

/* ===== UTILITY ===== */
.section-label{display:block;font-family:var(--font-sans);font-size:11px;font-weight:700;letter-spacing:0.3em;text-transform:uppercase;color:var(--gold);margin-bottom:12px}
.gold-divider{width:60px;height:1px;background:var(--gold);margin:16px auto 28px}
.gold-divider.left{margin-left:0}
h2.section-title{font-family:var(--font-serif);font-size:clamp(2.2rem,4.5vw,3.3rem);font-weight:600;color:var(--dark);line-height:1.15;letter-spacing:-0.02em;position:relative;display:inline-block;transition:all 0.3s ease}

/* ===== GLOBAL HEADING ACCENT ===== */
.shine-heading {
  color: #2c1810 !important;
  font-weight: 600 !important;
  -webkit-text-fill-color: #2c1810 !important;
}
/* Crispy dark accent — applied to last 1-2 words in every heading */
em.accent, .accent-word {
  font-style: italic;
  color: #7a4a1e !important;
  -webkit-text-fill-color: #7a4a1e !important;
  font-weight: 700;
}
h2.section-title::after{content:'';position:absolute;bottom:-12px;left:0;width:0;height:3px;background:linear-gradient(90deg,var(--gold),transparent);transition:width 0.6s cubic-bezier(0.34,1.56,0.64,1);box-shadow:0 0 15px rgba(201,169,110,0.4)}
h2.section-title:hover::after{width:100%}
h2.section-title.white{color:#fff}
h2.section-title.white::after{background:linear-gradient(90deg,rgba(255,255,255,0.8),transparent)}
.btn{display:inline-block;padding:14px 36px;font-family:var(--font-sans);font-size:12px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;border:none;cursor:pointer;transition:all 0.4s cubic-bezier(0.23,1,0.320,1);border-radius:4px;position:relative;overflow:hidden}
.btn::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.3),transparent);transition:left 0.5s ease}
.btn:hover::before{left:100%}
.btn-gold{background:linear-gradient(135deg,var(--gold),var(--gold-dark));color:#fff;box-shadow:0 4px 15px rgba(201,169,110,0.3)}
.btn-gold:hover{background:linear-gradient(135deg,var(--gold-dark),var(--gold));transform:translateY(-2px);box-shadow:0 8px 25px rgba(201,169,110,0.5)}
.btn-outline{background:transparent;color:var(--gold);border:1px solid var(--gold)}
.btn-outline:hover{background:var(--gold);color:#fff}
.btn-outline-white{background:transparent;color:#fff;border:1px solid rgba(255,255,255,0.6)}
.btn-outline-white:hover{background:#fff;color:var(--dark)}
.hero-offer-btn{display:inline-block;background:linear-gradient(135deg,#a07840,#c9a96e,#e3c98f,#c9a96e,#a07840);background-size:300% auto;color:#fff !important;font-family:'Lato',sans-serif;font-size:13px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;padding:14px 36px;text-decoration:none;cursor:pointer;border-radius:6px;animation:green-shine 3s linear infinite;box-shadow:0 6px 22px rgba(176,124,62,0.4),0 0 0 1px rgba(201,169,110,0.35);}
.hero-offer-btn:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(176,124,62,0.5),0 0 0 1px rgba(201,169,110,0.5)}
.fade-up{opacity:1;transform:translateY(0);transition:opacity 0.7s ease,transform 0.7s ease}
.js-animate .fade-up{opacity:0;transform:translateY(30px)}
.fade-up.visible{opacity:1 !important;transform:translateY(0) !important}

/* ===== NAVBAR ===== */
#navbar{position:fixed;top:0;left:0;right:0;z-index:1000;padding:20px 40px;display:flex;align-items:center;justify-content:space-between;transition:all 0.4s;background:transparent;min-width:0}
#navbar.scrolled{background:var(--cream);box-shadow:0 2px 20px rgba(0,0,0,0.08);padding:14px 40px}
#navbar.scrolled .nav-logo{color:var(--dark)}
#navbar.scrolled .nav-links a{color:var(--dark)}
#navbar.scrolled .nav-links a:hover{color:var(--gold)}
#navbar.scrolled .nav-phone{color:var(--dark)}
.nav-logo{font-family:var(--font-serif);font-size:19px;font-weight:500;color:#fff;letter-spacing:0.03em;margin-right:16px;white-space:nowrap;flex-shrink:0;display:inline-flex;align-items:center;gap:7px;text-decoration:none}
.nav-lotus{flex-shrink:0;color:var(--gold);display:block;width:28px;height:23px;filter:drop-shadow(0 0 3px rgba(201,169,110,0.6))}
.nav-logo span{color:var(--gold)}
.nav-logo-text-sub{font-size:0.65em;opacity:0.85}
/* Desktop: show inline text, hide 2-line words block */
.nav-logo-words{display:none}
.nav-logo-desktop{color:inherit}
.nav-logo-desktop span{color:var(--gold)}
/* Mobile brand logo icon - matches actual brand logo */
.nav-brand-icon{display:none}
.nav-links{display:flex;gap:18px;flex:1;justify-content:center}
@media(max-width:1200px){
  .nav-links{display:none}
  .nav-phone{display:none}
  #navbar .hamburger{display:flex}
}
.nav-links a{font-size:12.5px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:rgba(255,255,255,0.9);transition:color 0.3s;white-space:nowrap}
.nav-links a:hover{color:var(--gold)}
.nav-right{display:flex;align-items:center;gap:16px;flex-shrink:0}
.nav-phone{font-size:13px;color:rgba(255,255,255,0.85);font-weight:400;white-space:nowrap}
#navbar.scrolled .nav-phone{color:var(--text-light)}
.hamburger{display:none;flex-direction:column;gap:5px;cursor:pointer;padding:4px}
.hamburger span{display:block;width:24px;height:2px;background:#fff;transition:all 0.3s}
#navbar.scrolled .hamburger span{background:var(--dark)}
.mobile-menu{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:var(--dark);z-index:99999;flex-direction:column;align-items:center;justify-content:center;gap:32px}
.mobile-menu.open{display:flex}
.mobile-menu a{font-family:var(--font-serif);font-size:28px;color:#fff;transition:color 0.3s}
.mobile-menu a:hover{color:var(--gold)}
.mobile-close{position:absolute;top:24px;right:32px;font-size:32px;color:#fff;cursor:pointer;background:none;border:none}

/* ===== HERO SLIDER ===== */
#hero{position:relative;width:100%;height:100vh;min-height:600px;overflow:hidden;background:#000;}
.slide{position:absolute !important;top:0 !important;left:0 !important;width:100% !important;height:100% !important;display:none !important;align-items:center;justify-content:center;background:transparent !important;}
.slide.active{display:flex !important;z-index:99 !important;background:transparent !important;}
.slide-bg{position:absolute;top:0;left:0;width:100%;height:100%;background-size:cover;background-position:center;overflow:hidden;}
.slide-overlay{position:absolute;inset:0}
.slide-content{position:relative;z-index:5;text-align:center;max-width:800px;padding:0 24px}
.slide-badge{display:inline-block;font-size:11px;font-weight:700;letter-spacing:0.3em;text-transform:uppercase;color:var(--gold);border:1px solid var(--gold);padding:6px 20px;margin-bottom:20px}
.slide-content h1,.slide-content h2.slide-h{font-family:var(--font-serif);font-size:clamp(2.5rem,6vw,4.5rem);font-weight:300;color:#fff;line-height:1.15;margin-bottom:20px}
.slide-content h1 em{font-style:italic;color:var(--gold-light)}
.hero-type-cursor{color:#c9a96e;font-style:normal;font-weight:300;animation:blink-cursor 0.75s step-end infinite}
.slide-1-type-cursor{color:var(--gold-light);font-style:normal;font-weight:300;animation:blink-cursor 0.75s step-end infinite}
.slide-content p{font-size:16px;color:rgba(255,255,255,0.8);max-width:560px;margin:0 auto 36px;line-height:1.7}
.slide-btns{display:flex;gap:16px;justify-content:center;flex-wrap:wrap}
.slide-stats{display:flex;gap:60px;justify-content:center;margin-top:40px;flex-wrap:wrap}
.slide-stat{text-align:center}
.slide-stat-num{font-family:var(--font-serif);font-size:2.5rem;color:var(--gold);font-weight:500}
.slide-stat-label{font-size:11px;letter-spacing:0.2em;text-transform:uppercase;color:rgba(255,255,255,0.6);margin-top:4px}
.hero-arrows{position:absolute;top:50%;transform:translateY(-50%);z-index:10;width:100%;display:flex;justify-content:space-between;padding:0 20px;pointer-events:none}
.hero-arrow{width:48px;height:48px;border:1px solid rgba(255,255,255,0.4);background:rgba(0,0,0,0.2);color:#fff;font-size:18px;display:flex;align-items:center;justify-content:center;cursor:pointer;pointer-events:all;transition:all 0.3s;border-radius:2px}
.hero-arrow:hover{background:var(--gold);border-color:var(--gold)}
.hero-dots{position:absolute;bottom:32px;left:50%;transform:translateX(-50%);z-index:10;display:flex;gap:10px}
.hero-dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,0.4);cursor:pointer;transition:all 0.3s}
.hero-dot.active{background:var(--gold);width:24px;border-radius:4px}

/* SHIMMER & SHINE TEXT EFFECTS */
.shimmer-text{background:linear-gradient(90deg,var(--dark) 0%,var(--gold) 40%,var(--dark) 60%,var(--dark) 100%);background-size:200% auto;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;animation:shimmer 3s linear infinite;}
@keyframes shimmer{0%{background-position:200% center}100%{background-position:-200% center}}

/* Premium shine effect */
.shine-text{position:relative;display:inline-block;}
.shine-text::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.4),transparent);animation:shine-sweep 3s infinite;}
@keyframes shine-sweep{0%{left:-100%}100%{left:100%}}

/* Premium glowing effect */
.glow-text{text-shadow:0 0 10px rgba(201,169,110,0.5),0 0 20px rgba(201,169,110,0.3);transition:text-shadow 0.3s ease;}
.glow-text:hover{text-shadow:0 0 15px rgba(201,169,110,0.8),0 0 30px rgba(201,169,110,0.5),0 0 40px rgba(201,169,110,0.3);}

/* Badge shimmer animation */
@keyframes badge-shimmer{0%{background-position:-200% center}100%{background-position:200% center}}
@keyframes badge-glow-pulse{0%,100%{box-shadow:0 0 12px rgba(201,169,110,0.35),inset 0 1px 0 rgba(255,255,255,0.3)}50%{box-shadow:0 0 20px rgba(201,169,110,0.55),inset 0 1px 0 rgba(255,255,255,0.5)}}
.sig-badge{animation:badge-glow-pulse 2.5s ease-in-out infinite}

/* Premium section titles with enhanced styling */
.premium-title{font-family:var(--font-serif);font-size:clamp(2.2rem,5vw,3.5rem);font-weight:600;color:#2c1810;line-height:1.1;letter-spacing:-0.02em;position:relative;display:inline-block;}
.premium-title::after{content:'';position:absolute;bottom:-8px;left:0;width:0;height:2px;background:linear-gradient(90deg,var(--gold),transparent);transition:width 0.6s ease;}
.premium-title:hover::after{width:100%;}

/* Premium card hover glow */
.premium-card{position:relative;transition:all 0.4s cubic-bezier(0.23,1,0.320,1);}
.premium-card::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 30% 30%,rgba(201,169,110,0.3),transparent);opacity:0;transition:opacity 0.4s ease;pointer-events:none;}
.premium-card:hover::before{opacity:1;}
.premium-card:hover{transform:translateY(-8px);box-shadow:0 20px 50px rgba(201,169,110,0.25);}

/* Enhanced typography */
.premium-text{font-size:1.05rem;line-height:1.9;letter-spacing:0.5px;color:var(--text-light);}
/* GALLERY LIGHTBOX */
.gallery-item{cursor:pointer;}
@keyframes pulse-dot{0%,100%{opacity:1}50%{opacity:0.3}}
/* ===== STATS BAR ===== */
#stats{background:var(--dark);padding:60px 40px}
.stats-grid{display:flex;justify-content:center;gap:0;max-width:900px;margin:0 auto;flex-wrap:wrap}
.stat-item{flex:1;min-width:180px;text-align:center;padding:20px 10px;position:relative}
.stat-item:not(:last-child)::after{content:'';position:absolute;right:0;top:50%;transform:translateY(-50%);height:50px;width:1px;background:rgba(201,169,110,0.3)}
.stat-num{font-family:var(--font-serif);font-size:3rem;color:var(--gold);font-weight:500;line-height:1}
.stat-label{font-size:11px;letter-spacing:0.2em;text-transform:uppercase;color:rgba(255,255,255,0.5);margin-top:8px}

@media(max-width:768px){
}

/* ===== GALLERY SECTION ===== */
#gallery{padding:100px 40px;background:#fff}

/* Gallery Popup Lightbox */
#galleryPopup{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.95);z-index:9999;display:none;align-items:center;justify-content:center;opacity:0;transition:opacity 0.4s ease}
#galleryPopup.open{display:flex;opacity:1}
#galleryPopupImg{max-width:90%;max-height:90%;object-fit:contain;border-radius:2px}

.gallery-popup-close{position:absolute;top:20px;right:30px;color:#fff;font-size:42px;line-height:1;cursor:pointer;font-weight:300;z-index:10000;transition:color 0.3s}
.gallery-popup-close:hover{color:#c9a96e}

.gallery-popup-nav{position:absolute;top:50%;width:100%;transform:translateY(-50%);display:flex;justify-content:space-between;padding:0 20px;pointer-events:none;z-index:9998}
.gallery-popup-btn{width:45px;height:45px;border-radius:50%;background:rgba(255,255,255,0.2);border:none;color:#fff;font-size:28px;cursor:pointer;pointer-events:all;display:flex;align-items:center;justify-content:center;transition:all 0.3s;font-weight:300}
.gallery-popup-btn:hover{background:rgba(201,169,110,1);color:#000}

.gallery-popup-counter{position:absolute;bottom:30px;left:50%;transform:translateX(-50%);color:rgba(255,255,255,0.7);font-size:14px;letter-spacing:0.1em;z-index:9998}

/* ===== SERVICES ===== */
#services{padding:100px 40px;background:#fff}
.section-header{text-align:center;margin-bottom:70px;animation:fadeInDown 0.8s ease-out}
@keyframes fadeInDown{from{opacity:0;transform:translateY(-30px)}to{opacity:1;transform:translateY(0)}}
/* ===== SERVICES GRID ===== */
.services-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:28px;max-width:1100px;margin:0 auto;align-items:stretch}

/* Card shell — flex column so footer always aligns to bottom */
.service-card{border-radius:18px;overflow:hidden;box-shadow:0 2px 18px rgba(0,0,0,0.07);transition:all 0.4s cubic-bezier(0.23,1,0.320,1);background:#fff;border:1.5px solid rgba(201,169,110,0.12);padding:0;position:relative;display:flex;flex-direction:column}
.service-card::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 30% 20%,rgba(201,169,110,0.18),transparent);opacity:0;transition:opacity 0.4s ease;pointer-events:none;z-index:0}
.service-card:hover{transform:translateY(-8px);box-shadow:0 24px 56px rgba(201,169,110,0.22);border-color:rgba(201,169,110,0.45)}
.service-card:hover::before{opacity:1}

/* Coloured top accent strip */
.service-top-accent{height:3px;background:linear-gradient(90deg,var(--cat-color,var(--gold)),var(--gold-light));flex-shrink:0}

/* Body — flex column fills remaining card height */
.service-body{padding:28px 28px 26px;position:relative;z-index:1;display:flex;flex-direction:column;flex:1}

/* Icon badge */
.service-icon-wrap{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:18px;background:rgba(201,169,110,0.07);border:1.5px solid rgba(201,169,110,0.18);color:var(--gold);transition:all 0.35s cubic-bezier(0.23,1,0.32,1);flex-shrink:0}
.service-card:hover .service-icon-wrap{background:var(--gold);color:#fff;border-color:var(--gold);transform:rotate(-8deg) scale(1.1);box-shadow:0 4px 18px rgba(201,169,110,0.38)}

.service-cat{font-size:10px;letter-spacing:0.38em;text-transform:uppercase;color:var(--gold);margin-bottom:10px;font-weight:700;opacity:0.85;transition:all 0.3s ease}
.service-card:hover .service-cat{opacity:1;letter-spacing:0.46em}
.service-body h3{font-family:var(--font-serif);font-size:1.55rem;margin-bottom:14px;color:var(--dark);font-weight:500;line-height:1.28;letter-spacing:-0.01em;transition:color 0.3s ease}
.service-card:hover .service-body h3{color:#7a4a1e}

/* Description flexes to fill space → price row always at bottom */
.service-body p{font-size:14.5px;color:var(--text-light);line-height:1.85;letter-spacing:0.25px;flex:1;padding-bottom:20px}

/* Footer row */
.service-meta{display:flex;justify-content:space-between;align-items:center;padding-top:18px;border-top:1.5px solid #ede7db;gap:12px;margin-top:auto}
.service-duration{font-size:11.5px;color:var(--text-light);font-weight:600;letter-spacing:0.05em;transition:color 0.3s ease}
.service-card:hover .service-duration{color:var(--gold)}
.service-price{font-family:var(--font-serif);font-size:1.35rem;color:var(--gold);font-weight:600;transition:all 0.3s ease}
.service-card:hover .service-price{text-shadow:0 0 14px rgba(201,169,110,0.35)}
/* ===== CART ===== */
.cart-nav-btn{background:none;border:none;cursor:pointer;position:relative;display:flex;align-items:center;gap:6px;padding:6px 8px;color:rgba(255,255,255,0.9);transition:color 0.3s;font-family:var(--font-sans);font-size:12px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;}
#navbar.scrolled .cart-nav-btn{color:var(--dark);}
.cart-nav-btn:hover{color:var(--gold);}
.cart-badge{background:var(--gold);color:#fff;font-size:9px;font-weight:700;width:17px;height:17px;border-radius:50%;display:none;align-items:center;justify-content:center;line-height:1;flex-shrink:0;}
.cart-badge.visible{display:flex;animation:badge-pop 0.4s cubic-bezier(0.34,1.56,0.64,1);}
@keyframes badge-pop{0%{transform:scale(0)}100%{transform:scale(1)}}
.service-book-btn{display:block;width:100%;margin-top:18px;padding:11px 16px;background:transparent;border:1.5px solid rgba(201,169,110,0.4);color:var(--gold);font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;cursor:pointer;border-radius:6px;transition:all 0.3s cubic-bezier(0.23,1,0.32,1);font-family:var(--font-sans);}
.service-book-btn:hover{background:var(--gold);color:#fff;border-color:var(--gold);transform:translateY(-2px);box-shadow:0 6px 20px rgba(201,169,110,0.3);}
.service-book-btn.selected{background:linear-gradient(135deg,var(--gold),var(--gold-dark));color:#fff;border-color:var(--gold-dark);}
.cart-toast{position:fixed;bottom:110px;left:50%;transform:translateX(-50%) translateY(16px);background:var(--dark);color:#fff;padding:13px 26px;border-radius:30px;font-size:13px;font-weight:600;letter-spacing:0.04em;opacity:0;transition:all 0.4s cubic-bezier(0.34,1.56,0.64,1);z-index:99998;border:1px solid rgba(201,169,110,0.35);white-space:nowrap;pointer-events:none;}
.cart-toast.show{opacity:1;transform:translateX(-50%) translateY(0);}

/* ===== OFFERS ===== */
#offers{background:var(--dark2);padding:100px 40px;display:none}
.offers-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;max-width:1100px;margin:0 auto}
.offer-card{background:rgba(255,255,255,0.04);border:1px solid rgba(201,169,110,0.2);border-radius:4px;padding:32px;position:relative;transition:border-color 0.3s;display:flex;flex-direction:column}
.offer-card:hover{border-color:var(--gold)}
.offer-card .offer-body{flex:1}
.offer-badge{display:inline-block;background:var(--gold);color:#fff;font-size:11px;font-weight:700;letter-spacing:0.1em;padding:5px 14px;border-radius:2px;margin-bottom:20px}
.offer-card h3{font-family:var(--font-serif);font-size:1.6rem;color:#fff;margin-bottom:12px}
.offer-card p{font-size:13px;color:rgba(255,255,255,0.6);line-height:1.7;margin-bottom:8px}
.offer-code{font-size:12px;letter-spacing:0.15em;color:var(--gold);background:rgba(201,169,110,0.1);padding:6px 14px;display:inline-block;margin:12px 0;border-radius:2px}
.offer-price{margin:12px 0}
.offer-price .original{font-size:14px;color:rgba(255,255,255,0.4);text-decoration:line-through;margin-right:10px}
.offer-price .sale{font-family:var(--font-serif);font-size:1.8rem;color:var(--gold)}
.countdown{display:flex;gap:12px;margin:16px 0;flex-wrap:wrap}
.countdown-unit{text-align:center;background:rgba(201,169,110,0.1);border:1px solid rgba(201,169,110,0.2);padding:8px 12px;border-radius:2px;min-width:52px}
.countdown-num{font-family:var(--font-serif);font-size:1.6rem;color:var(--gold);line-height:1;display:block}
.countdown-label{font-size:9px;letter-spacing:0.15em;text-transform:uppercase;color:rgba(255,255,255,0.4);display:block;margin-top:2px}

/* ===== PACKAGES TABLE — PREMIUM ===== */
#packages{padding:100px 40px;background:linear-gradient(160deg,#f5f0e8 0%,var(--cream) 50%,#ede7db 100%)}

/* Wrapper */
.pkg-table-wrap{max-width:1080px;margin:0 auto;overflow-x:auto;border-radius:20px;box-shadow:0 20px 64px rgba(0,0,0,0.14),0 0 0 1.5px rgba(201,169,110,0.25);-webkit-overflow-scrolling:touch}

/* Table */
.pkg-table{width:100%;min-width:560px;border-collapse:collapse;font-family:var(--font-sans);background:#fff}

/* ── HEADER ── */
.pkg-table thead tr{background:linear-gradient(135deg,#0d1b14 0%,#1e3626 55%,#0d1b14 100%)}
.pkg-table thead th{
  padding:22px 28px;
  font-family:var(--font-sans);
  font-size:11px;
  font-weight:700;
  letter-spacing:0.18em;
  text-transform:uppercase;
  white-space:nowrap;
  border-bottom:2px solid rgba(201,169,110,0.3);
}
.pkg-table thead th:nth-child(1){text-align:left;width:19%;color:rgba(255,255,255,0.55)}
.pkg-table thead th:nth-child(2){text-align:left;width:44%;color:#fff}
.pkg-table thead th:nth-child(3){text-align:center;width:18%;color:rgba(201,169,110,0.9)}
.pkg-table thead th:nth-child(4){
  text-align:center;width:19%;
  color:#fff;
  background:rgba(201,169,110,0.18);
  border-left:1px solid rgba(201,169,110,0.3);
}

/* ── ROW COUNTER ── */
.pkg-table tbody{counter-reset:pkg-row}
.pkg-table tbody tr{counter-increment:pkg-row}

/* ── ROWS ── */
.pkg-table tbody tr{border-bottom:1px solid rgba(201,169,110,0.12);transition:all 0.3s cubic-bezier(0.23,1,0.32,1)}
.pkg-table tbody tr:nth-child(odd){background:#ffffff}
.pkg-table tbody tr:nth-child(even){background:#fdfaf6}
.pkg-table tbody tr:hover{background:linear-gradient(90deg,rgba(201,169,110,0.07) 0%,rgba(201,169,110,0.03) 100%);box-shadow:inset 5px 0 0 var(--gold)}
.pkg-table tbody tr:last-child{border-bottom:none}
.pkg-table td{padding:20px 28px;vertical-align:middle}

/* Price column always has subtle tint */
.pkg-table tbody td:last-child{background:rgba(201,169,110,0.04);border-left:1px solid rgba(201,169,110,0.1)}
.pkg-table tbody tr:hover td:last-child{background:rgba(201,169,110,0.1)}

/* ── PACKAGE NAME ── */
.pkg-table td:first-child::before{
  content:counter(pkg-row,decimal-leading-zero);
  display:block;font-size:10px;font-weight:700;
  letter-spacing:0.14em;color:rgba(201,169,110,0.5);
  margin-bottom:4px;font-family:var(--font-sans)
}
.pkg-name{font-family:var(--font-serif);font-size:1.12rem;font-weight:600;color:var(--dark);line-height:1.3;display:block}

/* ── SERVICES ── */
.pkg-services{list-style:none;padding:0;margin:0}
.pkg-services li{font-size:13.5px;color:var(--text-light);line-height:1.85;padding:0 0 0 20px;position:relative}
.pkg-services li::before{content:'—';position:absolute;left:0;color:var(--gold);font-size:12px;line-height:1.85;font-weight:700}

/* ── DURATION ── */
.pkg-duration{text-align:center}
.pkg-dur-main{display:none}
.pkg-dur-sub{display:inline-block;font-size:11.5px;color:var(--text-light);background:rgba(201,169,110,0.1);border:1px solid rgba(201,169,110,0.25);padding:3px 14px;border-radius:30px;letter-spacing:0.04em}

/* ── PRICE ── */
.pkg-price-cell{text-align:center;padding:0}
.pkg-price{display:block;font-family:var(--font-serif);font-size:1.5rem;color:var(--gold);font-weight:600;white-space:nowrap;line-height:1.2}
.pkg-price-label{display:none}

/* ── FOOTER NOTE ── */
.pkg-footer-note{max-width:1080px;margin:22px auto 0;padding:15px 26px;background:rgba(201,169,110,0.07);border-left:3px solid var(--gold);border-radius:0 12px 12px 0;font-size:13px;color:var(--text-light);line-height:1.8}

/* ===== SIGNATURE TREATMENTS TABLE ===== */
#signature{padding:100px 40px 60px;background:#fff;position:relative;overflow:hidden}
#signature::before{content:'';position:absolute;top:-200px;left:50%;transform:translateX(-50%);width:800px;height:800px;background:radial-gradient(circle,rgba(201,169,110,0.05) 0%,transparent 65%);pointer-events:none}
#signature::after{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--gold),transparent)}

.sig-intro{max-width:720px;margin:0 auto 52px;text-align:center;font-size:15px;color:var(--text-light);line-height:1.95;font-style:italic;position:relative;z-index:1}

/* Table wrapper */
.sig-table-wrap{max-width:920px;margin:0 auto;overflow-x:auto;border-radius:20px;box-shadow:0 8px 40px rgba(0,0,0,0.1),0 0 0 1.5px rgba(201,169,110,0.25);position:relative;z-index:1}
.sig-table{width:100%;border-collapse:collapse;font-family:var(--font-sans)}

/* Header */
.sig-table thead tr{background:linear-gradient(135deg,#0d1b14 0%,#1e3626 55%,#0d1b14 100%);border-bottom:2px solid rgba(201,169,110,0.3)}
.sig-table thead th{padding:20px 26px;font-size:10px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;white-space:nowrap}
.sig-table thead th:first-child{text-align:left;color:rgba(255,255,255,0.55);width:40%}
.sig-table thead th:not(:first-child){text-align:center;width:15%}
.sig-table thead th .th-num{display:block;font-family:var(--font-serif);font-size:1.8rem;font-weight:300;color:#fff;letter-spacing:0;margin-bottom:2px;line-height:1}
.sig-table thead th .th-label{color:var(--gold);font-size:10px;letter-spacing:0.2em}

/* Row counter */
.sig-table tbody{counter-reset:sig-row}
.sig-table tbody tr{counter-increment:sig-row}

/* Rows */
.sig-table tbody tr{border-bottom:1px solid rgba(201,169,110,0.12);transition:all 0.3s cubic-bezier(0.23,1,0.32,1)}
.sig-table tbody tr:nth-child(odd){background:#ffffff}
.sig-table tbody tr:nth-child(even){background:#fdfaf6}
.sig-table tbody tr:hover{background:rgba(201,169,110,0.07);box-shadow:inset 5px 0 0 var(--gold)}
.sig-table tbody tr:last-child{border-bottom:none}
.sig-table td{padding:20px 26px;vertical-align:middle}

/* Price cols — permanent subtle tint */
.sig-table td:not(:first-child){text-align:center;background:rgba(201,169,110,0.04);border-left:1px solid rgba(201,169,110,0.1)}
.sig-table tbody tr:hover td:not(:first-child){background:rgba(201,169,110,0.1)}

/* Service name cell — row-numbered */
.sig-table td:first-child::before{content:counter(sig-row,decimal-leading-zero);display:block;font-size:9.5px;font-weight:700;letter-spacing:0.15em;color:rgba(201,169,110,0.4);margin-bottom:4px;font-family:var(--font-sans)}
.sig-name-wrap{display:flex;flex-direction:column;gap:4px}
.sig-service{font-family:var(--font-serif);font-size:1.06rem;font-weight:500;color:#1a1a1a;line-height:1.3}
.sig-desc{font-size:12px;color:var(--text-light);line-height:1.4;letter-spacing:0.02em}
.sig-badge{display:inline-flex;align-items:center;gap:5px;font-size:9px;font-weight:700;letter-spacing:0.13em;text-transform:uppercase;padding:5px 13px;border-radius:20px;margin-top:6px;width:fit-content;position:relative;transition:all 0.25s ease}
.sig-badge.popular{background:linear-gradient(135deg,#c9a96e,#a07840);color:#fff;border:none;box-shadow:0 3px 10px rgba(160,120,64,0.35)}
.sig-badge.popular:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(160,120,64,0.45)}
.sig-badge.exclusive{background:linear-gradient(135deg,#0d1b14,#1e3626);color:var(--gold);border:1px solid rgba(201,169,110,0.4);box-shadow:0 3px 10px rgba(13,27,20,0.2)}
.sig-badge.exclusive:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(13,27,20,0.3)}
.sig-badge.new{background:linear-gradient(135deg,#1a5c3a,#2d7a4f);color:#d4f5e4;border:none;box-shadow:0 3px 10px rgba(26,92,58,0.3)}
@media(max-width:768px){
  /* Compact table — same layout as desktop, sized to fit the screen */
  .sig-table-wrap{border-radius:12px}
  .sig-table{table-layout:fixed;width:100%}
  .sig-table thead th{padding:10px 3px;font-size:7.5px;letter-spacing:0.08em}
  .sig-table thead th:first-child{width:32%;padding-left:8px}
  .sig-table thead th:not(:first-child){width:14%}
  .sig-table thead th:last-child{width:60px !important}
  .sig-table thead th .th-num{font-size:1rem;margin-bottom:1px}
  .sig-table thead th .th-label{font-size:7px;letter-spacing:0.08em}
  .sig-table td{padding:10px 3px !important}
  .sig-table td:first-child{padding-left:8px !important}
  .sig-table td:first-child::before{font-size:8px;margin-bottom:2px}
  .sig-service{font-size:11px;line-height:1.3}
  .sig-desc{display:none}
  .sig-badge{font-size:6.5px;padding:3px 7px;margin-top:4px;gap:3px}
  .sig-table td:not(:first-child){font-size:11px}
  .sig-table .sig-price{font-size:11.5px}
  .sig-table .price-book-btn{padding:5px 3px;font-size:7.5px;letter-spacing:0.02em;border-radius:5px;max-width:100%}
}
.sig-badge.new:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(26,92,58,0.4)}

/* Prices */
.sig-price{font-family:var(--font-serif);font-size:1.2rem;font-weight:600;color:var(--gold);white-space:nowrap;display:block}
.sig-dash{font-size:20px;color:rgba(255,255,255,0.18);font-weight:300;display:block}

/* Book CTA */
.sig-book-wrap{text-align:center;margin-top:48px;position:relative;z-index:1}

/* ===== ABOUT ===== */
#about{padding:60px 40px;background:#fff}
.about-inner{display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:center;max-width:1100px;margin:0 auto}
.about-text .section-label{display:block}
.about-text h2{font-family:var(--font-serif);font-size:clamp(1.8rem,3.5vw,2.8rem);margin-bottom:20px;font-weight:600;color:#2c1810;}
.about-text p{font-size:15px;color:var(--text-light);line-height:1.9;margin-bottom:16px}
.about-list{margin:20px 0}
.about-list li{font-size:14px;color:var(--text-light);padding:8px 0;padding-left:20px;position:relative;border-bottom:1px solid #f0ebe3}
.about-list li::before{content:'—';position:absolute;left:0;color:var(--gold)}
.about-img{position:relative}
.about-img img{width:100%;height:500px;object-fit:cover;border-radius:2px}
.about-img-badge{position:absolute;bottom:-20px;left:-20px;background:var(--gold);color:#fff;padding:24px;text-align:center;border-radius:2px}
.about-img-badge .num{font-family:var(--font-serif);font-size:2.2rem;display:block;line-height:1}
.about-img-badge .lbl{font-size:10px;letter-spacing:0.2em;text-transform:uppercase;display:block;margin-top:4px}

/* ===== PRICING ===== */
#pricing{padding:100px 40px;background:var(--cream2)}
.tabs{display:flex;flex-wrap:wrap;gap:4px;justify-content:center;margin-bottom:40px}
.tab-btn{padding:13px 28px;font-family:var(--font-sans);font-size:11px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;background:#fff;border:2px solid #ddd6c8;color:#999;cursor:pointer;transition:all 0.4s cubic-bezier(0.23,1,0.320,1);border-radius:8px;position:relative;overflow:hidden}
.tab-btn::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(201,169,110,0.2),transparent);transition:left 0.6s ease}
.tab-btn:hover::before{left:100%}
.tab-btn.active{background:linear-gradient(135deg,#1a1a1a,#2a2a2a);color:var(--gold);border-color:var(--gold);box-shadow:0 6px 20px rgba(201,169,110,0.4),inset 0 0 20px rgba(201,169,110,0.1)}
.tab-btn:hover:not(.active){border-color:var(--gold);color:var(--gold);box-shadow:0 4px 12px rgba(201,169,110,0.2);transform:translateY(-2px)}
.tab-panel{display:none;max-width:800px;margin:0 auto}
.tab-panel.active{display:block}

/* ===== ACCORDION SYSTEM ===== */
.accordion-container{max-width:1000px;margin:0 auto}
.accordion-item{margin-bottom:16px;border-radius:8px;overflow:hidden;border:1.5px solid rgba(201,169,110,0.2);background:#fff}
.accordion-header{padding:20px 28px;background:linear-gradient(135deg,#7a6b4f 0%,#8b7c5c 100%);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:space-between;transition:all 0.3s ease;font-weight:600;font-size:15px;letter-spacing:0.05em}
.accordion-header:hover{background:linear-gradient(135deg,#6b5c40 0%,#7c6d4d 100%);padding-left:32px}
.accordion-icon{font-size:11px;color:#2196F3;margin-right:14px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;display:flex;align-items:center;gap:8px}
.accordion-icon::after{content:'▼';transition:transform 0.3s ease;display:inline-block}
.accordion-header.active .accordion-icon::after{transform:rotate(-180deg)}
.accordion-content{display:none;max-height:0;overflow:hidden;transition:max-height 0.4s ease;background:#fff}
.accordion-content.active{display:block;max-height:5000px;padding:24px 28px}
.accordion-content p{font-size:14px;color:var(--text-light);margin-bottom:20px;font-style:italic;line-height:1.8}
.accordion-content .price-table{margin-top:16px}
/* Hide old accordion system */
.accordion-item{display:none !important}
.accordion-container{display:none !important}

/* Horizontal Tab Navigation */
.pricing-tabs-nav{display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap;padding:0;justify-content:center}
.pricing-tab-btn{padding:12px 20px;font-family:var(--font-sans);font-size:12px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;background:#fff;border:1.5px solid rgba(201,169,110,0.25);color:var(--text-light);cursor:pointer;transition:all 0.3s ease;border-radius:6px}
.pricing-tab-btn:hover{border-color:var(--gold);color:var(--gold);box-shadow:0 4px 12px rgba(201,169,110,0.15)}
.pricing-tab-btn.active{background:linear-gradient(135deg,#7a6b4f 0%,#8b7c5c 100%);color:#fff;border-color:#7a6b4f;box-shadow:0 6px 18px rgba(201,169,110,0.25)}

/* Tab Content Display */
.pricing-tab-content{display:none}
.pricing-tab-content.active{display:block;animation:fadeIn 0.3s ease}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}

/* Excel-Style Table */
.accordion-content-pricing{width:100%;display:table;border-collapse:collapse;border:1.5px solid rgba(201,169,110,0.2);border-radius:8px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.06)}
.price-row{display:table-row;border-bottom:1px solid rgba(201,169,110,0.15)}
.price-row:last-child{border-bottom:none}
.price-row:nth-child(even){background:#f9f6f0}
.price-row:nth-child(odd){background:#fff}
.price-row:hover{background:rgba(201,169,110,0.08)}
.price-row-name,.price-col,.price-book-col{display:table-cell;padding:16px 20px;vertical-align:middle}
.price-row-name{text-align:left;font-family:var(--font-serif);font-size:15px;font-weight:600;color:#1a1a1a;width:32%;padding-left:24px}
.price-col{text-align:center;font-family:var(--font-serif);font-size:16px;font-weight:600;color:var(--gold);width:16%}
.price-col-label{display:block;font-family:var(--font-sans);font-size:10px;font-weight:700;color:var(--text-light);letter-spacing:0.08em;text-transform:uppercase;margin-bottom:3px}
.price-col-value::before{content:'฿';font-size:14px;margin-right:2px}
.price-book-col{text-align:center;width:20%;padding:12px 16px}
.price-book-btn{display:inline-block;padding:8px 18px;font-family:var(--font-sans);font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;background:transparent;border:1.5px solid rgba(201,169,110,0.45);color:var(--gold);cursor:pointer;border-radius:4px;transition:all 0.25s ease;white-space:nowrap}
.price-book-btn:hover{background:var(--gold);color:#fff;border-color:var(--gold);box-shadow:0 4px 14px rgba(201,169,110,0.35);transform:translateY(-1px)}

/* Hide price table and accordion content text */
.accordion-content{display:block !important;padding:0 !important}
.accordion-content p{display:none !important}
.accordion-content .price-table{display:none !important}

@media(max-width:768px){
  /* Expand Book button tap area invisibly — small visual button, finger-sized target */
  .price-book-btn{position:relative}
  .price-book-btn::after{content:'';position:absolute;top:-14px;bottom:-14px;left:-10px;right:-10px}
  .pricing-tabs-nav{gap:4px}
  .pricing-tab-btn{padding:10px 14px;font-size:11px}
  .price-row-name{padding:10px 10px;font-size:13px;width:28%}
  .price-col{padding:10px 6px;font-size:13px;width:18%}
  .price-book-col{padding:8px 6px;width:18%}
  .price-book-btn{padding:6px 10px;font-size:10px;letter-spacing:0.08em}
}
@media(max-width:768px){
  /* Compact table — same layout as desktop, sized to fit the screen */
  .accordion-content-pricing{table-layout:fixed;width:100%}
  .price-row-name{padding:10px 4px 10px 8px;font-size:11.5px;width:31%;line-height:1.35}
  .price-col{padding:10px 2px;font-size:11px;width:16%}
  .price-col::before{content:none}
  .price-col-label{display:block;font-size:8px;font-weight:700;color:var(--text-light);letter-spacing:0.04em;text-transform:uppercase;margin-bottom:2px}
  .price-col-value{color:var(--gold);font-weight:700;font-size:12px}
  .price-col-value::before{content:'฿';font-size:10px;margin-right:1px}
  .price-book-col{padding:8px 4px;width:21%}
  .price-book-btn{padding:6px 8px;font-size:9px;letter-spacing:0.05em;border-radius:5px}
}
/* Mobile price cards — shown only on mobile via JS class */
.price-card-list{display:none}
.price-card{background:#fff;border:1px solid #e8e2d8;border-radius:6px;padding:14px 16px;margin-bottom:10px}
.price-card-name{font-size:14px;font-weight:700;color:var(--dark);margin-bottom:10px}
.price-card-durations{display:flex;gap:8px;flex-wrap:wrap}
.price-card-dur{display:flex;flex-direction:column;align-items:center;gap:4px}
.price-card-min{font-size:10px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:var(--text-light)}
/* ===== PRICE TABLE ===== */
.price-table{width:100%;border-collapse:separate;border-spacing:0;border-radius:12px;overflow:hidden;box-shadow:0 4px 30px rgba(0,0,0,0.08),0 0 0 1px rgba(201,169,110,0.2)}
.price-table thead tr{background:#1a1a1a}
.price-table th{padding:20px 28px;font-family:var(--font-sans);font-size:11px;letter-spacing:0.2em;text-transform:uppercase;font-weight:700;border-bottom:2px solid var(--gold)}
.price-table th:first-child{text-align:left;color:rgba(255,255,255,0.55);width:44%}
.price-table th:not(:first-child){text-align:center;color:var(--gold)}
.price-table th:not(:first-child) span{display:block;font-size:22px;font-family:var(--font-serif);font-weight:300;letter-spacing:0.02em;color:#fff;margin-bottom:3px;text-transform:none}
/* Rows */
.price-table tbody tr:nth-child(odd){background:#fff}
.price-table tbody tr:nth-child(even){background:#f9f6f0}
/* Cells */
.price-table td{padding:22px 28px;border-bottom:1px solid #ede7db;font-family:var(--font-sans);font-size:15px;color:#1a1a1a;font-weight:400}
.price-table td:first-child{font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:600;color:#111;letter-spacing:0.02em;line-height:1.3}
.price-table td:not(:first-child){text-align:center;font-family:var(--font-sans);font-size:15px;font-weight:500;color:#2a2a2a}
.price-table tr:last-child td{border-bottom:none}
/* Price btn in table */
.price-table td .price-btn{font-size:13px !important;padding:9px 22px !important;border-radius:30px !important}
.price-table td.price-dash,.price-dash{color:#c8bfb0 !important;font-size:18px !important;font-family:var(--font-sans) !important}
/* Tab panel */
.tab-panel{position:relative}
.price-dash{color:#ccc !important;font-family:var(--font-sans) !important;font-size:13px !important}
/* Green price button */
.price-btn{display:inline-block;background:linear-gradient(135deg,#1b5e20,#2e7d32,#43a047,#66bb6a,#43a047,#2e7d32);background-size:300% auto;color:#fff !important;font-size:12px !important;font-weight:700;font-family:var(--font-sans) !important;padding:5px 14px;border-radius:20px;text-decoration:none;cursor:pointer;animation:green-shine 3s linear infinite;box-shadow:0 2px 10px rgba(46,125,50,0.45);transition:transform 0.2s,box-shadow 0.2s;white-space:nowrap;letter-spacing:0.03em}
.price-btn:hover{transform:scale(1.08);box-shadow:0 4px 18px rgba(46,125,50,0.7)}
@keyframes green-shine{0%{background-position:0% center}100%{background-position:300% center}}

/* ===== GALLERY ===== */
/* 12 photos → 4 cols × 3 rows — all equal size, equal gaps */
.gallery-grid{display:grid;grid-template-columns:repeat(4,1fr);grid-auto-rows:1fr;gap:16px;max-width:1100px;margin:40px auto 0}
.gallery-item{position:relative;overflow:hidden;border-radius:10px;cursor:pointer;aspect-ratio:1}
.gallery-item img{width:100%;height:100%;object-fit:cover;display:block;transition:transform 0.5s ease}
.gallery-item:hover img{transform:scale(1.07)}
.gallery-overlay{position:absolute;inset:0;background:rgba(13,27,20,0.55);opacity:0;transition:opacity 0.3s;display:flex;align-items:center;justify-content:center}
.gallery-item:hover .gallery-overlay{opacity:1}
.gallery-overlay svg{width:32px;height:32px;fill:none;stroke:#fff;stroke-width:2}

/* ===== TESTIMONIALS ===== */
#testimonials{padding:100px 40px;background:var(--dark)}
.testi-slider{max-width:800px;margin:0 auto;position:relative;overflow:hidden}
.testi-track{display:flex;transition:transform 0.6s ease}
.testi-slide{min-width:100%;padding:0 20px;text-align:center}
.testi-quote{font-size:80px;color:var(--gold);opacity:0.3;line-height:0.5;margin-bottom:30px;font-family:Georgia,serif}
.testi-text{font-family:var(--font-serif);font-size:1.3rem;color:rgba(255,255,255,0.9);line-height:1.8;font-style:italic;margin-bottom:30px}
.testi-author{font-size:12px;letter-spacing:0.2em;text-transform:uppercase;color:var(--gold)}
.testi-controls{display:flex;justify-content:center;gap:10px;margin-top:40px}
.testi-dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,0.25);cursor:pointer;transition:all 0.3s;border:none}
.testi-dot.active{background:var(--gold);width:24px;border-radius:4px}
.testi-arrows{display:flex;justify-content:center;gap:12px;margin-top:20px}
.testi-arrow{width:40px;height:40px;border:1px solid rgba(201,169,110,0.4);background:transparent;color:var(--gold);font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.3s;border-radius:2px}
.testi-arrow:hover{background:var(--gold);color:#fff}

/* ===== BOOKING ===== */
#booking{padding:100px 40px;background:#fff}
.booking-inner{display:grid;grid-template-columns:1fr 1.5fr;gap:0;max-width:1100px;margin:0 auto;border-radius:4px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.1)}
.booking-left{background:var(--dark);padding:60px 48px;display:flex;flex-direction:column;justify-content:center}
.booking-left .section-label{margin-bottom:8px}
.booking-left h2{font-family:var(--font-serif);font-size:2.2rem;color:#fff;font-weight:300;margin-bottom:20px}
.booking-left p{font-size:14px;color:rgba(255,255,255,0.6);line-height:1.8;margin-bottom:32px}
.booking-contact-item{display:flex;align-items:flex-start;gap:16px;margin-bottom:20px}
.booking-contact-icon{width:36px;height:36px;border:1px solid rgba(201,169,110,0.3);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.booking-contact-icon svg{width:16px;height:16px;fill:var(--gold)}
.booking-contact-text{font-size:13px;color:rgba(255,255,255,0.7);line-height:1.7}
.booking-confirm{background:rgba(201,169,110,0.1);border:1px solid rgba(201,169,110,0.2);padding:16px 20px;border-radius:2px;margin-top:24px}
.booking-confirm p{font-size:13px;color:rgba(255,255,255,0.7);margin:0}
.booking-confirm strong{color:var(--gold)}
.booking-right{background:#fff;padding:60px 48px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}
.form-group{margin-bottom:20px}
.form-group label{display:block;font-size:11px;letter-spacing:0.15em;text-transform:uppercase;color:var(--text-light);margin-bottom:8px;font-weight:700}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:12px 16px;border:1px solid #e0d9ce;border-radius:2px;font-family:var(--font-sans);font-size:14px;color:var(--text);background:#fff;transition:border-color 0.3s;outline:none}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--gold)}
.form-group textarea{resize:vertical;min-height:90px}
.form-btns{display:flex;gap:12px;flex-wrap:wrap}
.form-btns .btn{white-space:nowrap;min-width:0;flex:1;text-align:center;padding:14px 16px;font-size:11px;letter-spacing:0.1em}

/* ===== TRUST BADGES ===== */
/* ===== TRUST BADGES — PREMIUM REDESIGN ===== */
#trust{
  padding:0;
  background:linear-gradient(135deg,#0a1810 0%,#0f211a 50%,#0a1810 100%);
  position:relative;
  overflow:hidden;
  border-top:1px solid rgba(201,169,110,0.15);
  border-bottom:1px solid rgba(201,169,110,0.15);
}
#trust::before{
  content:'';position:absolute;top:50%;left:50%;
  transform:translate(-50%,-50%);
  width:70%;height:100%;
  background:radial-gradient(ellipse at center,rgba(201,169,110,0.07) 0%,transparent 70%);
  pointer-events:none;
}
#trust::after{
  content:'';position:absolute;top:0;left:0;right:0;height:1px;
  background:linear-gradient(90deg,transparent,rgba(201,169,110,0.5),rgba(201,169,110,0.8),rgba(201,169,110,0.5),transparent);
}
.trust-inner{
  display:flex;justify-content:stretch;gap:0;
  max-width:1200px;margin:0 auto;flex-wrap:wrap;
}
.trust-badge{
  flex:1;min-width:180px;text-align:center;
  padding:44px 24px;position:relative;
  transition:all 0.4s cubic-bezier(0.23,1,0.32,1);
  cursor:default;
}
.trust-badge:not(:last-child)::after{
  content:'';position:absolute;right:0;top:20%;
  height:60%;width:1px;
  background:linear-gradient(180deg,transparent,rgba(201,169,110,0.3),transparent);
}
.trust-badge:hover{background:rgba(201,169,110,0.04)}
.trust-icon-wrap{
  width:68px;height:68px;margin:0 auto 20px;
  border-radius:50%;
  background:radial-gradient(circle,rgba(201,169,110,0.18) 0%,rgba(201,169,110,0.05) 65%,transparent 100%);
  border:1.5px solid rgba(201,169,110,0.4);
  display:flex;align-items:center;justify-content:center;
  transition:all 0.4s cubic-bezier(0.23,1,0.32,1);
  animation:trust-icon-pulse 3.5s ease-in-out infinite;
}
@keyframes trust-icon-pulse{
  0%,100%{box-shadow:0 0 0 0 rgba(201,169,110,0.15),0 0 12px rgba(201,169,110,0.1)}
  50%{box-shadow:0 0 0 6px rgba(201,169,110,0.06),0 0 24px rgba(201,169,110,0.2)}
}
.trust-badge:nth-child(2) .trust-icon-wrap{animation-delay:0.7s}
.trust-badge:nth-child(3) .trust-icon-wrap{animation-delay:1.4s}
.trust-badge:nth-child(4) .trust-icon-wrap{animation-delay:2.1s}
.trust-badge:nth-child(5) .trust-icon-wrap{animation-delay:2.8s}
.trust-badge:hover .trust-icon-wrap{
  background:radial-gradient(circle,rgba(201,169,110,0.32) 0%,rgba(201,169,110,0.1) 65%,transparent 100%);
  border-color:rgba(201,169,110,0.75);
  box-shadow:0 0 28px rgba(201,169,110,0.3),0 0 60px rgba(201,169,110,0.1);
  transform:translateY(-3px) scale(1.06);
  animation:none;
}
.trust-badge svg{
  width:28px;height:28px;
  stroke:var(--gold);fill:none;stroke-width:1.5;
  transition:all 0.4s ease;
  filter:drop-shadow(0 0 4px rgba(201,169,110,0.3));
}
.trust-badge:hover svg{filter:drop-shadow(0 0 8px rgba(201,169,110,0.6))}
.trust-badge h4{
  font-family:var(--font-serif);font-size:1.08rem;
  color:#fff;font-weight:500;margin-bottom:7px;
  letter-spacing:0.01em;
  transition:color 0.3s ease;
}
.trust-badge:hover h4{color:var(--gold-light)}
.trust-badge p{
  font-size:11.5px;color:rgba(255,255,255,0.42);
  letter-spacing:0.04em;line-height:1.65;
  transition:color 0.3s ease;
}
.trust-badge:hover p{color:rgba(255,255,255,0.65)}

/* ===== CONTACT ===== */
#contact{padding:100px 40px;background:#fff}
.contact-inner{display:grid;grid-template-columns:1fr 1fr;gap:60px;max-width:1100px;margin:0 auto;align-items:start}
.contact-info h2{font-family:var(--font-serif);font-size:2.2rem;font-weight:600;color:#2c1810;margin-bottom:20px;}
.contact-detail{display:flex;gap:16px;margin-bottom:20px;align-items:flex-start}
.contact-detail-icon{width:40px;height:40px;border:1px solid rgba(201,169,110,0.3);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.contact-detail-icon svg{width:18px;height:18px;fill:var(--gold)}
.contact-detail-text h4{font-size:12px;letter-spacing:0.15em;text-transform:uppercase;color:var(--gold);margin-bottom:4px}
.contact-detail-text p{font-size:14px;color:var(--text-light);line-height:1.6}
.contact-map{border-radius:4px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);width:100%}
.contact-map iframe{width:100%;height:400px;border:none;display:block}

/* ===== FOOTER ===== */
#footer{background:#faf1e7;padding:64px 24px 36px;position:relative;z-index:1}
.f2-wrap{max-width:880px;margin:0 auto}
.f2-brand{text-align:center}
.f2-lotus{margin-bottom:8px}
.f2-title{font-family:var(--font-serif);font-size:clamp(28px,5vw,40px);font-weight:500;color:#2b2118;letter-spacing:0.01em}
.f2-title span{color:#c9a96e}
.f2-divider{display:flex;align-items:center;justify-content:center;gap:10px;margin:18px auto 22px;max-width:560px}
.f2-divider .f2-line{flex:1;height:1.5px;background:linear-gradient(90deg,rgba(176,124,62,0.15),rgba(176,124,62,0.65),rgba(176,124,62,0.15))}
.f2-diamond{color:#b07c3e;font-size:11px}
.f2-tagline{font-size:16px;color:#7d6f5f;line-height:1.85;max-width:480px;margin:0 auto}
.f2-social{display:flex;justify-content:center;flex-wrap:wrap;gap:26px;margin:36px auto 14px}
.f2-social a{display:flex;flex-direction:column;align-items:center;gap:10px;color:#5a4632;text-decoration:none}
.f2-soc-circle{width:58px;height:58px;border:1.5px solid #c9a96e;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#5a4632;background:transparent;transition:all 0.3s}
.f2-social a:hover .f2-soc-circle{background:#c9a96e;color:#fff;box-shadow:0 6px 18px rgba(201,169,110,0.4);transform:translateY(-3px)}
.f2-soc-label{font-size:14px;color:#5d5246}
.f2-section-head{display:flex;align-items:center;gap:16px;margin:44px 0 20px}
.f2-head-line{flex:1;height:1px;background:rgba(176,124,62,0.35)}
.f2-head-label{display:inline-flex;align-items:center;gap:8px;font-size:15px;font-weight:700;letter-spacing:0.18em;color:#b07c3e;text-transform:uppercase;white-space:nowrap}
.f2-links-card{background:#f6ebdf;border:1px solid rgba(176,124,62,0.18);border-radius:16px;padding:6px 22px;display:grid;grid-template-columns:1fr 1fr;column-gap:40px;position:relative}
.f2-links-card::before{content:'';position:absolute;top:14px;bottom:14px;left:50%;width:1px;background:rgba(176,124,62,0.18)}
.f2-link{display:flex;align-items:center;gap:14px;padding:15px 4px;font-size:16px;color:#3f3528;border-bottom:1px solid rgba(176,124,62,0.14);transition:color 0.25s}
.f2-link:nth-last-child(-n+2){border-bottom:none}
.f2-link:hover{color:#b07c3e}
.f2-link-icon{color:#b07c3e;display:flex;flex-shrink:0}
.f2-chev{margin-left:auto;color:#b07c3e;font-size:20px;line-height:1}
.f2-contact-card{background:#f6ebdf;border:1px solid rgba(176,124,62,0.18);border-radius:16px;padding:6px 22px}
.f2-contact-row{display:flex;align-items:center;gap:16px;padding:15px 4px;font-size:16px;color:#3f3528;border-bottom:1px solid rgba(176,124,62,0.14);text-decoration:none}
.f2-contact-row:last-child{border-bottom:none}
a.f2-contact-row:hover{color:#b07c3e}
.f2-contact-icon{width:42px;height:42px;border:1.5px solid rgba(176,124,62,0.45);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#b07c3e;flex-shrink:0}
.f2-copy{text-align:center;margin-top:36px}
.f2-copy p{font-size:14px;color:#8a7c6b;line-height:1.7}
@media(max-width:600px){
  #footer{padding:52px 16px 30px}
  .f2-social{gap:16px}
  .f2-soc-circle{width:50px;height:50px}
  .f2-soc-label{font-size:12px}
  .f2-links-card{grid-template-columns:1fr 1fr;column-gap:24px;padding:4px 14px}
  .f2-link{font-size:13.5px;gap:9px;padding:13px 2px}
  .f2-chev{font-size:17px}
  .f2-contact-row{font-size:14px;gap:12px}
  .f2-contact-icon{width:38px;height:38px}
}

/* ===== SPEED DIAL FLOAT ===== */
#speedDial{position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;align-items:flex-end;gap:10px;}
.sd-items{display:flex;flex-direction:column;align-items:flex-end;gap:8px;pointer-events:none;}
.sd-items.open{pointer-events:all;}
.sd-item{display:flex;align-items:center;gap:10px;text-decoration:none;transform:translateX(80px) scale(0.8);opacity:0;transition:transform 0.35s cubic-bezier(0.34,1.56,0.64,1),opacity 0.25s ease;}
.sd-items.open .sd-item{transform:translateX(0) scale(1);opacity:1;}
.sd-items.open .sd-item:nth-child(1){transition-delay:0.30s;}
.sd-items.open .sd-item:nth-child(2){transition-delay:0.24s;}
.sd-items.open .sd-item:nth-child(3){transition-delay:0.18s;}
.sd-items.open .sd-item:nth-child(4){transition-delay:0.12s;}
.sd-items.open .sd-item:nth-child(5){transition-delay:0.06s;}
.sd-items.open .sd-item:nth-child(6){transition-delay:0.02s;}
.sd-icon{width:50px;height:50px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(0,0,0,0.25);flex-shrink:0;transition:transform 0.2s,box-shadow 0.2s;}
.sd-item:hover .sd-icon{transform:scale(1.12);box-shadow:0 6px 20px rgba(0,0,0,0.35);}
.sd-label{background:rgba(13,27,20,0.88);color:#fff;font-size:12px;font-weight:600;padding:5px 14px;border-radius:20px;white-space:nowrap;backdrop-filter:blur(6px);letter-spacing:0.04em;box-shadow:0 2px 8px rgba(0,0,0,0.2);}
.sd-main{height:52px;padding:0 20px 0 16px;border-radius:30px;background:linear-gradient(135deg,#0d1b14,#1a2e1e);border:1.5px solid rgba(201,169,110,0.35);cursor:pointer;display:flex;align-items:center;gap:10px;box-shadow:0 4px 24px rgba(0,0,0,0.45),0 0 0 0 rgba(37,211,102,0.4);transition:all 0.35s cubic-bezier(0.34,1.56,0.64,1);position:relative;white-space:nowrap;}
.sd-main:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(0,0,0,0.5),0 0 0 4px rgba(201,169,110,0.15);border-color:rgba(201,169,110,0.6);}
.sd-main.open{background:linear-gradient(135deg,#1a1a2e,#2a1a2e);border-color:rgba(201,169,110,0.2);box-shadow:0 4px 20px rgba(0,0,0,0.5);}
.sd-online-badge{display:flex;align-items:center;gap:5px;padding:3px 9px;background:rgba(37,211,102,0.12);border:1px solid rgba(37,211,102,0.3);border-radius:20px;transition:opacity 0.2s;}
.sd-main.open .sd-online-badge{opacity:0;}
.sd-online-dot-inner{width:7px;height:7px;background:#25D366;border-radius:50%;flex-shrink:0;animation:pulse-dot 1.5s ease-in-out infinite;}
.sd-online-text{font-size:10px;font-weight:700;color:#25D366;letter-spacing:0.06em;text-transform:uppercase;}
.sd-support-icon,.sd-close-icon{transition:opacity 0.2s,transform 0.3s;display:flex;align-items:center;justify-content:center;}
.sd-main.open .sd-support-icon{opacity:0;transform:scale(0);}
.sd-close-icon{opacity:0;transform:rotate(45deg) scale(0);position:absolute;right:20px;}
.sd-main.open .sd-close-icon{opacity:1;transform:rotate(0deg) scale(1);}
.sd-support-label{font-size:12px;font-weight:700;color:rgba(255,255,255,0.9);letter-spacing:0.1em;text-transform:uppercase;transition:opacity 0.2s;}
.sd-main.open .sd-support-label{opacity:0;}
@keyframes pulse-ring{0%{box-shadow:0 0 0 0 rgba(37,211,102,0.4)}100%{box-shadow:0 0 0 14px rgba(37,211,102,0)}}
@keyframes pulse-dot{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.5;transform:scale(1.4)}}
.sd-main:not(.open){animation:sd-glow 3s ease-in-out infinite;}
@keyframes sd-glow{0%,100%{box-shadow:0 4px 24px rgba(0,0,0,0.45),0 0 0 0 rgba(201,169,110,0.2)}50%{box-shadow:0 4px 28px rgba(0,0,0,0.5),0 0 0 6px rgba(201,169,110,0.12)}}

/* ===== PAYMENT METHODS (UPDATED) ===== */
#payment-methods{padding:0 0 80px;background:linear-gradient(135deg,var(--cream) 0%,rgba(232,213,176,0.4) 100%);border-top:2px solid var(--gold);position:relative;overflow:hidden}
#payment-methods::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--gold),transparent);}
/* ===== PAYMENT POLICY — REDESIGNED ===== */
.payment-policy-banner{background:linear-gradient(135deg,#0a1810 0%,#0f211a 60%,#0a1810 100%);padding:40px 40px 36px;position:relative;overflow:hidden;}
.payment-policy-banner::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(201,169,110,0.6),transparent);}
.payment-policy-banner::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(201,169,110,0.3),transparent);}
.pay-policy-label{text-align:center;font-size:10px;font-weight:700;letter-spacing:0.3em;text-transform:uppercase;color:rgba(255,255,255,0.3);margin-bottom:28px;}
.pay-flow{display:flex;align-items:center;justify-content:center;gap:0;flex-wrap:wrap;max-width:900px;margin:0 auto 24px;}
.pay-step{display:flex;flex-direction:column;align-items:center;text-align:center;padding:20px 28px;flex:1;min-width:160px;max-width:240px;}
.pay-step-icon{width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:14px;transition:transform 0.3s ease;}
.pay-step:hover .pay-step-icon{transform:scale(1.1);}
.pay-step-1 .pay-step-icon{background:rgba(201,169,110,0.12);border:1.5px solid rgba(201,169,110,0.35);}
.pay-step-2 .pay-step-icon{background:rgba(74,222,128,0.12);border:1.5px solid rgba(74,222,128,0.4);}
.pay-step-3 .pay-step-icon{background:rgba(201,169,110,0.12);border:1.5px solid rgba(201,169,110,0.35);}
.pay-step-title{font-size:12px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:5px;}
.pay-step-1 .pay-step-title{color:var(--gold);}
.pay-step-2 .pay-step-title{color:#4ade80;}
.pay-step-3 .pay-step-title{color:var(--gold);}
.pay-step-desc{font-size:11.5px;color:rgba(255,255,255,0.45);line-height:1.6;}
.pay-arrow{font-size:20px;color:rgba(201,169,110,0.4);padding:0 4px;margin-top:-18px;flex-shrink:0;}
.pay-tagline{text-align:center;font-family:var(--font-serif);font-size:1.05rem;color:rgba(255,255,255,0.5);letter-spacing:0.02em;}
.pay-tagline strong{color:#4ade80;font-weight:600;}
.pay-typing-wrap{text-align:center;min-height:22px;margin-top:6px;}
.pay-typing{font-size:12px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:var(--gold);}
.pay-typing-cursor{display:inline-block;width:2px;height:13px;background:var(--gold);margin-left:2px;vertical-align:middle;animation:blink-cursor 0.75s step-end infinite;}
@keyframes blink-cursor{0%,100%{opacity:1}50%{opacity:0}}
@media(max-width:768px){
  .payment-policy-banner{padding:28px 20px 24px}
  .pay-flow{gap:0}
  .pay-step{padding:14px 14px;min-width:120px}
  .pay-arrow{font-size:16px;margin-top:-14px}
  .pay-step-desc{font-size:10.5px}
  .pay-step-title{font-size:10px}
}
.payment-header{text-align:center;padding:56px 40px 36px;margin-bottom:0;}
.payment-header h3{font-family:var(--font-serif);font-size:2.5rem;margin-bottom:16px;font-weight:600;color:#2c1810;letter-spacing:-0.02em;position:relative;display:inline-block;}
.payment-header h3::after{content:'';position:absolute;bottom:-10px;left:50%;transform:translateX(-50%);width:60px;height:2px;background:linear-gradient(90deg,transparent,var(--gold),transparent);}
.payment-header p{font-size:15px;color:var(--text-light);max-width:580px;margin:20px auto 0;line-height:1.8;}
.payment-rows{max-width:960px;margin:0 auto;padding:0 40px;}
.payment-row-1{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:18px;}
.payment-row-2{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;max-width:710px;margin:0 auto 0;}
.pm-card{background:#fff;border-radius:16px;border:1.5px solid rgba(201,169,110,0.15);padding:28px 16px 20px;text-align:center;transition:all 0.4s cubic-bezier(0.23,1,0.32,1);position:relative;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,0.05);}
.pm-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--brand-color,#c9a96e);border-radius:4px 4px 0 0;}
.pm-card:hover{transform:translateY(-7px);box-shadow:0 20px 45px rgba(0,0,0,0.12);border-color:rgba(201,169,110,0.35);}
.pm-logo{height:58px;display:flex;align-items:center;justify-content:center;margin-bottom:12px;}
.pm-title{font-size:14px;font-weight:700;margin-bottom:3px;}
.pm-sub{font-size:11px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:var(--text-light);}
.payment-thanks{text-align:center;margin-top:44px;padding:16px;font-size:12px;font-weight:700;color:var(--text-light);letter-spacing:0.12em;text-transform:uppercase;display:flex;align-items:center;justify-content:center;gap:8px;}

/* ===== BOOKING CHANNELS (PREMIUM) ===== */
#booking-channels{padding:80px 40px;background:#fff;position:relative}
#booking-channels::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--gold),transparent);}
.channels-header{text-align:center;margin-bottom:60px}
.channels-header h3{font-family:var(--font-serif);font-size:2.5rem;margin-bottom:16px;font-weight:600;color:#2c1810;letter-spacing:-0.02em;position:relative;display:inline-block;}
.channels-header h3::before{content:'';position:absolute;top:-20px;left:50%;transform:translateX(-50%);width:100px;height:2px;background:linear-gradient(90deg,transparent,var(--gold),transparent);}
.channels-header h3::after{content:'';position:absolute;bottom:-20px;left:50%;transform:translateX(-50%);width:100px;height:2px;background:linear-gradient(90deg,transparent,var(--gold),transparent);}
.channels-header p{font-size:15px;color:var(--text-light);margin-bottom:12px;letter-spacing:0.3px;line-height:1.8}
.channels-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:28px;max-width:1000px;margin:0 auto;justify-items:center;padding-top:40px}
.channel-card{background:linear-gradient(135deg,rgba(201,169,110,0.08),rgba(255,255,255,0.8));border:2px solid rgba(201,169,110,0.3);border-radius:14px;padding:32px 24px;text-align:center;transition:all 0.4s cubic-bezier(0.23,1,0.320,1);cursor:pointer;text-decoration:none;color:inherit;display:flex;flex-direction:column;align-items:center;gap:16px;position:relative;overflow:hidden}
.channel-card::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 30% 30%,rgba(201,169,110,0.2),transparent);opacity:0;transition:opacity 0.4s ease}
.channel-card::after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,transparent 0%,rgba(201,169,110,0.05) 100%);opacity:0;transition:opacity 0.4s ease}
.channel-card:hover{transform:translateY(-10px) scale(1.03);box-shadow:0 25px 60px rgba(201,169,110,0.3);border-color:var(--gold);background:linear-gradient(135deg,rgba(201,169,110,0.15),rgba(232,213,176,0.3))}
.channel-card:hover::before{opacity:1}
.channel-card:hover::after{opacity:1}
.channel-icon{font-size:3.2rem;transition:transform 0.3s ease,filter 0.3s ease;filter:drop-shadow(0 4px 12px rgba(201,169,110,0.2));position:relative;z-index:1}
.channel-card:hover .channel-icon{transform:scale(1.2) rotateZ(5deg);filter:drop-shadow(0 8px 16px rgba(201,169,110,0.4))}
.channel-name{font-size:13px;font-weight:700;color:var(--dark);letter-spacing:0.08em;text-transform:uppercase;position:relative;z-index:1;transition:color 0.3s ease}
.channel-card:hover .channel-name{color:var(--gold)}

/* ===== RESPONSIVE ===== */
@media(max-width:1024px){
  .services-grid{grid-template-columns:repeat(2,1fr);gap:22px}
  .footer-grid{grid-template-columns:1fr 1fr;gap:40px}
  .about-inner{grid-template-columns:1fr;gap:40px}
  .about-img{max-height:420px}
  .contact-inner{grid-template-columns:1fr}
  .booking-inner{grid-template-columns:1fr}
  .booking-left{padding:40px}
  .booking-right{padding:40px}
  .offers-grid{grid-template-columns:1fr}
  .pkg-table thead th{padding:12px 10px;font-size:9px;letter-spacing:0.08em}
  .pkg-table td{padding:12px 10px}
  .pkg-table thead th:nth-child(1){width:22%}
  .pkg-table thead th:nth-child(2){width:46%}
  .pkg-table thead th:nth-child(3){width:16%}
  .pkg-table thead th:nth-child(4){width:16%}
  .pkg-name{font-size:0.9rem}
  .pkg-services li{font-size:11.5px;line-height:1.7}
  .pkg-price{font-size:1.15rem}
  .pkg-duration-num{font-size:1.1rem !important}
  .pkg-duration-unit{font-size:9px !important}
}
@media(max-width:768px){
  /* Navbar */
  #navbar{padding:10px 16px}
  .nav-links{display:none}
  .nav-phone{display:none}
  .hamburger{display:flex}
  /* Mobile logo: icon + 2-line text */
  .nav-logo{display:flex;align-items:center;gap:8px;margin-right:0;flex-shrink:1;white-space:normal}
  .nav-lotus{display:none !important}
  .nav-logo-desktop{display:none !important}
  .nav-brand-icon{display:block !important;width:40px !important;height:48px !important;min-width:40px;flex-shrink:0;filter:drop-shadow(0 0 5px rgba(201,169,110,0.9))}
  .nav-logo-words{display:flex !important;flex-direction:column;line-height:1.3}
  .nav-logo-words .line1{font-family:var(--font-serif);font-size:16px;font-weight:700;color:#fff;letter-spacing:0.3px;white-space:nowrap}
  .nav-logo-words .line1 .gold{color:var(--gold)}
  .nav-logo-words .line2{font-family:var(--font-serif);font-size:13px;font-weight:400;color:rgba(255,255,255,0.9);letter-spacing:0.3px;white-space:nowrap}
  #navbar.scrolled .nav-logo-words .line1{color:var(--dark)}
  #navbar.scrolled .nav-logo-words .line2{color:var(--dark);opacity:0.7}
  .nav-right{gap:10px;flex-shrink:0}
  .nav-btn{padding:8px 14px;font-size:10px;white-space:nowrap;flex-shrink:0}
  /* Sections */
  #hero{height:100svh;min-height:580px}
  .hero-arrows{display:none}
  .slide-content{padding:0 24px}
  .slide-content h1,.slide-content h2.slide-h{font-size:clamp(2rem,8vw,3.2rem) !important}
  .slide-content p{font-size:14px !important}
  .slide-btns{flex-direction:column;gap:10px;align-items:center}
  .slide-btns a{width:200px;text-align:center}
  .slide-stats{gap:20px;flex-wrap:wrap;justify-content:center}
  .slide-stat{min-width:60px}
  #stats{padding:36px 20px}
  .stats-grid{display:grid !important;grid-template-columns:1fr 1fr;gap:0}
  .stat-item{min-width:unset;flex:unset;padding:28px 10px}
  .stat-item:not(:last-child)::after{display:none}
  .stat-num{font-size:2.2rem !important}
  .stat-label{font-size:10px}
  /* Sections padding */
  #services,#offers,#packages,#pricing,#gallery,#testimonials,#booking,#contact,#about{padding:60px 18px}
  .section-header{margin-bottom:40px}
  h2.section-title{font-size:clamp(1.6rem,6vw,2.4rem) !important}
  /* Services */
  .services-grid{grid-template-columns:1fr !important}
  .service-card{max-width:460px;margin:0 auto;width:100%}
  .service-top-accent{height:3px}
  /* Offers */
  .offers-grid{grid-template-columns:1fr;max-width:440px;margin:0 auto}
  .offer-card{padding:24px}
  /* Packages — compact table, same layout as desktop, fits the screen */
  #packages{padding:60px 8px}
  .pkg-table-wrap{border-radius:10px}
  .pkg-table{min-width:unset;table-layout:fixed;width:100%}
  .pkg-table thead th{padding:9px 3px;font-size:7px;letter-spacing:0.06em}
  .pkg-table thead th:nth-child(1){width:21%;padding-left:7px}
  .pkg-table thead th:nth-child(2){width:34%}
  .pkg-table thead th:nth-child(3){width:13%}
  .pkg-table thead th:nth-child(4){width:15%}
  .pkg-table thead th:nth-child(5){width:62px !important}
  .pkg-table td{padding:9px 3px !important;vertical-align:top}
  .pkg-table td:first-child{padding-left:7px !important}
  .pkg-table td:first-child::before{font-size:8px;margin-bottom:2px}
  .pkg-name{font-size:10.5px;font-weight:700;line-height:1.3}
  .pkg-services{margin:0}
  .pkg-services li{font-size:9px;line-height:1.45;padding-left:8px}
  .pkg-services li::before{font-size:8px}
  .pkg-duration{margin:0}
  .pkg-dur-main{font-size:10px}
  .pkg-dur-sub{font-size:7.5px;padding:2px 5px}
  .pkg-price{font-size:11px}
  .pkg-price-label{font-size:6.5px}
  .pkg-table .price-book-btn{padding:5px 3px;font-size:7.5px;letter-spacing:0.02em;border-radius:5px;max-width:100%}
  /* About */
  .about-inner{grid-template-columns:1fr;gap:32px}
  .about-img video{min-height:300px !important}
  .about-img-badge{bottom:16px !important;left:16px !important}
  .about-text h2{font-size:1.9rem}
  /* Pricing — switch to cards on mobile */
  .price-table{display:none !important}
  .price-card-list{display:block !important}
  .tabs{gap:6px;justify-content:flex-start;overflow-x:auto;padding-bottom:6px;flex-wrap:nowrap;-webkit-overflow-scrolling:touch}
  .tabs::-webkit-scrollbar{height:3px}
  .tabs::-webkit-scrollbar-thumb{background:var(--gold);border-radius:2px}
  .tab-btn{padding:9px 16px;font-size:11px;flex-shrink:0}
  /* Gallery */
  .gallery-grid{grid-template-columns:repeat(2,1fr) !important;gap:10px !important}
  .gallery-item{aspect-ratio:1}
  /* Testimonials */
  .testi-reviews-grid{grid-template-columns:1fr !important}
  /* Booking */
  .booking-left{padding:28px 20px}
  .booking-right{padding:28px 20px}
  .form-row{grid-template-columns:1fr}
  .form-btns{flex-direction:column;gap:10px}
  .form-btns .btn{width:100%;flex:unset;white-space:nowrap;font-size:12px;padding:14px 20px;letter-spacing:0.1em}
  /* Trust */
  .trust-inner{flex-wrap:wrap}
  .trust-badge{min-width:45%;padding:32px 16px}
  .trust-badge:not(:last-child)::after{display:none}
  /* Payment Methods */
  .payment-header{padding:36px 20px 24px}
  .payment-header h3{font-size:1.75rem}
  .payment-header p{font-size:13px;line-height:1.7}
  .payment-rows{padding:0 16px}
  .payment-row-1{grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:12px}
  .payment-row-2{grid-template-columns:repeat(3,1fr);gap:10px}
  .pm-card{padding:16px 10px 14px;border-radius:12px}
  .pm-logo{height:46px;margin-bottom:8px}
  .pm-title{font-size:12px;line-height:1.3}
  .pm-sub{font-size:9.5px;letter-spacing:0.05em}
  .payment-thanks{margin-top:28px;font-size:10px}
  /* Lightbox */
  /* Chat widget — compact on mobile, auto-hide */
  #chatWidget{bottom:14px;right:14px;gap:8px}
  .sd-main{height:46px;padding:0 16px 0 12px;gap:8px;}
  .sd-support-label{font-size:11px;}
  .sd-online-text{font-size:9px;}
  /* Hero arrows — keep visible but push down below text */
  .hero-arrows{top:auto;bottom:110px;transform:none;padding:0 12px}
  .hero-arrow{width:36px;height:36px;font-size:14px}
  /* Trust grid 2-col on mobile */
  .trust-inner{display:grid !important;grid-template-columns:1fr 1fr;gap:0}
  .trust-badge{min-width:unset;padding:28px 14px}
  .trust-icon-wrap{width:56px;height:56px}
}
@media(max-width:400px){
  .slide-content h1,.slide-content h2.slide-h{font-size:1.9rem !important}
  .stat-item{padding:20px 8px}
  .pkg-card{padding:24px}
  .offer-card{padding:20px}
}
/* BOOKING CHANNELS */
.booking-channel{position:relative;overflow:hidden}
.booking-channel::before{content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle,rgba(255,255,255,0.3),transparent);animation:shine 3s infinite;opacity:0}
.booking-channel:hover{transform:translateY(-6px);box-shadow:0 12px 32px rgba(201,169,110,0.4) !important}
.booking-channel:hover::before{animation:shine 0.6s ease-in-out;opacity:1}
@keyframes shine{0%{top:-50%;left:-50%}100%{top:150%;left:150%}}
#bookingModal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:1000;align-items:center;justify-content:center}
#bookingModal.active{display:flex;animation:fadeIn 0.3s}
.modal-content{background:#fff;border-radius:12px;padding:32px;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);position:relative;animation:slideUp 0.3s}
.modal-close{position:absolute;top:16px;right:16px;background:none;border:none;font-size:24px;cursor:pointer;color:#999;transition:color 0.2s}
.modal-close:hover{color:var(--dark)}
.qr-display{text-align:center;padding:24px;background:#f5f1eb;border-radius:8px;margin:20px 0}
.qr-display img{max-width:260px;width:100%;border-radius:4px;box-shadow:0 4px 16px rgba(0,0,0,0.1)}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes slideUp{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}

/* ===== CREAM THEME — replace dark green section backgrounds with footer palette ===== */
/* Stats strip — bounded band with gold hairlines */
#stats{background:#f6ebdf;padding:48px 40px;border-top:1px solid rgba(176,124,62,0.25);border-bottom:1px solid rgba(176,124,62,0.25);position:relative}
#stats::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,rgba(176,124,62,0.55),transparent)}
#stats::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,rgba(176,124,62,0.55),transparent)}
#stats .stat-label{color:#7d6f5f}
#stats .stat-item:not(:last-child)::after{background:rgba(176,124,62,0.35)}
#packages{padding-top:70px}
/* Reviews / Testimonials */
#testimonials{background:#faf1e7}
#testimonials h2.section-title.white{color:#2b2118}
#testimonials h2.section-title.white::after{background:linear-gradient(90deg,rgba(176,124,62,0.8),transparent)}
#testimonials .section-title em{color:#b07c3e !important}
#testimonials .section-title .hero-type-cursor{color:#b07c3e !important}
#testimonials .section-label{color:#b07c3e !important}
#testimonials span[style*="color:rgba(255,255,255,0.7)"]{color:#7d6f5f !important}
#testimonials span[style*="color:#fff"]{color:#2b2118 !important}
#testimonials div[style*="color:rgba(255,255,255,0.5)"]{color:#8a7c6b !important}
#testimonials div[style*="background:rgba(255,255,255,0.06)"]{background:#fff !important;border-color:rgba(176,124,62,0.22) !important;box-shadow:0 2px 16px rgba(0,0,0,0.06)}
#testimonials div[style*="font-weight:600;font-size:14px"]{color:#2b2118 !important}
#testimonials div[style*="color:rgba(255,255,255,0.45)"]{color:#8a7c6b !important}
#testimonials p[style*="color:rgba(255,255,255,0.8)"]{color:#5d5246 !important}
#testimonials p[style*="color:rgba(255,255,255,0.7)"]{color:#7d6f5f !important}
/* Booking — Get In Touch panel */
.booking-left{background:#f6ebdf}
.booking-left h2{color:#2b2118}
.booking-left p{color:#7d6f5f}
.booking-left .section-label{color:#b07c3e !important}
.booking-contact-text{color:#5d5246}
.booking-contact-text strong{color:#2b2118 !important}
.booking-confirm{background:rgba(176,124,62,0.08);border-color:rgba(176,124,62,0.25)}
.booking-confirm p{color:#5d5246}
.booking-confirm strong{color:#b07c3e}
/* Trust badges */
#trust{background:#faf1e7;border-top:1px solid rgba(176,124,62,0.2);border-bottom:1px solid rgba(176,124,62,0.2)}
#trust h4{color:#2b2118 !important}
#trust p{color:#7d6f5f !important}
/* Payment policy banner */
.payment-policy-banner{background:#faf1e7;color:#5d5246}
.payment-policy-banner .pay-policy-label{color:#8a7c6b}
.payment-policy-banner .pay-step-title{color:#2b2118}
.payment-policy-banner .pay-step-sub{color:#7d6f5f}
.payment-policy-banner .pay-step-desc{color:#7d6f5f}
.payment-policy-banner .pay-tagline{color:#5d5246}
.payment-policy-banner .pay-tagline strong{color:#2e8b57}
/* Review cards — shadow + hover lift */
#testimonials div[style*="background:rgba(255,255,255,0.06)"]{transition:transform 0.35s cubic-bezier(0.23,1,0.32,1),box-shadow 0.35s ease,border-color 0.35s ease}
#testimonials div[style*="background:rgba(255,255,255,0.06)"]:hover{transform:translateY(-6px);box-shadow:0 18px 44px rgba(176,124,62,0.22) !important;border-color:rgba(176,124,62,0.5) !important}
/* How Payment Works — larger steps, card style with shadow + hover */
.payment-policy-banner{padding:56px 40px 52px}
.payment-policy-banner .pay-policy-label{font-size:12px;margin-bottom:36px}
.pay-step{background:#fff;border:1.5px solid rgba(176,124,62,0.18);border-radius:16px;margin:0 10px 14px;padding:28px 26px;box-shadow:0 4px 18px rgba(0,0,0,0.06);transition:transform 0.35s cubic-bezier(0.23,1,0.32,1),box-shadow 0.35s ease,border-color 0.35s ease;max-width:260px}
.pay-step:hover{transform:translateY(-6px);box-shadow:0 18px 44px rgba(176,124,62,0.22);border-color:rgba(176,124,62,0.45)}
.pay-step-icon{width:60px;height:60px;margin-bottom:18px}
.pay-step-icon svg{width:26px;height:26px}
.pay-step-title{font-size:14px}
.pay-step-desc{font-size:13px}
.pay-arrow{font-size:26px;margin-top:0}
.payment-policy-banner .pay-tagline{font-size:1.2rem;margin-top:10px}
.payment-policy-banner .payment-thanks{color:#8a7c6b}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav id="navbar">
  <a href="#" class="nav-logo">
    <!-- Desktop: simple lotus icon -->
    <svg class="nav-lotus" width="34" height="28" viewBox="0 0 120 100" fill="none" aria-hidden="true">
      <path d="M60 8 L63 16 L71 18 L63 20 L60 28 L57 20 L49 18 L57 16 Z" fill="currentColor"/>
      <path d="M24 88 A 42 42 0 1 1 96 88" stroke="currentColor" stroke-width="4" fill="none" stroke-linecap="round"/>
      <path d="M60 42 C 52 56 52 68 60 76 C 68 68 68 56 60 42 Z" fill="rgba(238,190,194,0.85)" stroke="currentColor" stroke-width="3.5"/>
      <path d="M44 50 C 40 62 46 72 60 76 C 56 64 52 56 44 50 Z" fill="rgba(238,190,194,0.7)" stroke="currentColor" stroke-width="3.5"/>
      <path d="M76 50 C 80 62 74 72 60 76 C 64 64 68 56 76 50 Z" fill="rgba(238,190,194,0.7)" stroke="currentColor" stroke-width="3.5"/>
      <path d="M30 60 C 30 70 40 77 60 76 C 48 70 38 66 30 60 Z" fill="rgba(238,190,194,0.55)" stroke="currentColor" stroke-width="3.5"/>
      <path d="M90 60 C 90 70 80 77 60 76 C 72 70 82 66 90 60 Z" fill="rgba(238,190,194,0.55)" stroke="currentColor" stroke-width="3.5"/>
    </svg>
    <!-- Mobile: actual brand logo icon (arch + star + pink lotus) — sized by CSS -->
    <svg class="nav-brand-icon" viewBox="10 2 100 108" fill="none" aria-hidden="true">
      <!-- 4-pointed star at top -->
      <path d="M60 4 L62.8 11 L70 12 L62.8 13 L60 20 L57.2 13 L50 12 L57.2 11 Z" fill="#C9A96E"/>
      <!-- Arch/semicircle -->
      <path d="M18 108 A 44 44 0 1 1 102 108" stroke="#C9A96E" stroke-width="4" fill="none" stroke-linecap="round"/>
      <!-- Lotus center petal -->
      <path d="M60 58 C 54 70 54 86 60 94 C 66 86 66 70 60 58 Z" fill="#F2C8CC" stroke="#C9A96E" stroke-width="2.5"/>
      <!-- Lotus left inner -->
      <path d="M46 66 C 41 80 47 91 60 94 C 56 82 52 74 46 66 Z" fill="#F2C8CC" stroke="#C9A96E" stroke-width="2.5"/>
      <!-- Lotus right inner -->
      <path d="M74 66 C 79 80 73 91 60 94 C 64 82 68 74 74 66 Z" fill="#F2C8CC" stroke="#C9A96E" stroke-width="2.5"/>
      <!-- Lotus left outer -->
      <path d="M30 78 C 30 91 40 98 60 96 C 48 90 38 86 30 78 Z" fill="#EAB4BA" stroke="#C9A96E" stroke-width="2.5"/>
      <!-- Lotus right outer -->
      <path d="M90 78 C 90 91 80 98 60 96 C 72 90 82 86 90 78 Z" fill="#EAB4BA" stroke="#C9A96E" stroke-width="2.5"/>
    </svg>
    <span class="nav-logo-words">
      <span class="line1">Crystal <span class="gold">Aura</span></span>
      <span class="line2">Massage &amp; Spa</span>
    </span>
    <!-- Desktop text (hidden on mobile) -->
    <span class="nav-logo-desktop">Crystal <span>Aura</span> Massage &amp; Spa</span>
  </a>
  <ul class="nav-links">
    <li><a href="#pricing">Services</a></li>
    <li><a href="#packages">Packages</a></li>
    <li><a href="#signature">Our Signature Treatments</a></li>
    <li><a href="#gallery">Gallery</a></li>
    <li><a href="#testimonials">Reviews</a></li>
    <li><a href="#contact">Contact</a></li>
  </ul>
  <div class="nav-right">
    <span class="nav-phone">095 993 2861</span>
    <a href="#booking" class="btn btn-gold" style="font-size:11px;padding:10px 22px">Book Now</a>
    <div class="hamburger" id="hamburger">
      <span></span><span></span><span></span>
    </div>
  </div>
</nav>

<!-- MOBILE MENU -->
<div class="mobile-menu" id="mobileMenu">
  <button class="mobile-close" id="mobileClose">&#x2715;</button>
  <a href="#pricing" class="mobile-link">Services</a>
  <a href="#packages" class="mobile-link">Packages</a>
  <a href="#signature" class="mobile-link">Our Signature Treatments</a>
  <a href="#gallery" class="mobile-link">Gallery</a>
  <a href="#testimonials" class="mobile-link">Reviews</a>
  <a href="#booking" class="mobile-link">Book Now</a>
  <a href="#contact" class="mobile-link">Contact</a>
</div>

<!-- HERO SLIDER -->
<section id="hero">
  <!-- SHARED VIDEO BACKGROUND — sits behind all slides -->
  <video id="heroBgVideo" autoplay muted loop playsinline style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);min-width:100%;min-height:100%;width:auto;height:auto;object-fit:cover;z-index:0;">
    <source src="massage-bg-opt.mp4" type="video/mp4">
  </video>
  <!-- Shared dark overlay -->
  <div style="position:absolute;inset:0;z-index:1;background:rgba(0,0,0,0.42);pointer-events:none;"></div>

  <!-- Slide 1 -->
  <div class="slide active" id="slide-0">
    <!-- Centered content -->
    <div style="position:absolute;inset:0;z-index:10;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:0 24px;">
      <div style="display:inline-block;border:1px solid rgba(201,169,110,0.7);padding:6px 22px;margin-bottom:24px;letter-spacing:0.3em;font-size:11px;font-weight:700;text-transform:uppercase;color:#c9a96e;font-family:'Lato',sans-serif;">Crystal Aura Massage &amp; Spa · Chiang Mai</div>
      <h1 style="font-family:'Cormorant Garamond',serif;font-size:clamp(3rem,7vw,5.5rem);font-weight:300;color:#fff;line-height:1.1;margin:0 0 20px;text-shadow:0 2px 30px rgba(0,0,0,0.8);">Discover Your <em style="color:#c9a96e;font-style:italic;"><span id="ht1">Inner Peace</span><span class="hero-type-cursor">|</span></em></h1>
      <p style="font-family:'Lato',sans-serif;font-size:1.05rem;color:rgba(255,255,255,0.85);max-width:520px;margin:0 auto 36px;line-height:1.8;text-shadow:0 1px 12px rgba(0,0,0,0.9);">Experience authentic Thailand wellness traditions combined with modern spa techniques.</p>
      <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;align-items:center;">
        <a href="#booking" style="background:#c9a96e;color:#fff;padding:14px 36px;font-family:'Lato',sans-serif;font-size:13px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;text-decoration:none;cursor:pointer;">Book Now</a>
        <a href="#pricing" style="background:transparent;color:#fff;padding:14px 36px;font-family:'Lato',sans-serif;font-size:13px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;text-decoration:none;border:1px solid rgba(255,255,255,0.7);cursor:pointer;">View Services</a>
      </div>
    </div>
    <div style="position:absolute;bottom:80px;left:50%;transform:translateX(-50%);z-index:10;display:flex;flex-direction:column;align-items:center;gap:8px;">
      <span style="font-family:'Lato',sans-serif;font-size:10px;letter-spacing:0.3em;text-transform:uppercase;color:rgba(255,255,255,0.5);">Scroll</span>
      <div style="width:1px;height:40px;background:linear-gradient(to bottom,rgba(201,169,110,0.8),transparent);"></div>
    </div>
  </div>

  <!-- Slide 2 -->
  <div class="slide" id="slide-1">
    <div class="slide-content" style="position:relative;z-index:10;">
      <div class="slide-badge">Since 2022</div>
      <h2 class="slide-h">Authentic <em><span id="ht2">Thai Wellness</span><span class="slide-1-type-cursor">|</span></em></h2>
      <div class="slide-stats">
        <div class="slide-stat"><div class="slide-stat-num">3+</div><div class="slide-stat-label">Years</div></div>
        <div class="slide-stat"><div class="slide-stat-num">5,000+</div><div class="slide-stat-label">Clients</div></div>
        <div class="slide-stat"><div class="slide-stat-num">10,000+</div><div class="slide-stat-label">Treatments</div></div>
        <div class="slide-stat"><div class="slide-stat-num">100%</div><div class="slide-stat-label">Natural</div></div>
      </div>
    </div>
  </div>

  <!-- Slide 3 -->
  <div class="slide" id="slide-2">
    <div class="slide-content" style="position:relative;z-index:10;">
      <span class="section-label" style="color:var(--gold-light)">Welcome to Our Sanctuary</span>
      <h2 class="slide-h">A Sanctuary of <em><span id="ht3">Tranquility</span><span class="slide-1-type-cursor">|</span></em></h2>
      <p>Nestled in the heart of Nimman, Chiang Mai — your escape from the everyday.</p>
      <div class="slide-btns">
        <a href="#booking" class="btn btn-gold">Book Now</a>
        <a href="#pricing" class="btn btn-outline-white">View Services</a>
      </div>
    </div>
  </div>
  <!-- Arrows -->
  <div class="hero-arrows">
    <button class="hero-arrow" id="heroPrev">&#8592;</button>
    <button class="hero-arrow" id="heroNext">&#8594;</button>
  </div>
  <!-- Dots -->
  <div class="hero-dots">
    <div class="hero-dot active" data-slide="0"></div>
    <div class="hero-dot" data-slide="1"></div>
    <div class="hero-dot" data-slide="2"></div>
  </div>
</section>

<!-- STATS BAR -->
<section id="stats">
  <div class="stats-grid">
    <div class="stat-item fade-up"><div class="stat-num">3+</div><div class="stat-label">Years of Excellence</div></div>
    <div class="stat-item fade-up" style="transition-delay:0.1s"><div class="stat-num">5,000+</div><div class="stat-label">Happy Clients</div></div>
    <div class="stat-item fade-up" style="transition-delay:0.2s"><div class="stat-num">10,000+</div><div class="stat-label">Treatments Given</div></div>
    <div class="stat-item fade-up" style="transition-delay:0.3s"><div class="stat-num">100%</div><div class="stat-label">All Natural Products</div></div>
  </div>
</section>

<!-- SERVICES (removed — duplicates #signature table) -->
<section id="services" style="display:none">
  <div class="section-header fade-up">
    <span class="section-label">What We Offer</span>
    <h2 class="section-title shine-heading">Our Signature <em class="accent">Treatments</em></h2>
    <div class="gold-divider"></div>
  </div>
  <div class="services-grid">

    <!-- Card 1 — Traditional Thai -->
    <div class="service-card fade-up" style="--cat-color:#c9a96e">
      <div class="service-top-accent"></div>
      <div class="service-body">
        <div class="service-icon-wrap">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2z"/>
            <path d="M7.5 10.5C9 9 10.5 8.5 12 9s3 1.5 4.5.5"/>
            <path d="M9 13l3 2 3-2M12 15v4M10 19h4"/>
          </svg>
        </div>
        <span class="service-cat">Traditional</span>
        <h3>Traditional Thai Massage</h3>
        <p>Ancient acupressure techniques combined with deep stretching and energy line work to restore balance, relieve tension, and revitalize the body.</p>
        <div class="service-meta">
          <span class="service-duration">60 / 90 / 120 min</span>
          <span class="service-price">From 400 THB</span>
        </div>
        <button class="service-book-btn" onclick="addToCart('Traditional Thai Massage', this)">Book This →</button>
      </div>
    </div>

    <!-- Card 2 — Aromatherapy -->
    <div class="service-card fade-up" style="--cat-color:#9b8ec4;transition-delay:0.1s">
      <div class="service-top-accent"></div>
      <div class="service-body">
        <div class="service-icon-wrap" style="color:#9b8ec4;border-color:rgba(155,142,196,0.25);background:rgba(155,142,196,0.08)">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 22V12"/>
            <path d="M12 12C12 7 7 3.5 7 3.5S7 8 12 9"/>
            <path d="M12 12c0-5 5-8.5 5-8.5S17 8 12 9"/>
            <path d="M9 16c-2-1-3-3-3-3s2 0 3 1M15 16c2-1 3-3 3-3s-2 0-3 1"/>
          </svg>
        </div>
        <span class="service-cat" style="color:#9b8ec4">Aromatherapy</span>
        <h3>Aromatherapy Massage</h3>
        <p>Premium therapeutic essential oils are custom-blended and applied with gentle flowing strokes to deeply calm the mind, ease anxiety, and soften tired muscles.</p>
        <div class="service-meta">
          <span class="service-duration">60 / 90 / 120 min</span>
          <span class="service-price">From 790 THB</span>
        </div>
        <button class="service-book-btn" onclick="addToCart('Aromatherapy Massage', this)">Book This →</button>
      </div>
    </div>

    <!-- Card 3 — Body Scrub -->
    <div class="service-card fade-up" style="--cat-color:#5a9e7a;transition-delay:0.2s">
      <div class="service-top-accent"></div>
      <div class="service-body">
        <div class="service-icon-wrap" style="color:#5a9e7a;border-color:rgba(90,158,122,0.25);background:rgba(90,158,122,0.08)">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="4"/>
            <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
          </svg>
        </div>
        <span class="service-cat" style="color:#5a9e7a">Body Treatment</span>
        <h3>Body Scrub</h3>
        <p>Natural Thai herbs and botanical ingredients gently exfoliate dead skin cells, deeply nourish, and leave your skin luminously glowing and silky-smooth.</p>
        <div class="service-meta">
          <span class="service-duration">60 / 90 min</span>
          <span class="service-price">From 900 THB</span>
        </div>
        <button class="service-book-btn" onclick="addToCart('Body Scrub', this)">Book This →</button>
      </div>
    </div>

    <!-- Card 4 — Anti Aging Facial -->
    <div class="service-card fade-up" style="--cat-color:#d4847a;transition-delay:0.3s">
      <div class="service-top-accent"></div>
      <div class="service-body">
        <div class="service-icon-wrap" style="color:#d4847a;border-color:rgba(212,132,122,0.25);background:rgba(212,132,122,0.08)">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2a7 7 0 0 1 7 7c0 5-7 13-7 13S5 14 5 9a7 7 0 0 1 7-7z"/>
            <circle cx="12" cy="9" r="2.5"/>
          </svg>
        </div>
        <span class="service-cat" style="color:#d4847a">Facial</span>
        <h3>Anti Aging Facial</h3>
        <p>A multi-step premium facial — deep cleanse, antioxidant serum, collagen mask — to visibly firm, hydrate, and restore radiant youthful luminosity to your skin.</p>
        <div class="service-meta">
          <span class="service-duration">60 / 90 / 120 min</span>
          <span class="service-price">From 790 THB</span>
        </div>
        <button class="service-book-btn" onclick="addToCart('Anti Aging Facial', this)">Book This →</button>
      </div>
    </div>

    <!-- Card 5 — Hot Stone -->
    <div class="service-card fade-up" style="--cat-color:#c97f4a;transition-delay:0.4s">
      <div class="service-top-accent"></div>
      <div class="service-body">
        <div class="service-icon-wrap" style="color:#c97f4a;border-color:rgba(201,127,74,0.25);background:rgba(201,127,74,0.08)">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2c-3 4-5 7-3 11 1-2 3-3 4-2-1 3 0 6 3 7 3.5 1 6-2 6-5 0-3-2-5-4-6 0 2-1 3.5-2.5 3.5C16.5 7 15 4 12 2z"/>
          </svg>
        </div>
        <span class="service-cat" style="color:#c97f4a">Therapeutic</span>
        <h3>Hot Stone Massage</h3>
        <p>Smooth warm volcanic basalt stones glide along muscle pathways, melting away deep tension and significantly improving circulation and joint mobility.</p>
        <div class="service-meta">
          <span class="service-duration">90 / 120 min</span>
          <span class="service-price">From 1,500 THB</span>
        </div>
        <button class="service-book-btn" onclick="addToCart('Hot Stone Massage', this)">Book This →</button>
      </div>
    </div>

    <!-- Card 6 — Deep Tissue -->
    <div class="service-card fade-up" style="--cat-color:#5b7fa6;transition-delay:0.5s">
      <div class="service-top-accent"></div>
      <div class="service-body">
        <div class="service-icon-wrap" style="color:#5b7fa6;border-color:rgba(91,127,166,0.25);background:rgba(91,127,166,0.08)">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M13 2L4.5 13.5H11L10 22l9.5-12H13z"/>
          </svg>
        </div>
        <span class="service-cat" style="color:#5b7fa6">Therapeutic</span>
        <h3>Deep Tissue Massage</h3>
        <p>Sustained firm pressure targets deep muscle layers and connective tissue to break up chronic knots, correct posture imbalances, and eliminate persistent soreness.</p>
        <div class="service-meta">
          <span class="service-duration">60 / 90 / 120 min</span>
          <span class="service-price">From 990 THB</span>
        </div>
        <button class="service-book-btn" onclick="addToCart('Deep Tissue Massage', this)">Book This →</button>
      </div>
    </div>

  </div>
</section>

<!-- SPECIAL OFFERS -->
<section id="offers" style="display:none">
  <div class="section-header fade-up">
    <span class="section-label" style="color:var(--gold-light)">Limited Time</span>
    <h2 class="section-title white">Exclusive <em style="font-style:italic;color:#f5d48a;font-weight:700">Offers</em></h2>
    <div class="gold-divider"></div>
  </div>
  <div class="offers-grid">
    <!-- Card 1 -->
    <div class="offer-card fade-up">
      <div class="offer-badge">20% OFF</div>
      <div class="offer-body">
        <h3>First Visit Special</h3>
        <p>Valid for first-time guests only. The perfect introduction to Crystal Aura Spa's healing traditions.</p>
        <p style="font-size:13px;color:rgba(255,255,255,0.5);margin-top:8px;"><span style="color:#4a9eff">✦</span> Any massage treatment · First visit only</p>
        <div class="offer-code" style="margin:18px 0 12px;">Use code: WELCOME20</div>
        <div class="offer-price" style="margin-bottom:0;">
          <span class="original">Regular Price</span>
          <span class="sale">20% Off</span>
        </div>
      </div>
      <a href="#booking" class="btn btn-gold" style="width:100%;text-align:center;display:block;margin-top:20px;">Claim Offer →</a>
    </div>
    <!-- Card 2 -->
    <div class="offer-card fade-up" style="transition-delay:0.1s">
      <div class="offer-badge">Save 380 THB</div>
      <div class="offer-body">
        <h3>Couples Retreat</h3>
        <p>Share an unforgettable spa experience with your partner in our tranquil couples treatment suite.</p>
        <p style="font-size:13px;color:rgba(255,255,255,0.5);margin-top:8px;"><span style="color:#4a9eff">✦</span> 2× Aromatherapy Massage · 90 minutes each</p>
        <div class="offer-code" style="margin:18px 0 12px;">Couples Special Package</div>
        <div class="offer-price" style="margin-bottom:0;">
          <span class="original">2,580 THB</span>
          <span class="sale">2,200 THB</span>
        </div>
      </div>
      <a href="#booking" class="btn btn-gold" style="width:100%;text-align:center;display:block;margin-top:20px;">Book for Two →</a>
    </div>
    <!-- Card 3 -->
    <div class="offer-card fade-up" style="transition-delay:0.2s">
      <div class="offer-badge">⏰ Limited Time</div>
      <div class="offer-body">
        <h3>Weekday Bliss</h3>
        <p>Book any 90-minute treatment Monday–Thursday. Unwind mid-week with this incredible flat-rate value.</p>
        <p style="font-size:13px;color:rgba(255,255,255,0.5);margin-top:8px;"><span style="color:#4a9eff">✦</span> Valid Mon–Thu · All 90 min treatments</p>
        <div class="offer-code" style="margin:18px 0 12px;">Offer Ends In:</div>
        <div class="offer-price" style="margin-bottom:0;">
          <span class="original">From 790 THB</span>
          <span class="sale">999 THB</span>
        </div>
      </div>
      <div class="countdown" id="countdown" style="margin-top:14px;">
        <div class="countdown-unit"><span class="countdown-num" id="cd-days">0</span><span class="countdown-label">Days</span></div>
        <div class="countdown-unit"><span class="countdown-num" id="cd-hours">0</span><span class="countdown-label">Hours</span></div>
        <div class="countdown-unit"><span class="countdown-num" id="cd-mins">0</span><span class="countdown-label">Mins</span></div>
        <div class="countdown-unit"><span class="countdown-num" id="cd-secs">0</span><span class="countdown-label">Secs</span></div>
      </div>
      <a href="#booking" class="btn btn-gold" style="width:100%;text-align:center;display:block;margin-top:14px;">Book Weekday →</a>
    </div>
  </div>
</section>

<!-- PACKAGE DEALS -->
<section id="packages">
  <div class="section-header fade-up">
    <span class="section-label">✦ Packages</span>
    <h2 class="section-title shine-heading">Crystal Aura Spa – <em class="accent">Spa Packages</em></h2>
    <div class="gold-divider"></div>
  </div>

  <div class="pkg-table-wrap fade-up">
    <table class="pkg-table">
      <thead>
        <tr>
          <th>Package Name</th>
          <th>Included Services</th>
          <th>Duration</th>
          <th>Price (THB)</th>
          <th style="text-align:center;width:10%;color:rgba(255,255,255,0.55)">Book</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><span class="pkg-name">Thai Bliss</span></td>
          <td><ul class="pkg-services"><li>Foot Massage (30 min)</li><li>Thai Massage (60 min)</li><li>Herbal Ball Compress (60 min)</li></ul></td>
          <td><div class="pkg-duration"><span class="pkg-dur-main">2.5 hrs.</span><span class="pkg-dur-sub">150 min</span></div></td>
          <td><div class="pkg-price-cell"><span class="pkg-price">฿1,350</span><span class="pkg-price-label">Thai Baht</span></div></td>
        </tr>
        <tr>
          <td><span class="pkg-name">Soothing Retreat</span></td>
          <td><ul class="pkg-services"><li>Foot / Head Massage (60 min)</li><li>Aromatherapy Massage (90 min)</li></ul></td>
          <td><div class="pkg-duration"><span class="pkg-dur-main">2.5 hrs.</span><span class="pkg-dur-sub">150 min</span></div></td>
          <td><div class="pkg-price-cell"><span class="pkg-price">฿1,400</span><span class="pkg-price-label">Thai Baht</span></div></td>
        </tr>
        <tr>
          <td><span class="pkg-name">Ultimate Rejuvenation</span></td>
          <td><ul class="pkg-services"><li>Rose Quartz Foot Massage (30 min)</li><li>Crystal-Rose Quartz Oil Massage (60 min)</li><li>Face Massage (60 min)</li></ul></td>
          <td><div class="pkg-duration"><span class="pkg-dur-main">2.5 hrs.</span><span class="pkg-dur-sub">150 min</span></div></td>
          <td><div class="pkg-price-cell"><span class="pkg-price">฿1,900</span><span class="pkg-price-label">Thai Baht</span></div></td>
        </tr>
        <tr>
          <td><span class="pkg-name">Relaxation Oasis</span></td>
          <td><ul class="pkg-services"><li>Body Detoxing Scrub (60 min)</li><li>Coffee Scrub / Himalayan Salt Scrub (90 min)</li><li>Deep Tissue Massage (90 min)</li></ul></td>
          <td><div class="pkg-duration"><span class="pkg-dur-main">2.5 hrs.</span><span class="pkg-dur-sub">150 min</span></div></td>
          <td><div class="pkg-price-cell"><span class="pkg-price">฿1,900</span><span class="pkg-price-label">Thai Baht</span></div></td>
        </tr>
        <tr>
          <td><span class="pkg-name">Revitalizing Glow</span></td>
          <td><ul class="pkg-services"><li>Aromatherapy Oil Massage (60 min)</li><li>Rose Quartz Gua sha Face Massage (60 min)</li><li>Migraine Reliever Head Massage (30 min)</li></ul></td>
          <td><div class="pkg-duration"><span class="pkg-dur-main">2.5 hrs.</span><span class="pkg-dur-sub">150 min</span></div></td>
          <td><div class="pkg-price-cell"><span class="pkg-price">฿2,100</span><span class="pkg-price-label">Thai Baht</span></div></td>
        </tr>
        <tr>
          <td><span class="pkg-name">Head and Foot Relief</span></td>
          <td><ul class="pkg-services"><li>Foot Massage (30 min)</li><li>Neck Back Shoulder (60 min)</li><li>Migraine Reliever Head Massage (60 min)</li></ul></td>
          <td><div class="pkg-duration"><span class="pkg-dur-main">2 hrs.</span><span class="pkg-dur-sub">150 min</span></div></td>
          <td><div class="pkg-price-cell"><span class="pkg-price">฿1,250</span><span class="pkg-price-label">Thai Baht</span></div></td>
        </tr>
        <tr>
          <td><span class="pkg-name">Foot Bliss</span></td>
          <td><ul class="pkg-services"><li>Foot Massage (60 min)</li><li>Hot Compress (Foot) (30 min)</li><li>Rose Quartz Stone Nursing Oil (60 min)</li></ul></td>
          <td><div class="pkg-duration"><span class="pkg-dur-main">2 hrs.</span><span class="pkg-dur-sub">150 min</span></div></td>
          <td><div class="pkg-price-cell"><span class="pkg-price">฿2,000</span><span class="pkg-price-label">Thai Baht</span></div></td>
        </tr>
        <tr>
          <td><span class="pkg-name">Rejuvenation Skin</span></td>
          <td><ul class="pkg-services"><li>Himalayan Salt Scrub (60 min)</li><li>Hot Aroma Massage (60 min)</li><li>Face Massage (60 min)</li></ul></td>
          <td><div class="pkg-duration"><span class="pkg-dur-main">3 hrs.</span><span class="pkg-dur-sub">180 min</span></div></td>
          <td><div class="pkg-price-cell"><span class="pkg-price">฿2,400</span><span class="pkg-price-label">Thai Baht</span></div></td>
        </tr>
        <tr>
          <td><span class="pkg-name">Crystal Harmony</span></td>
          <td><ul class="pkg-services"><li>Rose Quartz Foot Massage (60 min)</li><li>Crystal-Infused Oil Massage + Rose Quartz Stone (60 min)</li><li>Quartz Gua Sha Face Massage (60 min)</li></ul></td>
          <td><div class="pkg-duration"><span class="pkg-dur-main">3 hrs.</span><span class="pkg-dur-sub">180 min</span></div></td>
          <td><div class="pkg-price-cell"><span class="pkg-price">฿2,900</span><span class="pkg-price-label">Thai Baht</span></div></td>
        </tr>
        <tr>
          <td><span class="pkg-name">Golden Bliss</span></td>
          <td><ul class="pkg-services"><li>Golden Body Scrub (60 min)</li><li>Aromatherapy Massage (60 min)</li><li>Migraine Reliever Head Massage (60 min)</li></ul></td>
          <td><div class="pkg-duration"><span class="pkg-dur-main">3 hrs.</span><span class="pkg-dur-sub">180 min</span></div></td>
          <td><div class="pkg-price-cell"><span class="pkg-price">฿2,500</span><span class="pkg-price-label">Thai Baht</span></div></td>
        </tr>
        <tr>
          <td><span class="pkg-name">Serene Wellness Retreat</span></td>
          <td><ul class="pkg-services"><li>Rose Quartz Foot Massage (60 min)</li><li>Deep Tissue Coconut Oil Massage (60 min)</li><li>Rose Quartz Gua sha Face Massage (60 min)</li></ul></td>
          <td><div class="pkg-duration"><span class="pkg-dur-main">3 hrs.</span><span class="pkg-dur-sub">180 min</span></div></td>
          <td><div class="pkg-price-cell"><span class="pkg-price">฿2,500</span><span class="pkg-price-label">Thai Baht</span></div></td>
        </tr>
        <tr>
          <td><span class="pkg-name">Harmonious Spa Escape</span></td>
          <td><ul class="pkg-services"><li>Himalayan Salt Scrub (60 min)</li><li>Crystal-Infused Oil Massage + Rose Quartz Stone (60 min)</li><li>Face Massage (60 min)</li></ul></td>
          <td><div class="pkg-duration"><span class="pkg-dur-main">3 hrs.</span><span class="pkg-dur-sub">180 min</span></div></td>
          <td><div class="pkg-price-cell"><span class="pkg-price">฿3,000</span><span class="pkg-price-label">Thai Baht</span></div></td>
        </tr>
        <tr>
          <td><span class="pkg-name">Tranquil Spa Sanctuary</span></td>
          <td><ul class="pkg-services"><li>Traditional Thai Massage (60 min)</li><li>Herbal Ball Hot Compress (60 min)</li><li>Coconut Oil Massage (60 min)</li><li>Face Massage (60 min)</li></ul></td>
          <td><div class="pkg-duration"><span class="pkg-dur-main">4 hrs.</span><span class="pkg-dur-sub">240 min</span></div></td>
          <td><div class="pkg-price-cell"><span class="pkg-price">฿2,600</span><span class="pkg-price-label">Thai Baht</span></div></td>
        </tr>
      </tbody>
    </table>
  </div>

  <p class="pkg-footer-note">All prices are in Thai Baht (THB). &nbsp;For your comfort and safety, please inform your therapist of any medical conditions, allergies, injuries, or special requirements prior to your treatment.</p>
</section>

<!-- OUR SIGNATURE TREATMENTS -->
<section id="signature">
  <div class="section-header fade-up">
    <span class="section-label" style="color:var(--gold)">✦ Our Signature Treatments</span>
    <h2 class="section-title" style="-webkit-text-fill-color:var(--dark);color:var(--dark)">Our Signature <em style="font-style:italic;color:var(--gold)">Treatments</em></h2>
    <div class="gold-divider"></div>
  </div>

  <p class="sig-intro fade-up">
    Loved by guests, trusted by locals, and recommended by travelers worldwide — our spa treatments have become Chiang Mai's best-kept wellness secret. With a loyal community of returning clients and countless five-star recommendations, each of our signature therapies is a testament to the art of exceptional care. From deeply restorative massages to time-honored Lanna rituals, every treatment is designed to bring you back — again and again.
  </p>

  <div class="sig-table-wrap fade-up">
    <table class="sig-table">
      <thead>
        <tr>
          <th><span class="th-label">Service</span></th>
          <th><span class="th-num">60</span><span class="th-label">min</span></th>
          <th><span class="th-num">90</span><span class="th-label">min</span></th>
          <th><span class="th-num">120</span><span class="th-label">min</span></th>
          <th style="text-align:center;color:rgba(255,255,255,0.55);width:12%"><span class="th-label">Book</span></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <div class="sig-name-wrap">
              <span class="sig-service">Foot Scrub + Foot Reflexology</span>
              <span class="sig-desc">Detoxifying scrub followed by pressure-point reflexology therapy</span>
              <span class="sig-badge popular">★ Guest Favourite</span>
            </div>
          </td>
          <td><span class="sig-price">฿690</span></td>
          <td><span class="sig-price">฿1,000</span></td>
          <td><span class="sig-price">฿1,100</span></td>
        </tr>
        <tr>
          <td>
            <div class="sig-name-wrap">
              <span class="sig-service">4-Hands Thai Massage</span>
              <span class="sig-desc">Two therapists, perfectly synchronized deep-tissue bodywork</span>
              <span class="sig-badge exclusive">✦ Exclusive</span>
            </div>
          </td>
          <td><span class="sig-price">฿1,200</span></td>
          <td><span class="sig-price">฿1,750</span></td>
          <td><span class="sig-dash">—</span></td>
        </tr>
        <tr>
          <td>
            <div class="sig-name-wrap">
              <span class="sig-service">Aromatherapy + Bamboo Massage</span>
              <span class="sig-desc">Premium essential oils with warm heated bamboo cane rolling</span>
            </div>
          </td>
          <td><span class="sig-dash">—</span></td>
          <td><span class="sig-price">฿1,500</span></td>
          <td><span class="sig-price">฿1,900</span></td>
        </tr>
        <tr>
          <td>
            <div class="sig-name-wrap">
              <span class="sig-service">Aromatherapy + Gua Sha</span>
              <span class="sig-desc">Flowing oil massage combined with traditional jade gua sha facial</span>
            </div>
          </td>
          <td><span class="sig-price">฿790</span></td>
          <td><span class="sig-price">฿1,100</span></td>
          <td><span class="sig-price">฿1,500</span></td>
        </tr>
        <tr>
          <td>
            <div class="sig-name-wrap">
              <span class="sig-service">Aromatherapy + Herbal Ball</span>
              <span class="sig-desc">Steamed Thai herbal compress applied with warm aromatic oil</span>
            </div>
          </td>
          <td><span class="sig-dash">—</span></td>
          <td><span class="sig-price">฿1,200</span></td>
          <td><span class="sig-price">฿1,700</span></td>
        </tr>
        <tr>
          <td>
            <div class="sig-name-wrap">
              <span class="sig-service">CBD Aroma Massage</span>
              <span class="sig-desc">CBD-infused botanical oil for deep muscle relief and calm</span>
              <span class="sig-badge new">◉ New</span>
            </div>
          </td>
          <td><span class="sig-price">฿1,290</span></td>
          <td><span class="sig-price">฿1,900</span></td>
          <td><span class="sig-price">฿2,400</span></td>
        </tr>
        <tr>
          <td>
            <div class="sig-name-wrap">
              <span class="sig-service">Ear Cleaning &amp; Spa</span>
              <span class="sig-desc">Professional ear cleanse with soothing aromatherapy care</span>
            </div>
          </td>
          <td><span class="sig-price">฿790</span></td>
          <td><span class="sig-dash">—</span></td>
          <td><span class="sig-dash">—</span></td>
        </tr>
        <tr>
          <td>
            <div class="sig-name-wrap">
              <span class="sig-service">Facial Massage with Charcoal Mask</span>
              <span class="sig-desc">Deep-pore charcoal cleanse and blackhead extraction facial</span>
              <span class="sig-badge popular">★ Guest Favourite</span>
            </div>
          </td>
          <td><span class="sig-price">฿990</span></td>
          <td><span class="sig-price">฿1,450</span></td>
          <td><span class="sig-price">฿1,800</span></td>
        </tr>
        <tr>
          <td>
            <div class="sig-name-wrap">
              <span class="sig-service">Thai Massage with Bamboo</span>
              <span class="sig-desc">Traditional Thai bodywork enhanced with warm bamboo rolling</span>
            </div>
          </td>
          <td><span class="sig-price">฿790</span></td>
          <td><span class="sig-price">฿1,150</span></td>
          <td><span class="sig-price">฿1,400</span></td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="sig-book-wrap fade-up">
    <a href="#booking" class="hero-offer-btn">Book a Signature Treatment →</a>
  </div>
</section>

<!-- ABOUT -->
<section id="about">
  <div class="about-inner">
    <div class="about-text fade-up">
      <span class="section-label">Our Story</span>
      <h2>Years of Thai Wellness <em class="accent"><span id="ht4">Mastery</span><span class="hero-type-cursor" style="color:#7a4a1e;">|</span></em></h2>
      <div class="gold-divider left"></div>
      <p>Founded in 2022, Crystal Aura Spa has grown from a small neighbourhood massage studio into one of Chiang Mai's most beloved wellness destinations. Nestled in the charming Nimman district, we blend ancient Thai healing traditions with the finest natural ingredients.</p>
      <p>Our team of certified therapists each brings years of training in traditional Thai healing arts, ensuring every treatment is an authentic journey into wellness — not simply a service, but a transformative experience.</p>
      <ul class="about-list">
        <li>Certified Traditional Thai Massage Therapists</li>
        <li>100% Natural & Locally Sourced Products</li>
        <li>Private Treatment Rooms for Full Relaxation</li>
        <li>Couples & Group Bookings Welcome</li>
        <li>Open Daily 09:00 AM – 11:30 PM</li>
      </ul>
      <a href="#booking" class="btn btn-gold" style="margin-top:28px">Reserve Your Treatment</a>
    </div>
    <div class="about-img" style="position:relative;border-radius:4px;overflow:hidden;">
      <video autoplay muted loop playsinline style="width:100%;height:100%;object-fit:cover;display:block;min-height:460px;">
        <source src="about-video-opt.mp4" type="video/mp4">
      </video>
      <div style="position:absolute;inset:0;background:rgba(0,0,0,0.25);"></div>
      <div class="about-img-badge" style="position:absolute;bottom:24px;left:24px;z-index:2;">
        <span class="num">3+</span>
        <span class="lbl">Years of<br>Excellence</span>
      </div>
      <!-- Certified therapists badge -->
      <div style="position:absolute;top:24px;right:24px;z-index:2;background:rgba(255,255,255,0.95);border-radius:8px;padding:14px 18px;display:flex;align-items:center;gap:12px;box-shadow:0 4px 20px rgba(0,0,0,0.15);max-width:200px;">
        <div style="width:44px;height:44px;background:#c9a96e;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 15l-3 3 1-4-3-3 4-.5L12 7l1.5 3.5 4 .5-3 3 1 4z"/><circle cx="12" cy="12" r="10"/></svg>
        </div>
        <div>
          <div style="font-size:11px;font-weight:700;color:#3E3835;letter-spacing:0.05em;line-height:1.3;">CERTIFIED</div>
          <div style="font-size:10px;color:#6B6360;line-height:1.4;">Thai Massage<br>Therapists</div>
          <div style="display:flex;gap:2px;margin-top:4px;">
            <span style="font-size:9px;background:#c9a96e;color:#fff;padding:1px 5px;border-radius:2px;">WAT PHO</span>
            <span style="font-size:9px;background:#5E7A5A;color:#fff;padding:1px 5px;border-radius:2px;">MOT</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- PRICING -->
<section id="pricing">
  <div class="section-header fade-up">
    <span class="section-label">Transparent Pricing</span>
    <h2 class="section-title shine-heading">Treatment Menu & <em class="accent">Rates</em></h2>
    <div class="gold-divider"></div>
    <p style="font-size:14px;color:var(--text-light);margin-top:-10px">All prices in Thai Baht (THB) · 60 / 90 / 120 min</p>
  </div>
  <div class="fade-up">
    <!-- HORIZONTAL TAB BUTTONS -->
    <div class="pricing-tabs-nav">
      <button class="pricing-tab-btn active" data-tab="foot">✦ Foot Massage</button>
      <button class="pricing-tab-btn" data-tab="traditional">✦ Thai Massage</button>
      <button class="pricing-tab-btn" data-tab="oil">✦ Aromatherapy (Oil)</button>
      <button class="pricing-tab-btn" data-tab="head">✦ Head Massage</button>
      <button class="pricing-tab-btn" data-tab="facial">✦ Facial Treatment</button>
      <button class="pricing-tab-btn" data-tab="scrub">✦ Body Scrub</button>
    </div>

    <!-- FOOT MASSAGE TAB CONTENT -->
    <div class="pricing-tab-content active" id="tab-foot">
      <p style="font-size:14px;color:var(--text-light);margin-bottom:24px;font-style:italic">Start your treatment with a relaxing foot bath, followed by professional reflexology therapy designed to relieve tension, improve circulation, and restore balance.</p>
      <div class="accordion-content-pricing">
        <div class="price-row">
          <div class="price-row-name">Foot Massage</div>
          <div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">400</span></div>
          <div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">590</span></div>
          <div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">780</span></div>
        <div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row">
          <div class="price-row-name">Foot Scrub + Foot Reflexology</div>
          <div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">690</span></div>
          <div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1000</span></div>
          <div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1100</span></div>
        <div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row">
          <div class="price-row-name">Foot Massage + Herbal Ball Hot Compress</div>
          <div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">650</span></div>
          <div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">950</span></div>
          <div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1200</span></div>
        <div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row">
          <div class="price-row-name">Foot, Back, Neck & Shoulder</div>
          <div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">550</span></div>
          <div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">800</span></div>
          <div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1050</span></div>
        <div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row">
          <div class="price-row-name">Rose Quartz Foot Massage</div>
          <div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">490</span></div>
          <div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">690</span></div>
          <div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">900</span></div>
        <div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
      </div>
    </div>

    <!-- THAI MASSAGE TAB CONTENT -->
    <div class="pricing-tab-content" id="tab-traditional">
      <p style="font-size:14px;color:var(--text-light);margin-bottom:24px;font-style:italic">Discover the benefits of authentic Traditional Thai Massage in Nimman. Combining expert stretching, acupressure, and centuries-old healing techniques, this treatment relieves tension, improves flexibility, and restores balance to body and mind. Ideal for relaxation, recovery, and overall wellness.</p>
      <div class="accordion-content-pricing">
        <div class="price-row">
          <div class="price-row-name">Thai Traditional Massage</div>
          <div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">400</span></div>
          <div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">590</span></div>
          <div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">780</span></div>
        <div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row">
          <div class="price-row-name">4-Hands Thai Massage</div>
          <div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">1200</span></div>
          <div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1750</span></div>
          <div class="price-col"><span class="price-col-label">120 min</span><span style="font-size:18px;color:var(--text-light)">—</span></div>
        <div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row">
          <div class="price-row-name">Neck, Back & Shoulder Massage</div>
          <div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">650</span></div>
          <div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">950</span></div>
          <div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1200</span></div>
        <div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row">
          <div class="price-row-name">Thai Balm Massage</div>
          <div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">650</span></div>
          <div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">950</span></div>
          <div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1200</span></div>
        <div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row">
          <div class="price-row-name">Thai Massage + Herbal Ball Compress</div>
          <div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">890</span></div>
          <div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1300</span></div>
          <div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1650</span></div>
        <div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row">
          <div class="price-row-name">Thai Massage with Bamboo</div>
          <div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">790</span></div>
          <div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1150</span></div>
          <div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1400</span></div>
        <div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
      </div>
    </div>

    <!-- AROMATHERAPY TAB CONTENT -->
    <div class="pricing-tab-content" id="tab-oil">
      <p style="font-size:14px;color:var(--text-light);margin-bottom:24px;font-style:italic">Relax and recharge with an Aromatherapy Oil Massage. Essential oils and soothing massage techniques relieve stress, ease muscle tension, and restore balance.</p>
      <div class="accordion-content-pricing">
        <div class="price-row"><div class="price-row-name">4-Hands Aroma Massage</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">1600</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">2300</span></div><div class="price-col"><span class="price-col-label">120 min</span><span style="font-size:18px;color:var(--text-light)">—</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row"><div class="price-row-name">Oil Massage</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">690</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1000</span></div><div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1300</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row"><div class="price-row-name">Aromatherapy Massage</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">790</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1150</span></div><div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1400</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row"><div class="price-row-name">Aloe Vera Gel / Body Lotion Massage</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">790</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1150</span></div><div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1400</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row"><div class="price-row-name">Hot Aromatherapy Massage</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">990</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1450</span></div><div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1800</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row"><div class="price-row-name">Sports Massage</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">990</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1450</span></div><div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1800</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row"><div class="price-row-name">Deep Tissue Massage</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">990</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1450</span></div><div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1800</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row"><div class="price-row-name">Virgin Coconut Oil Massage</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">990</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1450</span></div><div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1800</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row"><div class="price-row-name">CBD Aroma Massage</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">1290</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1900</span></div><div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">2400</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row"><div class="price-row-name">Hot Stone Massage</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">1100</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1500</span></div><div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1900</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row"><div class="price-row-name">Aromatherapy + Gua Sha</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">790</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1100</span></div><div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1500</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row"><div class="price-row-name">Aromatherapy + Herbal Ball</div><div class="price-col"><span class="price-col-label">60 min</span><span style="font-size:18px;color:var(--text-light)">—</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1200</span></div><div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1700</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row"><div class="price-row-name">Aromatherapy + Bamboo Massage</div><div class="price-col"><span class="price-col-label">60 min</span><span style="font-size:18px;color:var(--text-light)">—</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1500</span></div><div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1900</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row"><div class="price-row-name">Rose Quartz Stone Therapy</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">1290</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1900</span></div><div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">2400</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row"><div class="price-row-name">Aroma Oil for Kids</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">590</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">850</span></div><div class="price-col"><span class="price-col-label">120 min</span><span style="font-size:18px;color:var(--text-light)">—</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
      </div>
    </div>

    <!-- HEAD MASSAGE TAB CONTENT -->
    <div class="pricing-tab-content" id="tab-head">
      <p style="font-size:14px;color:var(--text-light);margin-bottom:24px;font-style:italic">Whether you're seeking relief from headaches, screen fatigue, neck stiffness, each treatment is tailored to your needs. Relax in a serene wellness sanctuary and experience improved circulation, enhanced mental clarity, and a profound sense of relaxation.</p>
      <div class="accordion-content-pricing">
        <div class="price-row"><div class="price-row-name">Migraine Reliever Head Massage</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">790</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1150</span></div><div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1400</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row"><div class="price-row-name">Indian Head Massage with Coconut Oil</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">990</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1450</span></div><div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1800</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row"><div class="price-row-name">Ear Cleaning & Spa</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">790</span></div><div class="price-col"><span class="price-col-label">90 min</span><span style="font-size:18px;color:var(--text-light)">—</span></div><div class="price-col"><span class="price-col-label">120 min</span><span style="font-size:18px;color:var(--text-light)">—</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
      </div>
    </div>

    <!-- FACIAL TREATMENT TAB CONTENT -->
    <div class="pricing-tab-content" id="tab-facial">
      <p style="font-size:14px;color:var(--text-light);margin-bottom:24px;font-style:italic">Each treatment is carefully tailored to improve circulation, stimulate lymphatic drainage, promote collagen production, and enhance overall skin health. Whether you are looking for a deep cleansing facial, a natural facial lifting treatment, or a luxurious skin rejuvenation experience, our facial therapies help leave your skin feeling refreshed, smoother, firmer, and visibly glowing.</p>
      <div class="accordion-content-pricing">
        <div class="price-row"><div class="price-row-name">Anti Aging Facial Massage (Gua Sha)</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">790</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1150</span></div><div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1400</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row"><div class="price-row-name">Facial Massage with Charcoal Mask (Blackhead Removal)</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">990</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1450</span></div><div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">1800</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row"><div class="price-row-name">Rose Quartz Gua Sha Facial</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">1290</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1900</span></div><div class="price-col"><span class="price-col-label">120 min</span><span class="price-col-value">2400</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
      </div>
    </div>

    <!-- BODY SCRUB TAB CONTENT -->
    <div class="pricing-tab-content" id="tab-scrub">
      <p style="font-size:14px;color:var(--text-light);margin-bottom:24px;font-style:italic">Treatments deeply hydrate, boost circulation for a natural glow, re-mineralise with 84 pure Himalayan trace minerals, and draw out impurities from the pores — leaving your skin silky smooth, thoroughly detoxified, and radiantly healthy from head to toe.</p>
      <div class="accordion-content-pricing">
        <div class="price-row"><div class="price-row-name">Body Scrub</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">890</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1300</span></div><div class="price-col"><span class="price-col-label">120 min</span><span style="font-size:18px;color:var(--text-light)">—</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
        <div class="price-row"><div class="price-row-name">Himalayan Salt Scrub</div><div class="price-col"><span class="price-col-label">60 min</span><span class="price-col-value">990</span></div><div class="price-col"><span class="price-col-label">90 min</span><span class="price-col-value">1450</span></div><div class="price-col"><span class="price-col-label">120 min</span><span style="font-size:18px;color:var(--text-light)">—</span></div><div class="price-book-col"><button class="price-book-btn" onclick="bookTreatment(this)">Book &#x2192;</button></div></div>
      </div>
    </div>
  </div>
  <p style="text-align:center;font-size:13px;color:var(--text-light);margin-top:32px;font-style:italic">All prices are in Thai Baht (THB). Please inform your therapist of any medical conditions, allergies, or special requirements prior to your treatment.</p>
</section>

<!-- GALLERY -->
<section id="gallery">
  <div class="section-header fade-up">
    <span class="section-label">Our Space</span>
    <h2 class="section-title shine-heading">A Glimpse of <em class="accent"><span id="ht5">Serenity</span><span class="hero-type-cursor" style="color:#7a4a1e;">|</span></em></h2>
    <div class="gold-divider"></div>
  </div>
  <div class="gallery-grid fade-up" id="galleryGrid">
    <div class="gallery-item" data-index="0">
      <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/att.br74s8i4LKN26F-Kvn4hMbtSlnDSxU6oaxO9RsrVHSo-kEya3AK22B24Hoz768V0pBdqyk07p9.jpeg" alt="Crystal Aura Spa Gallery Image 1" loading="lazy">
      <div class="gallery-overlay"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></div>
    </div>
    <div class="gallery-item" data-index="1">
      <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/att.dq5Xbt0PJ59vWUulX1_QINbgZXn9CmHF4yp2QeeLn3A-iGWTG7EmtDBG9lydH7yWUQoUxPrnt6.jpeg" alt="Crystal Aura Spa Gallery Image 2" loading="lazy">
      <div class="gallery-overlay"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></div>
    </div>
    <div class="gallery-item" data-index="2">
      <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/att.kvPPpCRS3162381usHb5oy88QOHgER7dfdKJA8zO3QU-xrfp78aOyp12DfjfrFhOKvik9sexdK.jpeg" alt="Crystal Aura Spa Gallery Image 3" loading="lazy">
      <div class="gallery-overlay"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></div>
    </div>
    <div class="gallery-item" data-index="3">
      <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/att.oByDr1x0EqHLOJ1-PpiAFc_e26Uh_vTv5KWFmWMXeQc-DocXRoTysKTHdzmIPvq2Si0hkoXmFo.jpeg" alt="Crystal Aura Spa Gallery Image 4" loading="lazy">
      <div class="gallery-overlay"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></div>
    </div>
    <div class="gallery-item" data-index="4">
      <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/att.rcsD3_YC7OCwNnJMY8u-ZU5_sMkbY7DxWiiSJc6io84-avgodXFbAy30NBoAeeoIR9DiNOFAGs.jpeg" alt="Crystal Aura Spa Gallery Image 5" loading="lazy">
      <div class="gallery-overlay"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></div>
    </div>
    <div class="gallery-item" data-index="5">
      <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/att.vemFMJJhs9RE2wyk1gf2MJzzjwq7nXorgHMjokh233s-BID3y9ykpgLY4mGaNPR32QG6l3uffC.jpeg" alt="Crystal Aura Spa Gallery Image 6" loading="lazy">
      <div class="gallery-overlay"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></div>
    </div>
    <div class="gallery-item" data-index="6">
      <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/att.1PLm5TEYt7k-X_hol1fjUn_U3xdXCd0ee2ORdfKh4nw-DXCdNrZfcAZN7x0H4iec1qzW3LsOSR.jpeg" alt="Crystal Aura Spa Gallery Image 7" loading="lazy">
      <div class="gallery-overlay"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></div>
    </div>
    <div class="gallery-item" data-index="7">
      <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/att.1bki5nR-9h5s5j5RukwOmP2CI5NCOTIUguzSd14DQQ4-r8zDBxDgdIsiwWYLYk7QRxJby6YeAz.jpeg" alt="Crystal Aura Spa Gallery Image 8" loading="lazy">
      <div class="gallery-overlay"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></div>
    </div>
    <div class="gallery-item" data-index="8">
      <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/att.DfkrIwKFwHD1Lxvdss-XzpSBBUM3gDvUnKS_trd7OE0-FJBPgCtbfGgNFQhkd0MzFTDDU0Un84.jpeg" alt="Crystal Aura Spa Gallery Image 9" loading="lazy">
      <div class="gallery-overlay"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></div>
    </div>
    <div class="gallery-item" data-index="9">
      <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/att.K3biOrrMCK-e6NqjyC3dG-FNXW9NjyIGWWAyvBbKKqw-pOvi4egZ9RCi1f9DvYsS6xN7eoOPqo.jpeg" alt="Crystal Aura Spa Gallery Image 10" loading="lazy">
      <div class="gallery-overlay"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></div>
    </div>
    <div class="gallery-item" data-index="10">
      <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/att.Q3NDnUfj3MED5kU4Rp5VNzNr1LTX3z9UHkyQfjP_TTQ-Lk1t9jOCKDY5TfMt7YKsWfoyy5y5og.jpeg" alt="Crystal Aura Spa Gallery Image 11" loading="lazy">
      <div class="gallery-overlay"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></div>
    </div>
    <div class="gallery-item" data-index="11">
      <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/att.SKh6eAU5MDS4Tw1hvoh6Yt3yCuEgb1weUucnbjrwrRI-zNr2Qv28jsI0co1N0pYPCVspXoJrgK.jpeg" alt="Crystal Aura Spa Gallery Image 12" loading="lazy">
      <div class="gallery-overlay"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></div>
    </div>
  </div>

  <!-- GALLERY POPUP LIGHTBOX -->
  <div id="galleryPopup">
    <div class="gallery-popup-close" onclick="window.GalleryPopup.close()">&times;</div>
    <div class="gallery-popup-nav">
      <button class="gallery-popup-btn" onclick="window.GalleryPopup.prev()">&lt;</button>
      <button class="gallery-popup-btn" onclick="window.GalleryPopup.next()">&gt;</button>
    </div>
    <img id="galleryPopupImg" src="" alt="Gallery Image">
    <div class="gallery-popup-counter"><span id="galleryPopupCounter">1</span> / 12</div>
  </div>
</section>

<!-- TESTIMONIALS — Google Reviews Style -->
<section id="testimonials">
  <div class="section-header fade-up">
    <span class="section-label" style="color:var(--gold-light)">Guest Reviews</span>
    <h2 class="section-title white">What Our <em style="font-style:italic;color:#f5d48a;font-weight:700"><span id="ht6">Guests</span><span class="hero-type-cursor" style="color:#f5d48a;">|</span> Say</em></h2>
    <div class="gold-divider"></div>
    <!-- Google overall rating bar -->
    <div style="display:flex;align-items:center;justify-content:center;gap:16px;margin-top:16px;flex-wrap:wrap;">
      <div style="display:flex;align-items:center;gap:8px;">
        <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
        <span style="font-size:13px;color:rgba(255,255,255,0.7);">Google Reviews</span>
      </div>
      <div style="display:flex;align-items:center;gap:6px;">
        <span style="font-size:2rem;font-weight:700;color:#fff;font-family:'Cormorant Garamond',serif;">4.9</span>
        <div>
          <div style="color:#FBBC05;font-size:16px;">★★★★★</div>
          <div style="font-size:11px;color:rgba(255,255,255,0.5);">Based on 500+ reviews</div>
        </div>
      </div>
    </div>
  </div>
  <!-- Reviews Grid -->
  <div style="max-width:1100px;margin:40px auto 0;display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;padding:0 20px;" class="fade-up">
    <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:24px;">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <div style="width:42px;height:42px;border-radius:50%;background:#4285F4;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:16px;">S</div>
        <div>
          <div style="color:#fff;font-weight:600;font-size:14px;">Sarah Mitchell</div>
          <div style="font-size:11px;color:rgba(255,255,255,0.45);">Australia · 2 months ago</div>
        </div>
        <svg width="16" height="16" viewBox="0 0 24 24" style="margin-left:auto;flex-shrink:0;"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
      </div>
      <div style="color:#FBBC05;margin-bottom:10px;font-size:14px;">★★★★★</div>
      <p style="color:rgba(255,255,255,0.8);font-size:13px;line-height:1.7;">"Absolutely the best spa experience I've had in Thailand! The therapists are incredibly skilled and the atmosphere is so peaceful. The aromatherapy massage was heavenly."</p>
    </div>
    <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:24px;">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <div style="width:42px;height:42px;border-radius:50%;background:#34A853;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:16px;">J</div>
        <div>
          <div style="color:#fff;font-weight:600;font-size:14px;">James Chen</div>
          <div style="font-size:11px;color:rgba(255,255,255,0.45);">Singapore · 3 months ago</div>
        </div>
        <svg width="16" height="16" viewBox="0 0 24 24" style="margin-left:auto;flex-shrink:0;"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
      </div>
      <div style="color:#FBBC05;margin-bottom:10px;font-size:14px;">★★★★★</div>
      <p style="color:rgba(255,255,255,0.8);font-size:13px;line-height:1.7;">"I visit Chiang Mai frequently for business and Crystal Aura is always my first stop. The traditional Thai massage is authentic — the staff even remembers my preferences!"</p>
    </div>
    <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:24px;">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <div style="width:42px;height:42px;border-radius:50%;background:#EA4335;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:16px;">E</div>
        <div>
          <div style="color:#fff;font-weight:600;font-size:14px;">Emma Laurent</div>
          <div style="font-size:11px;color:rgba(255,255,255,0.45);">France · 1 month ago</div>
        </div>
        <svg width="16" height="16" viewBox="0 0 24 24" style="margin-left:auto;flex-shrink:0;"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
      </div>
      <div style="color:#FBBC05;margin-bottom:10px;font-size:14px;">★★★★★</div>
      <p style="color:rgba(255,255,255,0.8);font-size:13px;line-height:1.7;">"Such a hidden gem in Nimman! The hot stone therapy was exactly what I needed after a long flight. The ambiance is serene and the attention to detail is remarkable."</p>
    </div>
    <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:24px;">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <div style="width:42px;height:42px;border-radius:50%;background:#c9a96e;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:16px;">M</div>
        <div>
          <div style="color:#fff;font-weight:600;font-size:14px;">Michael Thompson</div>
          <div style="font-size:11px;color:rgba(255,255,255,0.45);">United Kingdom · 5 months ago</div>
        </div>
        <svg width="16" height="16" viewBox="0 0 24 24" style="margin-left:auto;flex-shrink:0;"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
      </div>
      <div style="color:#FBBC05;margin-bottom:10px;font-size:14px;">★★★★★</div>
      <p style="color:rgba(255,255,255,0.8);font-size:13px;line-height:1.7;">"My wife and I booked the couples package for our anniversary. Professional service, beautiful rooms, and we left feeling completely renewed. Highly recommend!"</p>
    </div>
    <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:24px;">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <div style="width:42px;height:42px;border-radius:50%;background:#FBBC05;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:16px;">Y</div>
        <div>
          <div style="color:#fff;font-weight:600;font-size:14px;">Yuki Tanaka</div>
          <div style="font-size:11px;color:rgba(255,255,255,0.45);">Japan · 2 weeks ago</div>
        </div>
        <svg width="16" height="16" viewBox="0 0 24 24" style="margin-left:auto;flex-shrink:0;"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
      </div>
      <div style="color:#FBBC05;margin-bottom:10px;font-size:14px;">★★★★★</div>
      <p style="color:rgba(255,255,255,0.8);font-size:13px;line-height:1.7;">"The facial treatment uses all natural products and my skin has never felt better. The therapist took time to understand my concerns and customized everything perfectly."</p>
    </div>
    <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(201,169,110,0.3);border-radius:12px;padding:24px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:12px;">
      <svg width="32" height="32" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
      <p style="color:rgba(255,255,255,0.7);font-size:13px;">500+ reviews on Google</p>
      <a href="https://maps.app.goo.gl/xrwVDyNcWYz5zzGH6" target="_blank" style="background:#c9a96e;color:#fff;padding:10px 24px;border-radius:4px;font-size:12px;font-weight:700;letter-spacing:0.08em;text-decoration:none;text-transform:uppercase;">Read All Reviews</a>
    </div>
  </div>
</section>

<!-- BOOKING -->
<section id="booking">
  <div class="section-header fade-up" style="text-align:center;margin-bottom:50px">
    <span class="section-label">Reserve Your Visit</span>
    <h2 class="section-title shine-heading">Book Your <em class="accent">Treatment</em></h2>
    <div class="gold-divider"></div>
  </div>
  <div class="booking-inner fade-up">
    <div class="booking-left">
      <span class="section-label">Get In Touch</span>
      <h2>We are here for you</h2>
      <p>Reach out and our team will confirm your booking within 2 hours. Walk-ins also welcome subject to availability.</p>
      <div class="booking-contact-item">
        <div class="booking-contact-icon"><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.11 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3 2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16.92z"/></svg></div>
        <div class="booking-contact-text"><strong style="color:#fff;display:block;margin-bottom:2px">Phone / WhatsApp</strong>095 993 2861</div>
      </div>
      <div class="booking-contact-item">
        <div class="booking-contact-icon"><svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
        <div class="booking-contact-text"><strong style="color:#fff;display:block;margin-bottom:2px">Email</strong>crystalauramassage@gmail.com</div>
      </div>
      <div class="booking-contact-item">
        <div class="booking-contact-icon"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
        <div class="booking-contact-text"><strong style="color:#fff;display:block;margin-bottom:2px">Address</strong>22 Nimmana Haeminda Rd Lane 13,<br>Suthep, Mueang Chiang Mai 50200</div>
      </div>
      <div class="booking-contact-item">
        <div class="booking-contact-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div class="booking-contact-text"><strong style="color:#fff;display:block;margin-bottom:2px">Hours</strong>Mon – Sun: 09:00 AM – 11:30 PM</div>
      </div>
      <div class="booking-confirm">
        <p><strong>Confirmation within 2 hours.</strong> Prefer instant booking? <a href="https://wa.me/66959932861?text=Hi%20Crystal%20Aura%20Spa!%20I%20visited%20your%20website%20and%20I%27d%20like%20to%20enquire%20about%20your%20treatments%20and%20availability.%20Could%20you%20please%20help%20me%3F" style="color:var(--gold);text-decoration:underline" target="_blank">Chat on WhatsApp</a></p>
      </div>
    </div>
    <div class="booking-right">
      <form id="bookingForm">
        <div class="form-row">
          <div class="form-group"><label>Full Name *</label><input type="text" id="f-name" placeholder="Your Name" required></div>
          <div class="form-group"><label>Phone Number *</label><input type="tel" id="f-phone" placeholder="+66 XX XXX XXXX" required></div>
        </div>
        <div class="form-group"><label>Email</label><input type="email" id="f-email" placeholder="your@email.com"></div>
        <div class="form-row">
          <div class="form-group">
            <label>Service *</label>
          <select id="f-service" required>
            <option value="">— Select Treatment —</option>
            <optgroup label="✦ Foot Massage">
              <option value="Foot Massage">Foot Massage</option>
              <option value="Foot Scrub + Foot Reflexology">Foot Scrub + Foot Reflexology</option>
              <option value="Foot Massage + Herbal Ball Hot Compress">Foot Massage + Herbal Ball Hot Compress</option>
              <option value="Foot, Back, Neck &amp; Shoulder">Foot, Back, Neck &amp; Shoulder</option>
              <option value="Rose Quartz Foot Massage">Rose Quartz Foot Massage</option>
            </optgroup>
            <optgroup label="✦ Thai Massage">
              <option value="Thai Traditional Massage">Thai Traditional Massage</option>
              <option value="4-Hands Thai Massage">4-Hands Thai Massage</option>
              <option value="Neck, Back &amp; Shoulder Massage">Neck, Back &amp; Shoulder Massage</option>
              <option value="Thai Balm Massage">Thai Balm Massage</option>
              <option value="Thai Massage + Herbal Ball Compress">Thai Massage + Herbal Ball Compress</option>
              <option value="Thai Massage with Bamboo">Thai Massage with Bamboo</option>
            </optgroup>
            <optgroup label="✦ Aromatherapy (Oil)">
              <option value="4-Hands Aroma Massage">4-Hands Aroma Massage</option>
              <option value="Oil Massage">Oil Massage</option>
              <option value="Aromatherapy Massage">Aromatherapy Massage</option>
              <option value="Aloe Vera Gel / Body Lotion Massage">Aloe Vera Gel / Body Lotion Massage</option>
              <option value="Hot Aromatherapy Massage">Hot Aromatherapy Massage</option>
              <option value="Sports Massage">Sports Massage</option>
              <option value="Deep Tissue Massage">Deep Tissue Massage</option>
              <option value="Virgin Coconut Oil Massage">Virgin Coconut Oil Massage</option>
              <option value="CBD Aroma Massage">CBD Aroma Massage</option>
              <option value="Hot Stone Massage">Hot Stone Massage</option>
              <option value="Aromatherapy + Gua Sha">Aromatherapy + Gua Sha</option>
              <option value="Aromatherapy + Herbal Ball">Aromatherapy + Herbal Ball</option>
              <option value="Aromatherapy + Bamboo Massage">Aromatherapy + Bamboo Massage</option>
              <option value="Rose Quartz Stone Therapy">Rose Quartz Stone Therapy</option>
              <option value="Aroma Oil for Kids">Aroma Oil for Kids</option>
            </optgroup>
            <optgroup label="✦ Head Massage">
              <option value="Migraine Reliever Head Massage">Migraine Reliever Head Massage</option>
              <option value="Indian Head Massage with Coconut Oil">Indian Head Massage with Coconut Oil</option>
              <option value="Ear Cleaning &amp; Spa">Ear Cleaning &amp; Spa</option>
            </optgroup>
            <optgroup label="✦ Facial Treatment">
              <option value="Anti Aging Facial Massage (Gua Sha)">Anti Aging Facial Massage (Gua Sha)</option>
              <option value="Facial Massage with Charcoal Mask (Blackhead Removal)">Facial Massage with Charcoal Mask (Blackhead Removal)</option>
              <option value="Rose Quartz Gua Sha Facial">Rose Quartz Gua Sha Facial</option>
            </optgroup>
            <optgroup label="✦ Body Scrub">
              <option value="Body Scrub">Body Scrub</option>
              <option value="Himalayan Salt Scrub">Himalayan Salt Scrub</option>
            </optgroup>
            <optgroup label="★ Packages">
              <option value="Thai Bliss">Thai Bliss</option>
              <option value="Soothing Retreat">Soothing Retreat</option>
              <option value="Ultimate Rejuvenation">Ultimate Rejuvenation</option>
              <option value="Relaxation Oasis">Relaxation Oasis</option>
              <option value="Revitalizing Glow">Revitalizing Glow</option>
              <option value="Head and Foot Relief">Head and Foot Relief</option>
              <option value="Foot Bliss">Foot Bliss</option>
              <option value="Rejuvenation Skin">Rejuvenation Skin</option>
              <option value="Crystal Harmony">Crystal Harmony</option>
              <option value="Golden Bliss">Golden Bliss</option>
              <option value="Serene Wellness Retreat">Serene Wellness Retreat</option>
              <option value="Harmonious Spa Escape">Harmonious Spa Escape</option>
              <option value="Tranquil Spa Sanctuary">Tranquil Spa Sanctuary</option>
              <option value="Couple's Retreat Package">Couple's Retreat Package</option>
              <option value="Full Day Bliss Package">Full Day Bliss Package</option>
              <option value="Ultimate Detox Package">Ultimate Detox Package</option>
              <option value="Royal Thai Package">Royal Thai Package</option>
            </optgroup>
          </select>
          </div>
          <div class="form-group">
            <label>Duration *</label>
            <select id="f-duration" required>
              <option value="">— Select Duration —</option>
              <option value="60 min">60 Minutes</option>
              <option value="90 min">90 Minutes</option>
              <option value="120 min">120 Minutes</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Preferred Date *</label><input type="date" id="f-date" required></div>
          <div class="form-group">
            <label>Preferred Time *</label>
            <select id="f-time" required>
              <option value="">— Select Time —</option>
              <option>09:00 AM</option><option>09:30 AM</option><option>10:00 AM</option><option>10:30 AM</option>
              <option>11:00 AM</option><option>11:30 AM</option><option>12:00 PM</option><option>12:30 PM</option>
              <option>01:00 PM</option><option>01:30 PM</option><option>02:00 PM</option><option>02:30 PM</option>
              <option>03:00 PM</option><option>03:30 PM</option><option>04:00 PM</option><option>04:30 PM</option>
              <option>05:00 PM</option><option>05:30 PM</option><option>06:00 PM</option><option>06:30 PM</option>
              <option>07:00 PM</option><option>07:30 PM</option><option>08:00 PM</option><option>08:30 PM</option>
              <option>09:00 PM</option><option>09:30 PM</option><option>10:00 PM</option><option>10:30 PM</option>
              <option>11:00 PM</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Number of People</label>
          <select id="f-people">
            <option>1 Person</option><option>2 People</option><option>3 People</option><option>4 People</option>
          </select>
        </div>
        <div class="form-group"><label>Special Requests</label><textarea id="f-notes" placeholder="Allergies, preferences, special occasions..."></textarea></div>
        <div class="form-btns">
          <button type="button" class="btn btn-gold" id="btnWA" style="flex:1;background:linear-gradient(135deg,#25D366,#1aad52);border-color:#25D366;">Book via WhatsApp</button>
          <button type="button" class="btn btn-gold" id="btnLine" style="flex:1;background:linear-gradient(135deg,#06C755,#03a847);border-color:#06C755;">Book via LINE</button>
          <button type="button" class="btn btn-outline" id="btnEmail" style="flex:1">Send Email Request</button>
        </div>
      </form>
    </div>
  </div>
</section>

<!-- PAYMENT METHODS -->
<section id="payment-methods">

  <!-- Policy Banner -->
  <div class="payment-policy-banner">
    <p class="pay-policy-label">How Payment Works</p>
    <div class="pay-flow">
      <!-- Step 1 -->
      <div class="pay-step pay-step-1">
        <div class="pay-step-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#c9a96e" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div class="pay-step-title">Book Online</div>
        <div class="pay-step-desc">Choose your treatment &amp; preferred time</div>
      </div>
      <!-- Arrow -->
      <div class="pay-arrow">›</div>
      <!-- Step 2 -->
      <div class="pay-step pay-step-2">
        <div class="pay-step-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="1.8"><path d="M9 12l2 2 4-4"/><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/></svg>
        </div>
        <div class="pay-step-title">No Upfront Payment</div>
        <div class="pay-step-desc">Reserve your spot — no card, no deposit</div>
      </div>
      <!-- Arrow -->
      <div class="pay-arrow">›</div>
      <!-- Step 3 -->
      <div class="pay-step pay-step-3">
        <div class="pay-step-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#c9a96e" stroke-width="1.8"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        </div>
        <div class="pay-step-title">Pay at the Spa</div>
        <div class="pay-step-desc">Just pay when you arrive, before your session</div>
      </div>
    </div>
    <!-- Typing tagline -->
    <div class="pay-typing-wrap">
      <span class="pay-typing" id="payTyping"></span><span class="pay-typing-cursor"></span>
    </div>
  </div>

  <!-- Header -->
  <div class="payment-header">
    <span class="section-label">Payments</span>
    <h3>Accepted Payment <em class="accent">Methods</em></h3>
    <div class="gold-divider"></div>
    <p>No online payment needed — simply pay at our spa before your treatment begins. We accept all major payment methods for your convenience.</p>
  </div>

  <div class="payment-rows">
    <!-- Row 1: Cash · PromptPay · Alipay+ · WeChat Pay -->
    <div class="payment-row-1">

      <!-- CASH THB -->
      <div class="pm-card" style="--brand-color:#2e7d32">
        <div class="pm-logo">
          <div style="width:54px;height:54px;background:linear-gradient(135deg,#2e7d32,#43a047);border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(46,125,50,0.3);">
            <span style="font-size:28px;font-weight:900;color:#fff;font-family:Arial,sans-serif;line-height:1;">฿</span>
          </div>
        </div>
        <div class="pm-title" style="color:#2e7d32;">Cash (THB)</div>
        <div class="pm-sub">Pay in Thai Baht</div>
      </div>

      <!-- PROMPTPAY -->
      <div class="pm-card" style="--brand-color:#5D2B8C">
        <div class="pm-logo">
          <div style="background:linear-gradient(135deg,#4a1e7a,#1565C0);border-radius:10px;padding:9px 16px;display:inline-block;">
            <span style="font-family:Arial,sans-serif;font-size:15px;font-weight:900;color:#fff;letter-spacing:-0.5px;">Prompt</span><span style="font-family:Arial,sans-serif;font-size:15px;font-weight:900;color:#1DE9B6;letter-spacing:-0.5px;">Pay</span>
          </div>
        </div>
        <div class="pm-title" style="color:#5D2B8C;">PromptPay</div>
        <div class="pm-sub">Thai QR Payment</div>
      </div>

      <!-- ALIPAY+ -->
      <div class="pm-card" style="--brand-color:#1677FF">
        <div class="pm-logo">
          <div style="width:54px;height:54px;background:#1677FF;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto;box-shadow:0 4px 14px rgba(22,119,255,0.3);">
            <span style="font-size:22px;font-weight:900;color:#fff;font-family:Arial,sans-serif;line-height:1;letter-spacing:-1px;">Ali<br><span style="font-size:13px;letter-spacing:0.05em;font-weight:700;">Pay+</span></span>
          </div>
        </div>
        <div class="pm-title" style="color:#1677FF;">Alipay+</div>
        <div class="pm-sub">Alipay Plus</div>
      </div>

      <!-- WECHAT PAY -->
      <div class="pm-card" style="--brand-color:#07C160">
        <div class="pm-logo">
          <svg width="54" height="54" viewBox="0 0 54 54">
            <rect width="54" height="54" rx="12" fill="#07C160"/>
            <ellipse cx="22" cy="22" rx="13" ry="10" fill="white"/>
            <ellipse cx="33" cy="33" rx="13" ry="10" fill="rgba(255,255,255,0.8)"/>
            <circle cx="17" cy="22" r="2" fill="#07C160"/>
            <circle cx="22" cy="22" r="2" fill="#07C160"/>
            <circle cx="27" cy="22" r="2" fill="#07C160"/>
            <circle cx="28" cy="33" r="2" fill="rgba(7,193,96,0.85)"/>
            <circle cx="33" cy="33" r="2" fill="rgba(7,193,96,0.85)"/>
            <circle cx="38" cy="33" r="2" fill="rgba(7,193,96,0.85)"/>
          </svg>
        </div>
        <div class="pm-title" style="color:#07C160;">WeChat Pay</div>
        <div class="pm-sub">Mobile Payment</div>
      </div>

    </div>

    <!-- Row 2: Visa · Mastercard · Apple Pay -->
    <div class="payment-row-2">

      <!-- VISA -->
      <div class="pm-card" style="--brand-color:#1a1f71">
        <div class="pm-logo">
          <span style="font-size:42px;font-weight:900;font-style:italic;color:#1a1f71;font-family:Arial,sans-serif;letter-spacing:-2px;line-height:1;">VISA</span>
        </div>
        <div class="pm-title" style="color:#1a1f71;">Visa</div>
        <div class="pm-sub">Credit / Debit Card</div>
      </div>

      <!-- MASTERCARD -->
      <div class="pm-card" style="--brand-color:#EB001B">
        <div class="pm-logo">
          <div style="position:relative;width:72px;height:44px;margin:0 auto;">
            <div style="position:absolute;left:0;top:2px;width:42px;height:42px;background:#EB001B;border-radius:50%;"></div>
            <div style="position:absolute;right:0;top:2px;width:42px;height:42px;background:#F79E1B;border-radius:50%;mix-blend-mode:multiply;"></div>
          </div>
        </div>
        <div class="pm-title" style="color:#333;font-family:Arial,sans-serif;font-weight:600;letter-spacing:0.01em;">mastercard</div>
        <div class="pm-sub">Credit / Debit Card</div>
      </div>

      <!-- APPLE PAY -->
      <div class="pm-card" style="--brand-color:#000">
        <div class="pm-logo">
          <div style="background:#000;border-radius:10px;padding:9px 22px;display:inline-flex;align-items:center;gap:7px;">
            <svg width="16" height="20" viewBox="0 0 16 20" fill="white"><path d="M13.196 10.645c-.022-2.075 1.694-3.074 1.77-3.12-.966-1.41-2.466-1.604-3.001-1.623-1.273-.13-2.497.752-3.144.752-.647 0-1.644-.735-2.705-.715C4.55 5.96 2.989 6.836 2.122 8.238.381 11.068 1.68 15.329 3.355 17.658c.84 1.191 1.824 2.52 3.119 2.472 1.258-.051 1.73-.8 3.25-.8 1.52 0 1.95.8 3.283.776 1.352-.025 2.203-1.208 3.03-2.41.956-1.383 1.349-2.726 1.37-2.796-.03-.012-2.624-.999-2.646-3.966zm-2.474-7.294c.697-.844 1.167-2.013 1.039-3.18-.984.041-2.175.654-2.88 1.48-.633.73-1.19 1.9-1.04 3.02 1.082.083 2.185-.55 2.88-1.32z"/></svg>
            <span style="font-family:-apple-system,BlinkMacSystemFont,Arial,sans-serif;font-size:19px;font-weight:500;color:#fff;letter-spacing:-0.3px;">Pay</span>
          </div>
        </div>
        <div class="pm-title" style="color:#000;">Apple Pay</div>
        <div class="pm-sub">Mobile Payment</div>
      </div>

    </div>
  </div>

  <!-- Footer note -->
  <div class="payment-thanks">
    <svg width="17" height="17" viewBox="0 0 24 24" fill="var(--gold)"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
    Thank you for your understanding
  </div>

</section>

<!-- TRUST BADGES -->
<section id="trust">
  <div class="trust-inner">
    <div class="trust-badge fade-up">
      <div class="trust-icon-wrap">
        <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      </div>
      <h4>Certified Therapists</h4>
      <p>Professionally trained &amp; certified</p>
    </div>
    <div class="trust-badge fade-up" style="transition-delay:0.1s">
      <div class="trust-icon-wrap">
        <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
      </div>
      <h4>100% Natural Products</h4>
      <p>Locally sourced Thai herbs &amp; oils</p>
    </div>
    <div class="trust-badge fade-up" style="transition-delay:0.2s">
      <div class="trust-icon-wrap">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="7"/><path d="M8.21 13.89L7 23l5-3 5 3-1.21-9.12"/></svg>
      </div>
      <h4>Award-Winning Spa</h4>
      <p>Recognised excellence since 2022</p>
    </div>
    <div class="trust-badge fade-up" style="transition-delay:0.3s">
      <div class="trust-icon-wrap">
        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <h4>5,000+ Happy Clients</h4>
      <p>Trusted by guests worldwide</p>
    </div>
    <div class="trust-badge fade-up" style="transition-delay:0.4s">
      <div class="trust-icon-wrap">
        <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
      </div>
      <h4>Secure Booking</h4>
      <p>Easy booking, flexible scheduling</p>
    </div>
  </div>
</section>

<!-- CONTACT + MAP -->
<section id="contact">
  <div class="contact-inner">
    <div class="fade-up">
      <span class="section-label">Find Us</span>
      <h2>Contact & <em class="accent">Location</em></h2>
      <div class="gold-divider left"></div>
      <div class="contact-detail">
        <div class="contact-detail-icon"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
        <div class="contact-detail-text"><h4>Address</h4><p>22 Nimmana Haeminda Rd Lane 13,<br>Suthep, Mueang Chiang Mai 50200,<br>Thailand</p></div>
      </div>
      <div class="contact-detail">
        <div class="contact-detail-icon"><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.11 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3 2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16.92z"/></svg></div>
        <div class="contact-detail-text"><h4>Phone</h4><p><a href="tel:0959932861" style="color:var(--text-light)">095 993 2861</a></p></div>
      </div>
      <div class="contact-detail">
        <div class="contact-detail-icon"><svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
        <div class="contact-detail-text"><h4>Email</h4><p><a href="mailto:crystalauramassage@gmail.com" style="color:var(--text-light)">crystalauramassage@gmail.com</a></p></div>
      </div>
      <div class="contact-detail">
        <div class="contact-detail-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div class="contact-detail-text"><h4>Opening Hours</h4><p>Monday – Sunday<br>09:00 AM – 11:30 PM</p></div>
      </div>
    </div>
    <div class="contact-map fade-up" style="transition-delay:0.2s">
      <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3775.557404857282!2d98.96531247620394!3d18.798631366577405!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x30da3a3d8f8f0001%3A0x1234567890abcdef!2s22%20Nimmana%20Haeminda%20Rd%2C%20Suthep%2C%20Chiang%20Mai%2050200%2C%20Thailand!5e0!3m2!1sen!2sth!4v1717151400000" width="100%" height="400" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Crystal Aura Spa Chiang Mai Location"></iframe>
    </div>
  </div>
</section>

<!-- QR BOOKING CHANNELS — FULL WIDTH -->
<section id="qr-channels" style="padding:80px 40px;background:linear-gradient(180deg,#faf9f7 0%,#f5f0e8 100%);position:relative;overflow:hidden;">

  <!-- Background decoration -->
  <div style="position:absolute;top:-80px;right:-80px;width:320px;height:320px;background:radial-gradient(circle,rgba(212,175,55,0.08),transparent 70%);pointer-events:none;"></div>
  <div style="position:absolute;bottom:-60px;left:-60px;width:260px;height:260px;background:radial-gradient(circle,rgba(212,175,55,0.06),transparent 70%);pointer-events:none;"></div>

  <div style="max-width:1200px;margin:0 auto;position:relative;">

    <!-- Section heading -->
    <div style="text-align:center;margin-bottom:52px;">
      <span style="font-size:11px;letter-spacing:0.25em;text-transform:uppercase;color:var(--gold);font-weight:700;display:block;margin-bottom:14px">Connect With Us</span>
      <h2 style="font-family:var(--font-serif);font-size:2.8rem;font-weight:600;color:#2c1810;letter-spacing:-0.02em;margin-bottom:14px;line-height:1.2">Message us on your <em class="accent">favorite app.</em></h2>
      <div style="width:50px;height:2px;background:linear-gradient(90deg,transparent,var(--gold),transparent);margin:0 auto 18px;"></div>
      <p style="font-size:15px;color:var(--text-light);line-height:1.8;max-width:520px;margin:0 auto">Whether you're local or traveling, scan any QR code or tap a card to connect with us instantly.</p>
    </div>

    <style>
      .booking-cards-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 20px;
        width: 100%;
      }
      @media (max-width: 1024px) { .booking-cards-grid { grid-template-columns: repeat(3, 1fr); gap:16px; } }
      @media (max-width: 580px)  { .booking-cards-grid { grid-template-columns: repeat(2, 1fr); gap:12px; } }

      .booking-card {
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 24px rgba(0,0,0,0.07);
        transition: transform 0.35s cubic-bezier(0.23,1,0.32,1), box-shadow 0.35s ease;
        background: #fff;
        display: flex;
        flex-direction: column;
        border: 1px solid rgba(212,175,55,0.12);
      }
      .booking-card:hover {
        transform: translateY(-12px);
        box-shadow: 0 24px 50px rgba(212,175,55,0.2);
        border-color: rgba(212,175,55,0.4);
      }
      .booking-card-header {
        padding: 18px 12px;
        color: #fff;
        font-weight: 700;
        font-size: 14px;
        letter-spacing: 0.07em;
        background: linear-gradient(135deg, var(--color1), var(--color2));
        text-align: center;
        position: relative;
        overflow: hidden;
      }
      .booking-card-header::after {
        content: '';
        position: absolute;
        top: 0; left: -100%;
        width: 60%; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        animation: shimmer 3s infinite;
      }
      @keyframes shimmer { 0% { left:-100%; } 100% { left:200%; } }

      .booking-card-content {
        padding: 18px 16px 22px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
      }
      .dummy-qr {
        width: 100%;
        aspect-ratio: 1;
        background: #fff;
        border-radius: 10px;
        border: 1.5px solid #eee;
        overflow: hidden;
        margin-bottom: 12px;
        transition: border-color 0.3s, box-shadow 0.3s;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      }
      .dummy-qr img {
        width: 100%; height: 100%;
        display: block; object-fit: contain;
      }
      .booking-card:hover .dummy-qr {
        border-color: var(--gold);
        box-shadow: 0 4px 16px rgba(212,175,55,0.15);
      }
      .booking-card-scan {
        font-size: 9px; color: var(--gold); font-weight: 700;
        margin-bottom: 10px; letter-spacing: 0.18em; text-transform: uppercase;
      }
      .booking-card-info {
        font-size: 12px; color: #1a1a1a; font-weight: 700;
        margin-bottom: 5px; line-height: 1.4;
      }
      .booking-card-desc {
        font-size: 11px; color: var(--text-light);
        margin-bottom: 16px; line-height: 1.6; flex: 1;
      }
      .booking-card-btn {
        display: block; color: var(--gold); font-size: 11px;
        font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
        text-decoration: none; padding: 10px 14px;
        border: 1.5px solid var(--gold); border-radius: 8px;
        transition: all 0.3s; background: transparent;
        width: 100%; box-sizing: border-box;
      }
      .booking-card:hover .booking-card-btn {
        background: var(--gold); color: #fff;
        box-shadow: 0 4px 16px rgba(212,175,55,0.35);
      }

      /* ---- Contact Info Strip ---- */
      .contact-strip {
        margin-top: 56px;
        background: var(--dark);
        border-radius: 20px;
        padding: 0;
        overflow: hidden;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        position: relative;
      }
      @media (max-width: 768px) {
        .contact-strip { grid-template-columns: repeat(2, 1fr); border-radius: 16px; }
        .contact-strip-item { padding: 24px 14px; }
        .contact-strip-item:not(:last-child)::after { display: none; }
        .contact-strip-item:nth-child(1),
        .contact-strip-item:nth-child(2) {
          border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .contact-strip-item:nth-child(odd) {
          border-right: 1px solid rgba(255,255,255,0.07);
        }
        .contact-strip-value { font-size: 13px; white-space: nowrap; }
        .contact-strip-icon { width: 40px; height: 40px; font-size: 17px; margin-bottom: 10px; }
        .contact-strip-label { font-size: 9px; margin-bottom: 6px; }
        .contact-strip-sub { font-size: 10px; }
      }
      @media (max-width: 380px) {
        .contact-strip { grid-template-columns: 1fr; }
        .contact-strip-item:nth-child(odd) { border-right: none; }
        .contact-strip-item { border-bottom: 1px solid rgba(255,255,255,0.07) !important; }
      }

      .contact-strip-item {
        padding: 36px 24px;
        text-align: center;
        position: relative;
        transition: background 0.3s;
        cursor: default;
      }
      .contact-strip-item:not(:last-child)::after {
        content: '';
        position: absolute;
        right: 0; top: 20%; height: 60%;
        width: 1px;
        background: rgba(255,255,255,0.08);
      }
      .contact-strip-item:hover {
        background: rgba(212,175,55,0.08);
      }
      .contact-strip-icon {
        width: 48px; height: 48px;
        background: rgba(212,175,55,0.12);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 14px;
        font-size: 20px;
        border: 1px solid rgba(212,175,55,0.2);
        transition: all 0.3s;
      }
      .contact-strip-item:hover .contact-strip-icon {
        background: rgba(212,175,55,0.25);
        border-color: var(--gold);
        transform: scale(1.1);
      }
      .contact-strip-label {
        font-size: 10px; letter-spacing: 0.2em; text-transform: uppercase;
        color: var(--gold); font-weight: 700; margin-bottom: 8px;
      }
      .contact-strip-value {
        font-size: 15px; color: #fff; font-weight: 600; line-height: 1.5;
      }
      .contact-strip-sub {
        font-size: 11px; color: rgba(255,255,255,0.4); margin-top: 4px;
      }
    </style>

    <!-- QR Cards Grid -->
    <div class="booking-cards-grid">
      <div class="booking-card" style="--color1:#00C300;--color2:#00A100">
        <div class="booking-card-header">💬 LINE</div>
        <div class="booking-card-content">
          <div class="dummy-qr"><img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=https://line.me/R/ti/p/@crystalauraspa&margin=6" alt="LINE QR"></div>
          <div class="booking-card-scan">📱 SCAN ME</div>
          <div class="booking-card-info">ID: @crystalauraspa</div>
          <div class="booking-card-desc">Our main Thai chat channel</div>
          <a href="https://line.me/R/ti/p/@crystalauraspa" target="_blank" class="booking-card-btn">ADD FRIEND →</a>
        </div>
      </div>
      <div class="booking-card" style="--color1:#25D366;--color2:#20BA5A">
        <div class="booking-card-header">💬 WhatsApp</div>
        <div class="booking-card-content">
          <div class="dummy-qr"><img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=https://wa.me/66959932861&margin=6" alt="WhatsApp QR"></div>
          <div class="booking-card-scan">📱 SCAN ME</div>
          <div class="booking-card-info">+66 95 993 2861</div>
          <div class="booking-card-desc">Fastest reply in English & Thai</div>
          <a href="https://wa.me/66959932861?text=Hi%20Crystal%20Aura%20Spa!%20I%20visited%20your%20website%20and%20I%27d%20like%20to%20enquire%20about%20your%20treatments%20and%20availability.%20Could%20you%20please%20help%20me%3F" target="_blank" class="booking-card-btn">START CHAT →</a>
        </div>
      </div>
      <div class="booking-card" style="--color1:#1877F2;--color2:#0A66C2">
        <div class="booking-card-header">f Facebook</div>
        <div class="booking-card-content">
          <div class="dummy-qr"><img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=https://www.facebook.com/crystalauraspa&margin=6" alt="Facebook QR"></div>
          <div class="booking-card-scan">📱 SCAN ME</div>
          <div class="booking-card-info">Crystal Aura Massage & Spa</div>
          <div class="booking-card-desc">Find us on Facebook</div>
          <a href="https://www.facebook.com/crystalauraspa" target="_blank" class="booking-card-btn">OPEN PAGE →</a>
        </div>
      </div>
      <div class="booking-card" style="--color1:#f09433;--color2:#d46f35">
        <div class="booking-card-header">📷 Instagram</div>
        <div class="booking-card-content">
          <div class="dummy-qr"><img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=https://www.instagram.com/crystalauraspa&margin=6" alt="Instagram QR"></div>
          <div class="booking-card-scan">📱 SCAN ME</div>
          <div class="booking-card-info">@crystalaura.cnx</div>
          <div class="booking-card-desc">Follow for updates & offers</div>
          <a href="https://www.instagram.com/crystalauraspa" target="_blank" class="booking-card-btn">FOLLOW →</a>
        </div>
      </div>
      <div class="booking-card" style="--color1:#010101;--color2:#1a1a1a">
        <div class="booking-card-header">🎵 TikTok</div>
        <div class="booking-card-content">
          <div class="dummy-qr"><img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=https://www.tiktok.com/@crystalauraspa&margin=6" alt="TikTok QR"></div>
          <div class="booking-card-scan">📱 SCAN ME</div>
          <div class="booking-card-info">@crystalauraspa</div>
          <div class="booking-card-desc">Wellness tips & spa vibes</div>
          <a href="https://www.tiktok.com/@crystalauraspa" target="_blank" class="booking-card-btn">FOLLOW →</a>
        </div>
      </div>
    </div>

    <!-- Beautiful Contact Strip -->
    <div class="contact-strip">
      <div class="contact-strip-item">
        <div class="contact-strip-icon">📞</div>
        <div class="contact-strip-label">Phone</div>
        <div class="contact-strip-value">095 993 2861</div>
        <div class="contact-strip-sub">Call us anytime</div>
      </div>
      <div class="contact-strip-item">
        <div class="contact-strip-icon">✉️</div>
        <div class="contact-strip-label">Email</div>
        <div class="contact-strip-value" style="font-size:13px">crystalauramassage@gmail.com</div>
        <div class="contact-strip-sub">We reply within 2 hrs</div>
      </div>
      <div class="contact-strip-item">
        <div class="contact-strip-icon">🕐</div>
        <div class="contact-strip-label">Opening Hours</div>
        <div class="contact-strip-value">09:00 – 24:00</div>
        <div class="contact-strip-sub">Open every day</div>
      </div>
      <div class="contact-strip-item">
        <div class="contact-strip-icon">📍</div>
        <div class="contact-strip-label">Address</div>
        <div class="contact-strip-value" style="font-size:13px">22 Nimman Lane 13</div>
        <div class="contact-strip-sub">Nimman, Chiang Mai</div>
      </div>
    </div>

  </div>
</section>

<!-- FOOTER -->
<footer id="footer">
  <div class="f2-wrap">

    <!-- Brand -->
    <div class="f2-brand">
      <div class="f2-lotus">
        <svg width="120" height="100" viewBox="0 0 120 100" fill="none">
          <path d="M60 8 L63 16 L71 18 L63 20 L60 28 L57 20 L49 18 L57 16 Z" fill="#c9a96e"/>
          <path d="M24 88 A 42 42 0 1 1 96 88" stroke="#c9a96e" stroke-width="2.5" fill="none" stroke-linecap="round"/>
          <path d="M60 42 C 52 56 52 68 60 76 C 68 68 68 56 60 42 Z" fill="rgba(238,190,194,0.85)" stroke="#c9a96e" stroke-width="2"/>
          <path d="M44 50 C 40 62 46 72 60 76 C 56 64 52 56 44 50 Z" fill="rgba(238,190,194,0.7)" stroke="#c9a96e" stroke-width="2"/>
          <path d="M76 50 C 80 62 74 72 60 76 C 64 64 68 56 76 50 Z" fill="rgba(238,190,194,0.7)" stroke="#c9a96e" stroke-width="2"/>
          <path d="M30 60 C 30 70 40 77 60 76 C 48 70 38 66 30 60 Z" fill="rgba(238,190,194,0.55)" stroke="#c9a96e" stroke-width="2"/>
          <path d="M90 60 C 90 70 80 77 60 76 C 72 70 82 66 90 60 Z" fill="rgba(238,190,194,0.55)" stroke="#c9a96e" stroke-width="2"/>
        </svg>
      </div>
      <h3 class="f2-title">Crystal <span>Aura</span> Massage &amp; Spa</h3>
      <div class="f2-divider"><span class="f2-diamond">&#9670;</span><span class="f2-line"></span><span class="f2-diamond">&#9670;</span></div>
      <p class="f2-tagline">A sanctuary of authentic Thai wellness nestled in the heart of Nimman, Chiang Mai. Your journey to inner peace begins here.</p>
    </div>

    <!-- Social -->
    <div class="f2-social">
      <a href="https://www.facebook.com/crystalauraspa" target="_blank" aria-label="Facebook">
        <span class="f2-soc-circle"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></span>
        <span class="f2-soc-label">Facebook</span>
      </a>
      <a href="https://www.instagram.com/crystalauraspa" target="_blank" aria-label="Instagram">
        <span class="f2-soc-circle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg></span>
        <span class="f2-soc-label">Instagram</span>
      </a>
      <a href="https://wa.me/66959932861" target="_blank" aria-label="WhatsApp">
        <span class="f2-soc-circle"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></span>
        <span class="f2-soc-label">WhatsApp</span>
      </a>
      <a href="https://line.me/R/ti/p/@crystalauraspa" target="_blank" aria-label="LINE">
        <span class="f2-soc-circle"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg></span>
        <span class="f2-soc-label">LINE</span>
      </a>
      <a href="https://www.tiktok.com/@crystalauraspa" target="_blank" aria-label="TikTok">
        <span class="f2-soc-circle"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.34 6.34 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.18 8.18 0 004.78 1.52V6.76a4.85 4.85 0 01-1.01-.07z"/></svg></span>
        <span class="f2-soc-label">TikTok</span>
      </a>
    </div>

    <!-- Quick Links -->
    <div class="f2-section-head">
      <span class="f2-head-line"></span>
      <span class="f2-head-label"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg> QUICK LINKS</span>
      <span class="f2-head-line"></span>
    </div>
    <div class="f2-links-card">
      <a href="#hero" class="f2-link"><span class="f2-link-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>Home<span class="f2-chev">&#8250;</span></a>
      <a href="#pricing" class="f2-link"><span class="f2-link-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 3a9 9 0 0 1 9 9c0 4-3 6-5 7l-4 2-4-2c-2-1-5-3-5-7a9 9 0 0 1 9-9z"/></svg></span>Services<span class="f2-chev">&#8250;</span></a>
      <a href="#packages" class="f2-link"><span class="f2-link-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg></span>Packages<span class="f2-chev">&#8250;</span></a>
      <a href="#signature" class="f2-link"><span class="f2-link-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.5 5L19 9.5 13.5 11 12 16l-1.5-5L5 9.5 10.5 8z"/></svg></span>Signature Treatment<span class="f2-chev">&#8250;</span></a>
      <a href="#gallery" class="f2-link"><span class="f2-link-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></span>Gallery<span class="f2-chev">&#8250;</span></a>
      <a href="#testimonials" class="f2-link"><span class="f2-link-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg></span>Reviews<span class="f2-chev">&#8250;</span></a>
      <a href="#booking" class="f2-link"><span class="f2-link-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>Book Now<span class="f2-chev">&#8250;</span></a>
      <a href="#trust" class="f2-link"><span class="f2-link-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></span>Why Us<span class="f2-chev">&#8250;</span></a>
      <a href="#packages" class="f2-link"><span class="f2-link-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg></span>Special Offers<span class="f2-chev">&#8250;</span></a>
      <a href="#contact" class="f2-link"><span class="f2-link-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>Contact<span class="f2-chev">&#8250;</span></a>
    </div>

    <!-- Contact Us -->
    <div class="f2-section-head">
      <span class="f2-head-line"></span>
      <span class="f2-head-label"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg> CONTACT US</span>
      <span class="f2-head-line"></span>
    </div>
    <div class="f2-contact-card">
      <a href="tel:0959932861" class="f2-contact-row"><span class="f2-contact-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>095 993 2861</a>
      <a href="mailto:crystalauramassage@gmail.com" class="f2-contact-row"><span class="f2-contact-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>crystalauramassage@gmail.com</a>
      <div class="f2-contact-row"><span class="f2-contact-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>22 Nimman Lane 13, Chiang Mai</div>
      <div class="f2-contact-row"><span class="f2-contact-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>Open Daily 09:00 &ndash; 23:30</div>
    </div>

    <!-- Copyright -->
    <div class="f2-copy">
      <p>&copy; 2025 Crystal Aura Massage &amp; Spa.</p>
      <p>All rights reserved.</p>
    </div>

  </div>
</footer>

<!-- CART TOAST -->
<div class="cart-toast" id="cartToast"></div>

<!-- SPEED DIAL WIDGET -->
<div id="speedDial">

  <!-- Speed dial items (fan out upward) -->
  <div class="sd-items" id="sdItems">
    <!-- WhatsApp -->
    <a href="https://wa.me/66959932861?text=Hi%20Crystal%20Aura%20Spa!%20I%20visited%20your%20website%20and%20I%27d%20like%20to%20enquire%20about%20your%20treatments%20and%20availability.%20Could%20you%20please%20help%20me%3F" target="_blank" class="sd-item">
      <span class="sd-label">WhatsApp</span>
      <div class="sd-icon" style="background:linear-gradient(135deg,#25D366,#1aad52);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
      </div>
    </a>
    <!-- Phone -->
    <a href="tel:+66959932861" class="sd-item">
      <span class="sd-label">Call Us</span>
      <div class="sd-icon" style="background:linear-gradient(135deg,#0284c7,#0ea5e9);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
      </div>
    </a>
    <!-- TikTok -->
    <a href="https://www.tiktok.com/@crystalauraspa" target="_blank" class="sd-item">
      <span class="sd-label">TikTok</span>
      <div class="sd-icon" style="background:linear-gradient(135deg,#010101,#2a2a2a);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.34 6.34 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.18 8.18 0 004.78 1.52V6.76a4.85 4.85 0 01-1.01-.07z"/></svg>
      </div>
    </a>
    <!-- Instagram -->
    <a href="https://www.instagram.com/crystalauraspa" target="_blank" class="sd-item">
      <span class="sd-label">Instagram</span>
      <div class="sd-icon" style="background:linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
      </div>
    </a>
    <!-- Facebook -->
    <a href="https://www.facebook.com/crystalauraspa" target="_blank" class="sd-item">
      <span class="sd-label">Facebook</span>
      <div class="sd-icon" style="background:linear-gradient(135deg,#1877F2,#0d5ed9);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
      </div>
    </a>
    <!-- LINE -->
    <a href="https://line.me/R/ti/p/@crystalauraspa" target="_blank" class="sd-item">
      <span class="sd-label">LINE</span>
      <div class="sd-icon" style="background:linear-gradient(135deg,#06C755,#03a847);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>
      </div>
    </a>
  </div>

  <!-- Main Support pill button -->
  <button class="sd-main" id="sdMain" onclick="toggleSD()" aria-label="Contact options">
    <!-- ONLINE badge (visible when closed) -->
    <span class="sd-online-badge">
      <span class="sd-online-dot-inner"></span>
      <span class="sd-online-text">Online</span>
    </span>
    <!-- Headset icon (visible when closed) -->
    <span class="sd-support-icon">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="rgba(201,169,110,0.9)"><path d="M12 1C7.03 1 3 5.03 3 10v3c0 1.1.9 2 2 2h1c.55 0 1-.45 1-1v-4c0-.55-.45-1-1-1H5.07C5.56 6.19 8.48 4 12 4s6.44 2.19 6.93 5H17c-.55 0-1 .45-1 1v4c0 .55.45 1 1 1h1v1c0 1.1-.9 2-2 2h-2c0-.55-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1h2c.55 0 1-.45 1-1h2c2.21 0 4-1.79 4-4v-5c0-4.97-4.03-9-9-9z"/></svg>
    </span>
    <!-- Label (visible when closed) -->
    <span class="sd-support-label">Support</span>
    <!-- Close X (visible when open) -->
    <span class="sd-close-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="rgba(255,255,255,0.8)"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
    </span>
  </button>
</div>
<script>
// ===== NAVBAR SCROLL =====
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
  navbar.classList.toggle('scrolled', window.scrollY > 60);
});

// ===== HAMBURGER MENU =====
const hamburger = document.getElementById('hamburger');
const mobileMenu = document.getElementById('mobileMenu');
const mobileClose = document.getElementById('mobileClose');
if(hamburger && mobileMenu && mobileClose) {
  hamburger.addEventListener('click', () => mobileMenu.classList.add('open'));
  mobileClose.addEventListener('click', () => mobileMenu.classList.remove('open'));
  document.querySelectorAll('.mobile-link').forEach(link => {
    link.addEventListener('click', () => mobileMenu.classList.remove('open'));
  });
}

// ===== HERO SLIDER =====
// Set video speed to 0.8x
const heroBgVideo = document.getElementById('heroBgVideo');
if(heroBgVideo) heroBgVideo.playbackRate = 0.8;

// ===== CART =====
let cartService = null;
let lastBookBtn = null;
let toastTimer = null;

function scrollToSection(id) {
  var el = document.getElementById(id);
  if (!el) return;
  var target = (id === 'booking') ? (document.getElementById('f-service') || el) : el;
  var top = target.getBoundingClientRect().top + window.pageYOffset - 80;
  var isMobile = window.innerWidth <= 768;
  if (isMobile) {
    // CSS scroll-behavior:smooth hijacks plain scrollTo(x,y) and the animation
    // can be cancelled on mobile — force an instant jump instead.
    var htmlEl = document.documentElement;
    var prev = htmlEl.style.scrollBehavior;
    htmlEl.style.scrollBehavior = 'auto';
    try { window.scrollTo({top: top, left: 0, behavior: 'instant'}); } catch(e) { window.scrollTo(0, top); }
    htmlEl.style.scrollBehavior = prev;
    // Reveal fade-up content immediately — the scroll observer may lag after an instant jump
    el.querySelectorAll('.fade-up').forEach(function(f){ f.classList.add('visible'); });
  } else {
    try { window.scrollTo({top: top, behavior: 'smooth'}); } catch(e) { window.scrollTo(0, top); }
  }
}

function addToCart(serviceName, btn) {
  // Reset previous selected button
  if (lastBookBtn && lastBookBtn !== btn) {
    lastBookBtn.classList.remove('selected');
    lastBookBtn.textContent = 'Book This →';
  }
  cartService = serviceName;
  lastBookBtn = btn;
  // Update button state
  btn.classList.add('selected');
  btn.textContent = 'Selected!';
  // Show cart badge
  const badge = document.getElementById('cartBadge');
  if (badge) { badge.classList.add('visible'); badge.textContent = '1'; }
  // Show toast
  showCartToast('Added: ' + serviceName);
  // Scroll to booking after short delay and pre-fill service
  setTimeout(() => {
    const bookingSection = document.getElementById('booking');
    if (bookingSection) scrollToSection('booking');
    prefillService(serviceName);
  }, 600);
}

function goToCart() {
  const bookingSection = document.getElementById('booking');
  if (bookingSection) scrollToSection('booking');
  if (cartService) {
    setTimeout(() => prefillService(cartService), 400);
  }
}

function prefillService(name) {
  const sel = document.getElementById('f-service');
  if (!sel) return;
  for (let opt of sel.options) {
    if (opt.text === name || opt.value === name) {
      sel.value = opt.value;
      // Briefly highlight the field
      sel.style.transition = 'box-shadow 0.4s';
      sel.style.boxShadow = '0 0 0 3px rgba(201,169,110,0.5)';
      setTimeout(() => { sel.style.boxShadow = ''; }, 1500);
      break;
    }
  }
}

function showCartToast(msg) {
  const toast = document.getElementById('cartToast');
  if (!toast) return;
  if (toastTimer) clearTimeout(toastTimer);
  toast.textContent = msg;
  toast.classList.add('show');
  toastTimer = setTimeout(() => toast.classList.remove('show'), 2800);
}

// ===== SPEED DIAL =====
function toggleSD() {
  const items = document.getElementById('sdItems');
  const main = document.getElementById('sdMain');
  const isOpen = items.classList.contains('open');
  items.classList.toggle('open');
  main.classList.toggle('open');
}
// Close speed dial when clicking outside
document.addEventListener('click', function(e) {
  const sd = document.getElementById('speedDial');
  const items = document.getElementById('sdItems');
  const main = document.getElementById('sdMain');
  if (sd && !sd.contains(e.target) && items && items.classList.contains('open')) {
    items.classList.remove('open');
    main.classList.remove('open');
  }
});

let currentSlide = 0;
const heroSlides = document.querySelectorAll('#hero .slide');
const heroDots = document.querySelectorAll('.hero-dot');
let heroInterval;

function goToSlide(n) {
  heroSlides[currentSlide].classList.remove('active');
  heroDots[currentSlide].classList.remove('active');
  currentSlide = (n + heroSlides.length) % heroSlides.length;
  heroSlides[currentSlide].classList.add('active');
  heroDots[currentSlide].classList.add('active');
}

function startHeroAuto() {
  heroInterval = setInterval(() => goToSlide(currentSlide + 1), 6000);
}

document.getElementById('heroNext').addEventListener('click', () => { clearInterval(heroInterval); goToSlide(currentSlide + 1); startHeroAuto(); });
document.getElementById('heroPrev').addEventListener('click', () => { clearInterval(heroInterval); goToSlide(currentSlide - 1); startHeroAuto(); });
heroDots.forEach(dot => {
  dot.addEventListener('click', () => { clearInterval(heroInterval); goToSlide(+dot.dataset.slide); startHeroAuto(); });
});
// Ensure only active slide shows on load
heroSlides.forEach((s, i) => { if(i !== 0) s.classList.remove('active'); });

// Touch swipe for hero on mobile
(function(){
  let tx = 0;
  const hero = document.getElementById('hero');
  hero.addEventListener('touchstart', e => { tx = e.changedTouches[0].clientX; }, {passive:true});
  hero.addEventListener('touchend', e => {
    const diff = tx - e.changedTouches[0].clientX;
    if(Math.abs(diff) > 40) {
      clearInterval(heroInterval);
      goToSlide(diff > 0 ? currentSlide + 1 : currentSlide - 1);
      startHeroAuto();
    }
  }, {passive:true});
})();
startHeroAuto();

// ===== PRICING TABS SYSTEM =====
document.querySelectorAll('.pricing-tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const tabName = btn.getAttribute('data-tab');
    document.querySelectorAll('.pricing-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.pricing-tab-content').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-' + tabName).classList.add('active');
  });
});

// ===== PRICING TABLE BOOK BUTTON =====
function bookTreatment(btn) {
  const row = btn.closest('.price-row');
  const name = row.querySelector('.price-row-name').textContent.trim();

  // Auto-select matching option in booking form
  const sel = document.getElementById('f-service');
  let matched = false;
  for (let i = 0; i < sel.options.length; i++) {
    if (sel.options[i].value === name) {
      sel.selectedIndex = i;
      matched = true;
      break;
    }
  }
  // If no exact match, try partial match
  if (!matched) {
    for (let i = 0; i < sel.options.length; i++) {
      if (sel.options[i].value.toLowerCase().includes(name.toLowerCase()) ||
          name.toLowerCase().includes(sel.options[i].value.toLowerCase())) {
        sel.selectedIndex = i;
        break;
      }
    }
  }

  // Scroll to booking section smoothly
  const bookingSection = document.getElementById('booking');
  scrollToSection('booking');

  // Flash highlight on the select to draw attention
  setTimeout(function() {
    sel.style.transition = 'box-shadow 0.3s ease, border-color 0.3s ease';
    sel.style.borderColor = 'var(--gold)';
    sel.style.boxShadow = '0 0 0 3px rgba(201,169,110,0.25)';
    sel.focus();
    setTimeout(function() {
      sel.style.boxShadow = '';
      sel.style.borderColor = '';
    }, 2000);
  }, 700);
}

// ===== TABS =====
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
  });
});


// ===== TESTIMONIALS SLIDER =====
(function() {
  const testiTrack = document.getElementById('testiTrack');
  const testiDotsContainer = document.getElementById('testiDots');
  const testiSlides = document.querySelectorAll('.testi-slide');
  if (!testiTrack || !testiSlides.length) return;
  let testiCurrent = 0;
  let testiInterval;
  if (testiDotsContainer) {
    testiSlides.forEach((_, i) => {
      const dot = document.createElement('button');
      dot.className = 'testi-dot' + (i === 0 ? ' active' : '');
      dot.addEventListener('click', () => { clearInterval(testiInterval); goToTesti(i); startTestiAuto(); });
      testiDotsContainer.appendChild(dot);
    });
  }
  function goToTesti(n) {
    testiCurrent = (n + testiSlides.length) % testiSlides.length;
    testiTrack.style.transform = 'translateX(-' + (testiCurrent * 100) + '%)';
    document.querySelectorAll('.testi-dot').forEach((d, i) => d.classList.toggle('active', i === testiCurrent));
  }
  function startTestiAuto() {
    testiInterval = setInterval(() => goToTesti(testiCurrent + 1), 5000);
  }
  const testiNext = document.getElementById('testiNext');
  const testiPrev = document.getElementById('testiPrev');
  if (testiNext) testiNext.addEventListener('click', () => { clearInterval(testiInterval); goToTesti(testiCurrent + 1); startTestiAuto(); });
  if (testiPrev) testiPrev.addEventListener('click', () => { clearInterval(testiInterval); goToTesti(testiCurrent - 1); startTestiAuto(); });
  startTestiAuto();
})();

// ===== COUNTDOWN =====
function updateCountdown() {
  const now = new Date();
  const nextSunday = new Date(now);
  const day = now.getDay();
  const daysUntilSunday = (7 - day) % 7 || 7;
  nextSunday.setDate(now.getDate() + daysUntilSunday);
  nextSunday.setHours(23, 59, 59, 0);
  const diff = nextSunday - now;
  if (diff <= 0) return;
  const d = Math.floor(diff / 86400000);
  const h = Math.floor((diff % 86400000) / 3600000);
  const m = Math.floor((diff % 3600000) / 60000);
  const s = Math.floor((diff % 60000) / 1000);
  document.getElementById('cd-days').textContent = String(d).padStart(2, '0');
  document.getElementById('cd-hours').textContent = String(h).padStart(2, '0');
  document.getElementById('cd-mins').textContent = String(m).padStart(2, '0');
  document.getElementById('cd-secs').textContent = String(s).padStart(2, '0');
}
updateCountdown();
setInterval(updateCountdown, 1000);

// ===== BOOKING HELPERS =====
function getFormData() {
  return {
    name     : document.getElementById('f-name').value.trim() || 'Guest',
    phone    : document.getElementById('f-phone').value.trim() || 'N/A',
    email    : document.getElementById('f-email').value.trim() || 'N/A',
    service  : document.getElementById('f-service').value || 'N/A',
    duration : document.getElementById('f-duration').value || 'N/A',
    date     : document.getElementById('f-date').value || 'N/A',
    time     : document.getElementById('f-time').value || 'N/A',
    people   : document.getElementById('f-people').value || '1 Person',
    notes    : document.getElementById('f-notes').value.trim() || 'None'
  };
}
function buildBookingMsg(d) {
  const ref = '#CAS-' + Math.floor(100000 + Math.random() * 900000);
  return (
    'Hello Crystal Aura Spa!\n\n' +
    'I would like to confirm my booking.\n\n' +
    '----------------------------------\n' +
    'Booking Ref : ' + ref + '\n' +
    '----------------------------------\n' +
    'Name        : ' + d.name + '\n' +
    'Phone       : ' + d.phone + '\n' +
    (d.email !== 'N/A' ? 'Email       : ' + d.email + '\n' : '') +
    '----------------------------------\n' +
    'Service     : ' + d.service + '\n' +
    'Duration    : ' + d.duration + '\n' +
    'Date        : ' + d.date + '\n' +
    'Time        : ' + d.time + '\n' +
    'Guests      : ' + d.people +
    (d.notes !== 'None' ? '\nNotes       : ' + d.notes : '') + '\n' +
    '----------------------------------\n' +
    'Payment     : Kindly complete payment before the service begins\n' +
    '----------------------------------'
  );
}

// Capture every enquiry to the admin dashboard before handing off to WhatsApp/LINE/email.
// keepalive lets the request finish even while the browser opens the next window.
function captureEnquiry(d, channel) {
  try {
    fetch('capture.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(Object.assign({}, d, {channel: channel})),
      keepalive: true
    }).catch(function(){});
  } catch(e) {}
}

// Book via WhatsApp
document.getElementById('btnWA').addEventListener('click', () => {
  const d = getFormData();
  captureEnquiry(d, 'whatsapp');
  const msg = buildBookingMsg(d);
  window.open('https://wa.me/66959932861?text=' + encodeURIComponent(msg), '_blank');
});

// Book via LINE
document.getElementById('btnLine').addEventListener('click', () => {
  const d = getFormData();
  captureEnquiry(d, 'line');
  const msg = buildBookingMsg(d);
  // LINE supports text pre-fill via oaMessage URL
  window.open('https://line.me/R/oaMessage/@crystalauraspa/?' + encodeURIComponent(msg), '_blank');
});

// Send Email Request
document.getElementById('btnEmail').addEventListener('click', () => {
  const d = getFormData();
  captureEnquiry(d, 'email');
  const subject = 'Booking Request - ' + d.service + ' - ' + d.date;
  const body =
    'Hello Crystal Aura Spa,\n\n' +
    'I would like to request a booking.\n\n' +
    'Name          : ' + d.name + '\n' +
    'Phone         : ' + d.phone + '\n' +
    (d.email !== 'N/A' ? 'Email         : ' + d.email + '\n' : '') +
    'Service       : ' + d.service + '\n' +
    'Duration      : ' + d.duration + '\n' +
    'Date          : ' + d.date + '\n' +
    'Time          : ' + d.time + '\n' +
    'Guests        : ' + d.people + '\n' +
    (d.notes !== 'None' ? 'Special Requests: ' + d.notes + '\n' : '') +
    '\nThank you!';
  window.location.href = 'mailto:crystalauramassage@gmail.com?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
});

// ===== HERO TYPING ANIMATIONS =====
function heroTyper(elId, words, offset) {
  var el = document.getElementById(elId);
  if (!el) return;
  var wi = 0;
  el.textContent = words[0];

  function runCycle() {
    // Delete current text fast
    var txt = el.textContent;
    var i = txt.length;
    (function del() {
      if (i > 0) { el.textContent = txt.slice(0, --i); setTimeout(del, 32); }
      else {
        // Pick next word and type it
        wi = (wi + 1) % words.length;
        var next = words[wi], j = 0;
        setTimeout(function type() {
          el.textContent = next.slice(0, ++j);
          if (j < next.length) setTimeout(type, 68);
          else setTimeout(runCycle, 5000); // hold for 5 sec
        }, 280);
      }
    })();
  }

  // Hold first word for 5s + stagger offset, then start cycling
  setTimeout(runCycle, 5000 + (offset || 0));
}

// Slide 1 — "Discover Your ___"
heroTyper('ht1', ['Inner Peace', 'True Wellness', 'Deep Relaxation', 'Body Balance', 'Pure Serenity'], 0);
// Slide 2 — "Authentic ___"
heroTyper('ht2', ['Thai Wellness', 'Healing Touch', 'Ancient Craft', 'Sacred Ritual', 'Pure Balance'], 700);
// Slide 3 — "A Sanctuary of ___"
heroTyper('ht3', ['Tranquility', 'Pure Bliss', 'Rejuvenation', 'Harmony', 'True Serenity'], 1400);
// About — "A Decade of Thai Wellness ___"
heroTyper('ht4', ['Mastery', 'Excellence', 'Tradition', 'Healing', 'Expertise'], 300);
// Gallery — "A Glimpse of ___"
heroTyper('ht5', ['Serenity', 'Our Spa', 'Elegance', 'Tranquility', 'Pure Luxury'], 1000);
// Reviews — "What Our ___ Say"
heroTyper('ht6', ['Guests', 'Clients', 'Travellers', 'Visitors', 'Wellness Seekers'], 500);

// ===== PAYMENT TYPING ANIMATION =====
(function(){
  const el = document.getElementById('payTyping');
  if (!el) return;
  const phrases = [
    'No credit card required to reserve',
    'Book your spot — completely free',
    'Zero upfront cost. Book with confidence.',
    'Simply pay when you arrive at the spa'
  ];
  let phraseIndex = 0, charIndex = 0, deleting = false;
  function type() {
    const current = phrases[phraseIndex];
    if (!deleting) {
      el.textContent = current.slice(0, charIndex + 1);
      charIndex++;
      if (charIndex === current.length) {
        deleting = true;
        setTimeout(type, 2200);
        return;
      }
      setTimeout(type, 48);
    } else {
      el.textContent = current.slice(0, charIndex - 1);
      charIndex--;
      if (charIndex === 0) {
        deleting = false;
        phraseIndex = (phraseIndex + 1) % phrases.length;
        setTimeout(type, 400);
        return;
      }
      setTimeout(type, 24);
    }
  }
  setTimeout(type, 800);
})();

// ===== INTERSECTION OBSERVER =====
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.1 });

// Only hide elements for animation if IntersectionObserver is supported
if ('IntersectionObserver' in window) {
  document.documentElement.classList.add('js-animate');
  document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));
} else {
  // No observer support — keep everything visible
  document.querySelectorAll('.fade-up').forEach(el => el.classList.add('visible'));
}

// ===== SMOOTH SCROLL OFFSET =====
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', function(e) {
    const target = document.querySelector(this.getAttribute('href'));
    if (!target) return;
    e.preventDefault();
    const offset = 80;
    window.scrollTo({ top: target.offsetTop - offset, behavior: 'smooth' });
  });
});

// PRICE TABLE → GREEN BOOKING BUTTONS + MOBILE CARDS
function initPriceBtns() {
  // Apply green buttons to all table cells (store original value as data attr)
  document.querySelectorAll('.price-table td:not(:first-child):not(.price-dash)').forEach(td => {
    const text = td.textContent.trim();
    if(text && text !== '–' && !td.querySelector('.price-btn')) {
      td.dataset.price = text; // store original number
      td.innerHTML = `<a href="#booking" class="price-btn">${text} THB</a>`;
      td.style.padding = '10px 16px';
    }
  });
  // Build mobile card layout for each panel
  document.querySelectorAll('.tab-panel').forEach(panel => {
    if(panel.querySelector('.price-card-list')) return; // already built
    const table = panel.querySelector('.price-table');
    if(!table) return;
    const headers = Array.from(table.querySelectorAll('th')).map(th => th.textContent.trim());
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const cardList = document.createElement('div');
    cardList.className = 'price-card-list';
    rows.forEach(row => {
      const cells = Array.from(row.querySelectorAll('td'));
      const name = cells[0] ? cells[0].textContent.trim() : '';
      const card = document.createElement('div');
      card.className = 'price-card';
      let dursHtml = '';
      cells.slice(1).forEach((cell, i) => {
        // Use stored data-price (original number) to avoid THB doubling
        const raw = cell.dataset.price || cell.textContent.trim().replace(/\s*THB\s*/gi,'').trim();
        const header = headers[i+1] || '';
        if(raw && raw !== '–' && raw !== '') {
          dursHtml += `<div class="price-card-dur">
            <span class="price-card-min">${header}</span>
            <a href="#booking" class="price-btn">${raw} THB</a>
          </div>`;
        }
      });
      card.innerHTML = `<div class="price-card-name">${name}</div><div class="price-card-durations">${dursHtml}</div>`;
      cardList.appendChild(card);
    });
    panel.appendChild(cardList);
  });
}
// Run immediately + safety fallbacks (function is idempotent)
setTimeout(initPriceBtns, 0);
setTimeout(initPriceBtns, 400);
// Also run after tab switch
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => setTimeout(initPriceBtns, 50));
});

// ===== TAB FUNCTIONALITY WITH SMOOTH SCROLL =====
const tabButtons = document.querySelectorAll('.tab-btn');
const tabPanels = document.querySelectorAll('.tab-panel');

tabButtons.forEach(button => {
  button.addEventListener('click', function(e) {
    e.preventDefault();
    const tabName = this.getAttribute('data-tab');

    // Remove active from all buttons and panels
    tabButtons.forEach(btn => btn.classList.remove('active'));
    tabPanels.forEach(panel => panel.classList.remove('active'));

    // Add active to clicked button and corresponding panel
    this.classList.add('active');
    const activePanel = document.getElementById('tab-' + tabName);
    if(activePanel) {
      activePanel.classList.add('active');

      // Smooth scroll to pricing section
      setTimeout(() => {
        scrollToSection('pricing');
      }, 100);
    }
  });
});

// Add click handlers for navigation links to scroll to sections
document.querySelectorAll('a[href^="#"]').forEach(link => {
  link.addEventListener('click', function(e) {
    const href = this.getAttribute('href');
    if(href !== '#' && href !== '#booking') {
      e.preventDefault();
      const target = document.querySelector(href);
      if(target) {
        var tTop = target.getBoundingClientRect().top + window.pageYOffset - 70; try { window.scrollTo({top:tTop,behavior:'smooth'}); } catch(e){ window.scrollTo(0,tTop); }
      }
    }
  });
});

// ===== DYNAMIC CHANNEL LOADING FROM ADMIN =====
function loadChannelsFromStorage() {
  const defaultChannels = [
    {
      id: 'line',
      name: 'LINE',
      icon: '💬',
      color1: '#00C300',
      color2: '#00A100',
      handle: 'ID: @crystalauraspa',
      description: 'Our main Thai chat channel',
      buttonText: 'ADD FRIEND →',
      url: 'https://line.me/R/ti/p/@crystalauraspa',
      active: true
    },
    {
      id: 'whatsapp',
      name: 'WhatsApp',
      icon: '💬',
      color1: '#25D366',
      color2: '#20BA5A',
      handle: '+66 95 993 2861',
      description: 'Fastest reply in English & Thai',
      buttonText: 'START CHAT →',
      url: 'https://wa.me/66959932861',
      active: true
    },
    {
      id: 'facebook',
      name: 'Facebook',
      icon: 'f',
      color1: '#1877F2',
      color2: '#0A66C2',
      handle: 'Crystal Aura Massage & Spa',
      description: 'Find us on Facebook',
      buttonText: 'OPEN PAGE →',
      url: 'https://www.facebook.com/crystalauraspa',
      active: true
    },
    {
      id: 'instagram',
      name: 'Instagram',
      icon: '📷',
      color1: '#f09433',
      color2: '#d46f35',
      handle: '@crystalaura.cnx',
      description: 'Follow for updates & offers',
      buttonText: 'FOLLOW →',
      url: 'https://www.instagram.com/crystalauraspa',
      active: true
    },
    {
      id: 'tiktok',
      name: 'TikTok',
      icon: '🎵',
      color1: '#010101',
      color2: '#1a1a1a',
      handle: '@crystalauraspa',
      description: 'Wellness tips & spa vibes',
      buttonText: 'FOLLOW →',
      url: 'https://www.tiktok.com/@crystalauraspa',
      active: true
    }
  ];

  const stored = localStorage.getItem('spaChannels');
  return stored ? JSON.parse(stored) : defaultChannels;
}

// ===== REAL QR CODE GENERATOR =====
var qrInstances = {};

function generateRealQRCodes() {
  if (typeof QRCode === 'undefined') {
    console.warn('QRCode library not ready, retrying...');
    setTimeout(generateRealQRCodes, 200);
    return;
  }

  var channels = loadChannelsFromStorage();

  channels.forEach(function(channel) {
    if (!channel.active) return;
    var containerId = 'qr-' + channel.id;
    var container = document.getElementById(containerId);
    if (!container) return;

    // Destroy previous instance if exists
    if (qrInstances[containerId]) {
      try { qrInstances[containerId].clear(); } catch(e) {}
      container.innerHTML = '';
    } else {
      container.innerHTML = '';
    }

    try {
      qrInstances[containerId] = new QRCode(container, {
        text: channel.url,
        width: 130,
        height: 130,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
      });
      console.log('✅ QR generated for ' + channel.name);
    } catch(e) {
      console.error('❌ QR failed for ' + channel.name, e);
    }
  });
}

// Wait for both DOM + library, then generate
window.addEventListener('load', function() {
  var attempts = 0;
  var interval = setInterval(function() {
    attempts++;
    if (typeof QRCode !== 'undefined') {
      clearInterval(interval);
      generateRealQRCodes();
    } else if (attempts > 50) {
      clearInterval(interval);
      console.error('QRCode library never loaded');
    }
  }, 100);
});

// Gallery moved to separate script tag
</script>

<!-- BOOKING MODAL -->
<div id="bookingModal">
  <div class="modal-content">
    <button class="modal-close" onclick="closeBookingModal()">&times;</button>
    <h2 id="modalTitle" style="text-align:center;color:var(--dark);margin-bottom:8px;font-size:24px;font-family:'Cormorant Garamond',serif;letter-spacing:0.02em"></h2>
    <p id="modalSubtitle" style="text-align:center;color:var(--text-light);font-size:13px;margin-bottom:20px"></p>
    <div class="qr-display">
      <div id="qrImage" style="max-width:280px;height:280px;display:flex;align-items:center;justify-content:center;background:#f9f9f9;border-radius:4px;"></div>
    </div>
    <a id="modalAction" href="#" target="_blank" rel="noopener" class="btn btn-gold" style="width:100%;text-align:center;display:block;margin-top:16px"></a>
  </div>
</div>

<script>
const bookingChannels = {
  whatsapp: {
    title: 'WhatsApp',
    subtitle: 'Chat with us instantly on WhatsApp',
    qr: 'svg',
    data: 'https://wa.me/66959932861',
    action: 'https://wa.me/66959932861',
    actionText: 'Open WhatsApp'
  },
  phone: {
    title: 'Call Now',
    subtitle: 'Call us directly to book your treatment',
    qr: 'svg',
    data: 'tel:+66959932861',
    action: 'tel:+66959932861',
    actionText: 'Call +66 95 993 2861'
  },
  facebook: {
    title: 'Facebook',
    subtitle: 'Connect with us on Facebook Messenger',
    qr: 'svg',
    data: 'https://www.facebook.com/crystalauraspa',
    action: 'https://www.facebook.com/crystalauraspa',
    actionText: 'Open Facebook Messenger'
  },
  instagram: {
    title: 'Instagram',
    subtitle: 'DM us on Instagram for bookings',
    qr: 'svg',
    data: 'https://www.instagram.com/crystalauraspa',
    action: 'https://www.instagram.com/crystalauraspa',
    actionText: 'Open Instagram'
  },
  line: {
    title: 'LINE',
    subtitle: 'Add us on LINE and book directly',
    qr: 'svg',
    data: 'https://line.me/ti/p/crystalauraspa',
    action: 'https://line.me/ti/p/crystalauraspa',
    actionText: 'Open LINE'
  },
  tiktok: {
    title: 'TikTok',
    subtitle: 'Follow us on TikTok for updates',
    qr: 'svg',
    data: 'https://www.tiktok.com/@crystalauraspa',
    action: 'https://www.tiktok.com/@crystalauraspa',
    actionText: 'Open TikTok'
  },
  walkin: {
    title: 'Walk-In Welcome',
    subtitle: 'We welcome walk-ins during opening hours',
    qr: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjgwIiBoZWlnaHQ9IjI4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjgwIiBoZWlnaHQ9IjI4MCIgZmlsbD0iI2YwZWJlMyIvPjx0ZXh0IHg9IjUwJSIgeT0iNDUlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LXNpemU9IjY0IiBkeT0iLjNlbSI+8J+alTwvdGV4dD48dGV4dCB4PSI1MCUiIHk9IjU5JSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1zaXplPSIyMCIgZm9udC13ZWlnaHQ9ImJvbGQiIGZpbGw9IiMxQTFBMUEiIGR5PSIuM2VtIj5PcGVuIERhaWx5PC90ZXh0Pjx0ZXh0IHg9IjUwJSIgeT0iNjglIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LXNpemU9IjE2IiBmaWxsPSIjNjY2NjY2IiBkeT0iLjNlbSI+MDk6MDAgQU0gLSAxMTozMCBQTTwvdGV4dD48L3N2Zz4=',
    action: '#',
    actionText: 'See Hours'
  }
};

function openBookingModal(channel) {
  const data = bookingChannels[channel];
  document.getElementById('modalTitle').textContent = data.title;
  document.getElementById('modalSubtitle').textContent = data.subtitle;

  // Generate QR code if data property exists
  if (data.qr === 'svg' && data.data) {
    generateQRCode(data.data);
  } else if (data.qr === 'svg') {
    document.getElementById('qrImage').innerHTML = '<p style="color:#999;">No data available</p>';
  }

  const action = document.getElementById('modalAction');
  action.href = data.action;
  action.textContent = data.actionText;
  document.getElementById('bookingModal').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function generateQRCode(text) {
  // Generate QR code using client-side qrcode.js library (no CORS issues)
  try {
    const qrContainer = document.getElementById('qrImage');
    if (!qrContainer) {
      console.error('QR container not found');
      return;
    }

    // Clear any existing QR code
    qrContainer.innerHTML = '';

    // Generate QR code with qrcode.js library
    new QRCode(qrContainer, {
      text: text,
      width: 280,
      height: 280,
      colorDark: '#000000',
      colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.H
    });
  } catch(e) {
    console.error('QR generation error:', e);
    const qrContainer = document.getElementById('qrImage');
    if (qrContainer) {
      qrContainer.innerHTML = '<div style="width:280px;height:280px;display:flex;align-items:center;justify-content:center;background:#f5f5f5;border:1px solid #ddd;border-radius:4px;">QR Code Generation Failed</div>';
    }
  }
}

function closeBookingModal() {
  document.getElementById('bookingModal').classList.remove('active');
  document.body.style.overflow = 'auto';
}

document.getElementById('bookingModal').addEventListener('click', (e) => {
  if (e.target.id === 'bookingModal') closeBookingModal();
});

// ===== SIMPLE GALLERY LIGHTBOX =====
</script>

<script>
// GALLERY LIGHTBOX — Fixed and tested
(function() {
  console.log('🎨 GALLERY IIFE STARTING');
  var images = [];
  var currentIndex = 0;
  var isOpen = false;

  function collectImages() {
    images = Array.from(document.querySelectorAll('.gallery-item img')).map(function(img) {
      return img.src;
    });
  }

  function updateDisplay() {
    var imgEl = document.getElementById('galleryPopupImg');
    var counterEl = document.getElementById('galleryPopupCounter');
    if(images.length > 0 && imgEl) {
      imgEl.src = images[currentIndex];
      if(counterEl) counterEl.textContent = (currentIndex + 1);
    }
  }

  function open(index) {
    if(images.length === 0) collectImages();
    if(images.length === 0) {
      console.warn('No gallery images found');
      return;
    }
    currentIndex = Math.max(0, Math.min(index, images.length - 1));
    var popup = document.getElementById('galleryPopup');
    if(popup) {
      popup.classList.add('open');
      updateDisplay();
      document.body.style.overflow = 'hidden';
      isOpen = true;
    }
  }

  function close() {
    var popup = document.getElementById('galleryPopup');
    if(popup) {
      popup.classList.remove('open');
      document.body.style.overflow = '';
      isOpen = false;
    }
  }

  function next() {
    if(images.length === 0) return;
    currentIndex = (currentIndex + 1) % images.length;
    updateDisplay();
  }

  function prev() {
    if(images.length === 0) return;
    currentIndex = (currentIndex - 1 + images.length) % images.length;
    updateDisplay();
  }

  function init() {
    collectImages();
    var items = document.querySelectorAll('.gallery-item');
    items.forEach(function(item) {
      item.addEventListener('click', function() {
        var idx = parseInt(item.getAttribute('data-index'), 10);
        open(idx);
      });
    });
    document.addEventListener('keydown', function(e) {
      if(!isOpen) return;
      if(e.key === 'ArrowRight') next();
      else if(e.key === 'ArrowLeft') prev();
      else if(e.key === 'Escape') close();
    });
    var popup = document.getElementById('galleryPopup');
    if(popup) {
      popup.addEventListener('click', function(e) {
        if(e.target.id === 'galleryPopup') close();
      });
    }
  }

  if(document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.GalleryPopup = { open: open, close: close, next: next, prev: prev };
  console.log('✅ GalleryPopup created:', window.GalleryPopup);
})();
</script>
<script>
document.querySelectorAll('.sig-table tbody tr').forEach(function(row){
  var serviceEl = row.querySelector('.sig-service');
  var serviceName = serviceEl ? serviceEl.textContent.trim() : '';
  var td = document.createElement('td');
  td.style.cssText = 'text-align:center;padding:12px 16px;background:rgba(201,169,110,0.04);border-left:1px solid rgba(201,169,110,0.1)';
  var btn = document.createElement('button');
  btn.className = 'price-book-btn';
  btn.innerHTML = 'Book &#x2192;';
  btn.setAttribute('data-service', serviceName);
  td.appendChild(btn);
  row.appendChild(td);
});

document.addEventListener('click', function(e){
  var btn = e.target.closest('.sig-table .price-book-btn');
  if(!btn) return;
  var serviceName = btn.getAttribute('data-service') || '';
  var sel = document.getElementById('f-service');
  for(var i = 0; i < sel.options.length; i++){
    var v = sel.options[i].value;
    if(v && (v === serviceName || v.toLowerCase().includes(serviceName.toLowerCase()) || serviceName.toLowerCase().includes(v.toLowerCase()))){
      sel.selectedIndex = i; break;
    }
  }
  scrollToSection('booking');
  setTimeout(function(){
    sel.style.transition = 'box-shadow 0.3s, border-color 0.3s';
    sel.style.borderColor = 'var(--gold)';
    sel.style.boxShadow = '0 0 0 3px rgba(201,169,110,0.25)';
    setTimeout(function(){ sel.style.borderColor=''; sel.style.boxShadow=''; }, 2000);
  }, 800);
});

document.querySelectorAll('.pkg-table tbody tr').forEach(function(row){
  var td = document.createElement('td');
  td.style.cssText = 'text-align:center;padding:12px 16px;';
  var pkgName = row.querySelector('.pkg-name') ? row.querySelector('.pkg-name').textContent.trim() : '';
  var btn = document.createElement('button');
  btn.className = 'price-book-btn';
  btn.innerHTML = 'Book &#x2192;';
  btn.setAttribute('data-pkg', pkgName);
  btn.addEventListener('click', function(){
    var sel = document.getElementById('f-service');
    var pn = btn.getAttribute('data-pkg');
    var matched = false;
    // Exact match first
    for(var i = 0; i < sel.options.length; i++){
      if(sel.options[i].value === pn){ sel.selectedIndex = i; matched = true; break; }
    }
    // Partial match fallback
    if(!matched){
      for(var i = 0; i < sel.options.length; i++){
        var v = sel.options[i].value;
        if(v && (v.toLowerCase().includes(pn.toLowerCase()) || pn.toLowerCase().includes(v.toLowerCase()))){
          sel.selectedIndex = i; matched = true; break;
        }
      }
    }
    // If no match, set a note in the special requests field if it exists
    var notes = document.getElementById('f-notes') || document.getElementById('f-requests') || document.getElementById('f-message');
    if(notes && pkgName) notes.value = 'Package: ' + pkgName;
    var bookingSection = document.getElementById('booking');
    if(bookingSection) scrollToSection('booking');
    setTimeout(function(){
      sel.style.transition = 'box-shadow 0.3s ease, border-color 0.3s ease';
      sel.style.borderColor = 'var(--gold)';
      sel.style.boxShadow = '0 0 0 3px rgba(201,169,110,0.25)';
      setTimeout(function(){ sel.style.borderColor=''; sel.style.boxShadow=''; }, 2000);
    }, 800);
  });
  td.appendChild(btn);
  row.appendChild(td);
});

// Whole Book cell acts as the tap target — small buttons are hard to hit on mobile
document.addEventListener('click', function(e){
  if (e.target.closest('.price-book-btn')) return; // button handles its own click
  var cell = e.target.closest('.price-book-col, .pkg-table td:last-child, .sig-table td:last-child');
  if (!cell) return;
  var b = cell.querySelector('.price-book-btn');
  if (b) b.click();
});
</script>
</body>
</html>
