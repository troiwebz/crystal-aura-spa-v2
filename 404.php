<?php
http_response_code(404);
$S = json_decode(file_get_contents(__DIR__ . '/data/settings.json'), true);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Page Not Found | Crystal Aura Massage & Spa</title><meta name="robots" content="noindex">
<style>body{font-family:'Lato',sans-serif;background:#faf1e7;color:#2b2118;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;text-align:center}
h1{font-size:60px;color:#c9a96e;margin:0}p{color:#7d6f5f}a{display:inline-block;margin-top:14px;background:linear-gradient(135deg,#a07840,#c9a96e);color:#fff;padding:12px 30px;border-radius:8px;text-decoration:none;font-weight:700;font-size:13px;letter-spacing:0.08em;text-transform:uppercase}</style>
</head><body><div><h1>404</h1><p>This page drifted away like incense smoke.</p><a href="/">Back to Crystal Aura Spa</a></div></body></html>
