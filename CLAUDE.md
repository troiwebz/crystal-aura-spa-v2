# Crystal Aura Massage & Spa — Claude Project Brief

## Project Overview
Website for **Crystal Aura Massage & Spa**, a Thai massage and wellness spa located in Nimman, Chiang Mai, Thailand.

- **Client contact:** crystalauramassage@gmail.com | +66 959 932 861
- **Address:** 22 Nimmana Haeminda Rd Lane 13, Suthep, Mueang, Chiang Mai 50200
- **WhatsApp booking number:** +66959932861 (used in all booking links)

---

## Live URLs
| Environment | URL | Notes |
|---|---|---|
| Railway (PHP live) | https://crystal-aura-spa-production.up.railway.app | Primary backend |
| Vercel (static mirror) | https://crystal-aura-final.vercel.app | Static HTML copy of Railway |
| GitHub | https://github.com/troiwebz/crystal-aura-spa-v2 | Branch: `main` |

---

## File Structure
```
crystal-aura-php/
├── index.php        # Main homepage — all CSS, HTML, JS inline in one file
├── book.php         # Standalone booking page (opened when any Book button tapped)
├── router.php       # PHP built-in server router — maps URLs to PHP files
├── capture.php      # Silently saves booking data to data/bookings.json
├── admin.php        # Admin panel to view bookings
├── blog.php         # Blog page
├── sitemap.php      # Auto-generated sitemap
├── robots.php       # robots.txt
├── 404.php          # Custom 404 page
├── Dockerfile       # Railway Docker config — has a cache-bust comment at bottom
├── data/
│   ├── settings.json    # All site config — SEO, business info, services, reviews
│   └── bookings.json    # Booking submissions (auto-created)
└── CLAUDE.md        # This file
```

---

## Deploy Workflow

### Deploy to Railway (PHP backend)
```bash
cd crystal-aura-php

# Bump Dockerfile comment to bust Docker layer cache (required for Railway to pick up changes)
echo "# $(date)" >> Dockerfile

railway up --detach
```

### Sync Vercel (static mirror) after Railway deploy
```bash
# Wait for Railway to finish, then:
curl -s "https://crystal-aura-spa-production.up.railway.app/" > ../crystal-aura-final/index.html
curl -s "https://crystal-aura-spa-production.up.railway.app/book" > ../crystal-aura-final/book.html

cd ../crystal-aura-final
git add index.html book.html
git commit -m "Sync from Railway: <describe change>"
git push
npx vercel --prod --yes
```

### Verify deploy is live
```bash
curl -s "https://crystal-aura-spa-production.up.railway.app/" | grep -o "YOUR_UNIQUE_STRING"
```

---

## Architecture Decisions (important — do not change without understanding why)

### Booking buttons → `/book` page
All Book buttons and "Book Now" links are plain `<a href="/book?service=Name">` links.
- **Do NOT use onclick/JS handlers for booking** — spent many sessions fighting touch-event scroll detection on mobile. Plain links are the only reliable approach.
- `book.php` receives `?service=` param and pre-fills the service name
- On submit, opens WhatsApp with message pre-filled

### No JS event handlers on price buttons
The pricing table buttons (`<a class="price-book-btn" href="/book?service=...">`) are anchor tags, not `<button>` elements. This was intentional — `<button>` elements inside `display:table` containers on mobile suffer from scroll-detection delay where the browser consumes the first 1-3 taps thinking it might be a scroll gesture.

### Router
`router.php` maps all URL routes. When adding a new page, add it here:
```php
if ($uri === '/your-page') { require __DIR__.'/your-page.php'; return true; }
```

### Settings-driven content
Almost everything editable (SEO, business info, services, reviews, pricing) lives in `data/settings.json`. Edit the JSON, redeploy — no PHP changes needed.

### CSS/JS is inline in index.php
All styles and scripts are embedded directly in `index.php` (no separate CSS/JS files). This keeps the site fast (single request) but means the file is large (~4000 lines). Use grep to find things:
```bash
grep -n "SECTION NAME\|function name\|class-name" index.php
```

### Navbar on mobile
- Background: cream (`#f0ece4`)
- Logo: SVG lotus icon above, two-line text below
- Hamburger menu on right side
- `Book Now` button hidden on mobile (no space)

---

## PHP No-Cache Headers
`index.php` has these at the top — keep them:
```php
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
```

## Dockerfile Cache Busting
Railway caches Docker layers. If changes aren't showing after deploy, add a comment to Dockerfile:
```bash
echo "# $(date)" >> Dockerfile
railway up --detach
```

---

## Things NOT to Change
- WhatsApp number: `+66959932861` (appears in `book.php` submit function)
- Booking button approach: keep as plain `<a href="/book?service=...">` — do not add JS handlers
- `router.php`: do not remove existing routes
- `data/` folder: not served publicly (403 blocked in router)

---

## Local Development
```bash
cd crystal-aura-php
php -S localhost:8080 router.php
```
Then open http://localhost:8080

---

## Key Sections in index.php (grep these)
| Section | Grep for |
|---|---|
| Mobile navbar CSS | `@media(max-width:768px)` |
| Pricing tables | `pricing-tab-content` |
| Book buttons | `price-book-btn` |
| Booking form (desktop) | `id="booking"` |
| JS booking functions | `function bookTreatment` |
| Smooth scroll handler | `SMOOTH SCROLL OFFSET` |
