# Nena Studio — Your Story, One Stitch at a Time ✨

Creative textile atelier based in the Netherlands — personalised wedding & event accessories, embroidery and garment alterations. Every creation begins with your story.

🌐 **Live:** `https://nenastudio.nl` · 🇳🇱 Netherlands · 🧵 Slow-crafted, story-led

---

## Stack — cPanel-ready, zero build step

- **Frontend:** Semantic HTML5 · Modern CSS3 (Custom Properties, Grid/Flexbox, Glassmorphism, keyframe animations) · Vanilla JS (ES6+)
- **Backend:** PHP 8+ · **SQLite via PDO** — auto-initializes on first run (`data/database.sqlite` blocked via `.htaccess`)
- **Hosting:** Standard cPanel shared hosting (Apache / LiteSpeed) — just upload the repo to `public_html`.

No Composer, no npm, no frameworks. Upload and it runs.

---

## Quick start (local)

```bash
php -S localhost:8000
# open http://localhost:8000
# DB auto-creates at data/database.sqlite with 3 sample stories
```

## cPanel deploy

1. Upload everything to `public_html` (or your domain folder).
2. Ensure `data/` and `uploads/` are writable (0755). The DB file is created automatically.
3. Visit your domain — the site initializes with default SEO, admin credentials, and 3 portfolio stories.
4. Go to `/admin/login.php` → sign in with **admin / Nena2026!** → change password in **SEO & Settings**.

> `.htaccess` already enables GZIP/Brotli, browser caching, HTTPS redirect, clean `/sitemap.xml` route, and blocks direct `.sqlite` access.

---

## Features

**SEO & performance (Lighthouse 95–100 target)**
- Dynamic `<title>`, meta description/keywords, canonical, `robots`, geo tags (NL)
- Full Open Graph + Twitter Card (rich previews on WhatsApp / Instagram / Pinterest)
- Schema.org JSON-LD: `LocalBusiness` + `ProfessionalService` + `ArtGallery`, `WebSite`, `FAQPage` / `Service`
- Dynamic `sitemap.php` → `/sitemap.xml` (main URLs + all portfolio stories) + clean `robots.txt`
- Semantic one-`h1` hierarchy, `loading="lazy"` + `decoding="async"` + explicit dimensions (no CLS), alt text everywhere, WCAG AA
- GZIP, brotli, 1-year asset caching, preconnect, system font fallbacks

**Design — Cute · Romantic · Bridal · Minimalist · Glassmorphic**
- Palette: Dark Teal `#006D77` · Classic Teal `#008080` · Soft Turquoise `#2A9D8F` · Light Teal `#4DB6AC` · Vivid `#00A6A6` · Aqua `#A8DADC` on warm ivory/cream `#FAF8F5` / `#FFFDF9` / `#F4ECE6`
- True glassmorphism (`backdrop-filter: blur(16px)`), stitch dashed borders, ambient aura blobs
- Editorial typography: *Cormorant Garamond* (headings) + *Plus Jakarta Sans* (body)
- Custom inline SVG (needle & thread, botanical line art, scissors/buttons/spools), floating animations, stitch timeline

**Sections & copy — exact brand voice**
- Sticky glassmorphic nav (Home · Our Story · What We Create · Behind the Stitches · Let's Create Together CTA)
- Hero: “Your Story, One Stitch at a Time.” + two CTAs + “A Little More About Nena” card
- Our Story, What We Create (3 interactive service cards → preselect form), Behind the Stitches (filterable portfolio + story modal with carousel & video), Let's Create Together (4-step journey + full enquiry form)

**Enquiry form** — AJAX, CSRF, honeypot, drag-and-drop multi-upload (JPG/PNG/WEBP/PDF ≤10MB, previews), calendar + Flexible checkbox, contact pills

**Admin panel** (`/admin`) — glassmorphic Teal dashboard
- Enquiries CRM: statuses (New → Archived), notes, file previews/downloads, delete
- Portfolio Manager: full CRUD, multi-photo + video, alt-text & SEO slug/summary, reorder
- SEO & Site Settings: meta title/description/keywords, OG image, Instagram, contact email, admin password — no code edits

---

## File structure

```
index.php                # Frontend + dynamic SEO head + JSON-LD
assets/css/style.css     # Full glassmorphism, responsive, animations
assets/js/main.js        # Filters, modal, uploader, AJAX form
includes/db.php          # SQLite auto-init, seed, helpers, CSRF
api/submit_enquiry.php   # Secure form & upload handler
admin/login.php          # Login
admin/index.php          # Dashboard: CRM + Portfolio + Settings
sitemap.php              # Dynamic XML sitemap
.htaccess                # Compression, caching, HTTPS, clean URLs
robots.txt
data/database.sqlite     # auto-created (gitignored, blocked)
uploads/                 # enquiry & portfolio uploads
```

---

## Admin credentials (seed)

- **Username:** `admin`
- **Password:** `Nena2026!`
- Change immediately in Admin → SEO & Site Settings.

---

## License

All brand copy belongs to Nena Studio. Code is provided for this project’s deployment.

— Stitched with care in the Netherlands 🪡
