<?php
header('Cache-Control: no-store, no-cache, must-revalidate');
$service = htmlspecialchars($_GET['service'] ?? '', ENT_QUOTES, 'UTF-8');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book a Treatment — Crystal Aura Massage & Spa</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Lato',sans-serif;background:#f7f3ee;min-height:100vh}

/* Top bar */
.topbar{background:#fff;display:flex;align-items:center;padding:14px 16px;border-bottom:1px solid #ece7de;gap:12px;position:sticky;top:0;z-index:10}
.back-btn{width:40px;height:40px;border-radius:50%;border:none;background:#f5f0ea;font-size:22px;cursor:pointer;color:#2c2418;display:flex;align-items:center;justify-content:center;flex-shrink:0;text-decoration:none}
.topbar-title{flex:1}
.topbar-label{font-size:10px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#C9A96E;margin-bottom:2px}
.topbar-service{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:#2c2418;line-height:1.2}

/* Gold banner */
.banner{background:linear-gradient(135deg,#b07c3e 0%,#C9A96E 50%,#b07c3e 100%);padding:28px 20px;text-align:center}
.banner svg{display:block;margin:0 auto 10px}
.banner-name{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:500;color:#fff;letter-spacing:0.04em}
.banner-sub{font-size:11px;color:rgba(255,255,255,0.85);letter-spacing:0.12em;text-transform:uppercase;margin-top:3px}

/* Form card */
.card{background:#fff;margin:16px;border-radius:16px;padding:22px 18px;box-shadow:0 2px 12px rgba(0,0,0,0.07)}
.step-row{display:flex;align-items:center;gap:8px;margin-bottom:22px}
.step-circle{width:28px;height:28px;border-radius:50%;background:#C9A96E;color:#fff;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.step-circle.inactive{background:#ece7de;color:#999}
.step-label{font-size:13px;font-weight:600;color:#2c2418}
.step-label.inactive{color:#999}
.step-line{flex:1;height:1px;background:#ece7de}

label{display:block;font-size:11px;font-weight:700;color:#b07c3e;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:7px}
input, textarea{width:100%;padding:15px 14px;border:1.5px solid #ddd;border-radius:12px;font-size:16px;outline:none;box-sizing:border-box;background:#fafafa;-webkit-appearance:none;font-family:'Lato',sans-serif;transition:border-color 0.2s}
input:focus, textarea:focus{border-color:#C9A96E}
textarea{resize:none}
.field{margin-bottom:16px}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px}

.divider{display:flex;align-items:center;gap:10px;margin-bottom:20px}
.divider-line{flex:1;height:1px;background:#ece7de}

.submit-btn{width:100%;padding:17px;background:linear-gradient(135deg,#b07c3e,#C9A96E);color:#fff;border:none;border-radius:14px;font-size:16px;font-weight:700;letter-spacing:0.04em;cursor:pointer;font-family:'Lato',sans-serif;display:flex;align-items:center;justify-content:center;gap:10px}
.submit-note{text-align:center;font-size:12px;color:#aaa;margin:14px 0 0;line-height:1.5}
</style>
</head>
<body>

<div class="topbar">
  <a href="/" class="back-btn">&#8592;</a>
  <div class="topbar-title">
    <div class="topbar-label">Book Treatment</div>
    <div class="topbar-service"><?= $service ?: 'Crystal Aura Spa' ?></div>
  </div>
</div>

<div class="banner">
  <svg viewBox="0 0 80 75" width="52" height="52" fill="none">
    <path d="M40 2 L41.5 7 L46.5 8.2 L41.5 9.4 L40 14 L38.5 9.4 L33.5 8.2 L38.5 7 Z" fill="#fff"/>
    <path d="M10 70 A 31 31 0 1 1 70 70" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round"/>
    <path d="M40 35 C 37 43 36.5 53 40 60 C 43.5 53 43 43 40 35 Z" fill="rgba(255,255,255,0.85)"/>
    <path d="M33 39 C 29 48 32 57 40 60 C 38 52 35 46 33 39 Z" fill="rgba(255,255,255,0.7)"/>
    <path d="M47 39 C 51 48 48 57 40 60 C 42 52 45 46 47 39 Z" fill="rgba(255,255,255,0.7)"/>
  </svg>
  <div class="banner-name">Crystal Aura</div>
  <div class="banner-sub">Massage &amp; Spa</div>
</div>

<div class="card">
  <div class="step-row">
    <div class="step-circle">1</div>
    <div class="step-label">Your Details</div>
    <div class="step-line"></div>
    <div class="step-circle inactive">2</div>
    <div class="step-label inactive">Schedule</div>
  </div>

  <div class="field">
    <label>Full Name *</label>
    <input type="text" id="f-name" placeholder="e.g. Sarah Johnson" autocomplete="name">
  </div>
  <div class="field">
    <label>Phone / WhatsApp *</label>
    <input type="tel" id="f-phone" placeholder="+66 or local number" autocomplete="tel">
  </div>

  <div class="divider">
    <div class="divider-line"></div>
    <div style="display:flex;align-items:center;gap:8px">
      <div class="step-circle">2</div>
      <div class="step-label">Schedule</div>
    </div>
    <div class="divider-line"></div>
  </div>

  <div class="grid2">
    <div>
      <label>Date</label>
      <input type="date" id="f-date">
    </div>
    <div>
      <label>Time</label>
      <input type="time" id="f-time">
    </div>
  </div>
  <div class="field">
    <label>Special Requests</label>
    <textarea id="f-notes" rows="3" placeholder="Allergies, preferences, or anything we should know..."></textarea>
  </div>

  <button class="submit-btn" onclick="submit()">
    <svg viewBox="0 0 24 24" width="20" height="20" fill="#fff"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.138.558 4.14 1.532 5.874L.057 23.273c-.07.268.162.5.43.43l5.399-1.475A11.94 11.94 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.887 0-3.66-.5-5.197-1.374l-.371-.22-3.841 1.049 1.02-3.728-.24-.385A9.966 9.966 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
    Send via WhatsApp
  </button>
  <p class="submit-note">We'll confirm your booking via WhatsApp.<br>Opens WhatsApp with your details pre-filled.</p>
</div>

<script>
var service = <?= json_encode($service) ?>;
function submit() {
  var name = document.getElementById('f-name').value.trim();
  var phone = document.getElementById('f-phone').value.trim();
  if (!name || !phone) { alert('Please enter your name and phone number.'); return; }
  var date = document.getElementById('f-date').value;
  var time = document.getElementById('f-time').value;
  var notes = document.getElementById('f-notes').value.trim();
  var msg = 'Hi Crystal Aura! I would like to book:\n';
  if (service) msg += 'Service: ' + service + '\n';
  msg += 'Name: ' + name + '\n';
  msg += 'Phone: ' + phone + '\n';
  if (date) msg += 'Date: ' + date + '\n';
  if (time) msg += 'Time: ' + time + '\n';
  if (notes) msg += 'Notes: ' + notes;
  window.location.href = 'https://wa.me/66959932861?text=' + encodeURIComponent(msg);
}
</script>
</body>
</html>
