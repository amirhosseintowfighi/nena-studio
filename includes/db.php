<?php
// includes/db.php — Auto-initializing SQLite via PDO — Nena Studio
$__dbDir  = __DIR__ . '/../data';
$__dbFile = $__dbDir . '/database.sqlite';
$__needSeed = false;
if (!is_dir($__dbDir)) { @mkdir($__dbDir, 0755, true); }
if (!file_exists($__dbFile)) { $__needSeed = true; @touch($__dbFile); @chmod($__dbFile, 0644); }
if (!file_exists($__dbDir . '/.htaccess')) {
  @file_put_contents($__dbDir . '/.htaccess', "Require all denied\n<FilesMatch \\.(jpg|jpeg|png|webp|pdf|mp4|webm)$>\n  Require all granted\n</FilesMatch>\n");
}
try {
  $pdo = new PDO('sqlite:' . $__dbFile);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
} catch (PDOException $e) {
  http_response_code(500);
  exit('Database connection failed.');
}
$pdo->exec("
  CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT);
  CREATE TABLE IF NOT EXISTS portfolio (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL, slug TEXT UNIQUE NOT NULL, category TEXT NOT NULL,
    summary TEXT, story TEXT, cover_image TEXT, images TEXT, video TEXT,
    alt_text TEXT, seo_title TEXT, seo_description TEXT,
    status TEXT DEFAULT 'published', sort_order INTEGER DEFAULT 0,
    created_at TEXT, updated_at TEXT
  );
  CREATE TABLE IF NOT EXISTS enquiries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL, email TEXT NOT NULL, project_type TEXT, message TEXT,
    target_date TEXT, flexible INTEGER DEFAULT 0, contact_method TEXT, contact_detail TEXT,
    files TEXT, status TEXT DEFAULT 'New', admin_notes TEXT, created_at TEXT
  );
");
function get_setting(string $key, ?string $default = null): ?string {
  global $pdo; static $cache = null;
  if ($cache === null) { $cache = []; try { foreach ($pdo->query("SELECT key, value FROM settings") as $r) $cache[$r['key']] = $r['value']; } catch(Throwable $e) {} }
  return $cache[$key] ?? $default;
}
function set_setting(string $key, string $value): void {
  global $pdo; $pdo->prepare("INSERT INTO settings(key,value) VALUES(:k,:v) ON CONFLICT(key) DO UPDATE SET value=:v2")->execute([':k'=>$key, ':v'=>$value, ':v2'=>$value]);
}
function slugify(string $s): string {
  $tmp = iconv('UTF-8','ASCII//TRANSLIT',$s);
  $tmp = strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$tmp),'-'));
  return $tmp ?: 'item-'.time();
}
$__hasSettings = (int)$pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
if ($__hasSettings === 0 || $__needSeed) {
  $defaults = [
    'site_title' => 'Nena Studio — Your Story, One Stitch at a Time',
    'site_tagline' => 'Personalised textile creations, embroidery, and thoughtful alterations.',
    'meta_title' => 'Nena Studio | Personalised Embroidery & Textile Atelier in the Netherlands',
    'meta_description' => 'Nena Studio is a creative textile atelier in the Netherlands — personalised wedding & event accessories, embroidery and garment alterations. Every creation begins with your story.',
    'meta_keywords' => 'Nena Studio, embroidery Netherlands, bridal accessories, wedding veil embroidery, personalised textiles, garment alterations, Dutch atelier',
    'og_image' => 'https://images.unsplash.com/photo-1520637102912-2df6bb2aec6d?w=1200&q=80&auto=format&fit=crop',
    'site_url' => 'https://nenastudio.nl',
    'contact_email' => 'hello@nenastudio.nl',
    'instagram_url' => 'https://instagram.com/nena.studio',
    'admin_username' => 'admin',
    'admin_password_hash' => password_hash('Nena2026!', PASSWORD_DEFAULT),
    'robots' => 'index, follow',
  ];
  foreach ($defaults as $k=>$v) { $pdo->prepare("INSERT OR IGNORE INTO settings(key,value) VALUES(?,?)")->execute([$k,$v]); }
  $cnt = (int)$pdo->query("SELECT COUNT(*) FROM portfolio")->fetchColumn();
  if ($cnt === 0) {
    $now = date('Y-m-d H:i:s');
    $seed = [
      [
        'A Veil of Memories — Embroidered Bridal Veil with Birth Flowers',
        'a-veil-of-memories-embroidered-bridal-veil',
        'Weddings & Special Occasions',
        "A cathedral-length veil embroidered with the bride's birth flowers and a hidden love note in the hem — soft, luminous, and deeply personal.",
        "<p>When Sophie first wrote to me she had an idea that felt both simple and intimate: she wanted her veil to carry more than just tulle. Her mother had saved a piece of lace from her own wedding dress, and Sophie wondered if it could be part of hers.</p><p>We met with fabric swatches, pressed flowers, and pencil sketches. The final design wove together <strong>May lily-of-the-valley</strong> and <strong>November chrysanthemum</strong> — her and her partner's birth flowers — trailing lightly along the edge like a quiet garden. A tiny embroidered date, 12.06.2024, sits at the very hem, visible only when the wind lifts the veil.</p><p>The lace from her mother's dress became a delicate frame at the comb, hand-stitched so it floats against the tulle. The result is airy and romantic, but every stitch holds a memory.</p><p><em>Materials: soft English tulle, silk threads, vintage lace. Time: 3 weeks. Hand-embroidered in the atelier.</em></p>",
        'https://images.unsplash.com/photo-1519225421980-715cb0215aed?w=900&q=80&auto=format&fit=crop',
        '["https://images.unsplash.com/photo-1519741497674-611481863552?w=900&q=80&auto=format&fit=crop","https://images.unsplash.com/photo-1520854221256-589c14133f19?w=900&q=80&auto=format&fit=crop"]',
        '',
        'Bridal veil embroidered with delicate birth flowers on soft tulle, detail of hand stitching',
        'Embroidered Bridal Veil with Birth Flowers — Nena Studio',
        'A hand-embroidered cathedral veil featuring birth flowers and vintage lace — a bespoke bridal piece from Nena Studio, Netherlands.'
      ],
      [
        "The Little Blue Jacket — From Father's Shirt to Baby Keepsake",
        'the-little-blue-jacket-fathers-shirt-keepsake',
        'Made for You',
        "A beloved denim shirt, carefully unpicked and reimagined into a tiny jacket for a newborn — keeping a grandfather's story close.",
        "<p>Not every project starts with a special occasion on the calendar. This one started with a shirt — a much-loved, perfectly soft denim shirt that had belonged to a grandfather the family dearly missed.</p><p>The request was tender: could it become something a new baby could actually wear, not just keep in a box? We designed a <strong>little chore jacket</strong> with wooden buttons, tiny embroidered initials on the chest pocket, and the original shirt's faded pocket kept intact as a detail on the sleeve.</p><p>Inside the collar we embroidered a small robin — a quiet nod the family shared — and the words <em>'always with you'</em> in soft sage thread.</p><p>The jacket is made to be worn, outgrown, and kept. That's the point: stories that live on through what we make.</p>",
        'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=900&q=80&auto=format&fit=crop',
        '["https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?w=900&q=80&auto=format&fit=crop","https://images.unsplash.com/photo-1516762689617-e1cffcef479d?w=900&q=80&auto=format&fit=crop"]',
        '',
        'Tiny baby jacket sewn from repurposed denim shirt with embroidered initials',
        "Baby Keepsake Jacket from Father's Shirt — Nena Studio",
        "A custom baby jacket reimagined from a grandfather's denim shirt — a personal textile keepsake by Nena Studio."
      ],
      [
        'Giving Her Dress a Second Story — Vintage Dress Transformation',
        'vintage-dress-transformation-second-story',
        'Alterations & Reimagining',
        'A 1980s silk dress, shortened, reshaped and subtly embroidered — honouring the original while making it hers again.',
        "<p>Some clothes deserve a second story. This silk dress had beautiful bones — a bias cut, a soft champagne tone — but the shoulders were too broad and the length felt like a costume rather than a dress.</p><p>We began with the essentials: taking in the shoulders, refining the waist, shortening the hem to a modern midi. Then came the part that makes alterations feel like design: we added <strong>hand-embroidered botanical sprigs</strong> along the neckline to draw the eye and give the plain silk a quiet, romantic texture.</p><p>We kept the original silk-covered buttons and reused the excess fabric to create a slender self-belt. The dress now fits like it was made for her — because, in a way, it was: a collaboration between what it was and what it could become.</p><p><em>A reminder that sustainability can be exquisitely personal.</em></p>",
        'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=900&q=80&auto=format&fit=crop',
        '["https://images.unsplash.com/photo-1485230895905-ec40ba36b9bc?w=900&q=80&auto=format&fit=crop","https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=900&q=80&auto=format&fit=crop"]',
        '',
        'Vintage champagne silk dress after alteration with botanical embroidery detail',
        'Vintage Dress Alteration & Embroidery — Nena Studio',
        'Alteration and reimagining of a vintage silk dress with hand embroidery — second-story transformations by Nena Studio.'
      ],
    ];
    $ins = $pdo->prepare("INSERT INTO portfolio(title,slug,category,summary,story,cover_image,images,video,alt_text,seo_title,seo_description,status,sort_order,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    foreach ($seed as $i=>$r) {
      $ins->execute([$r[0],$r[1],$r[2],$r[3],$r[4],$r[5],$r[6],$r[7],$r[8],$r[9],$r[10],'published',$i,$now,$now]);
    }
  }
}
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
function csrf_token(): string { return $_SESSION['csrf_token'] ?? ''; }
function verify_csrf(?string $t): bool { return is_string($t) && hash_equals($_SESSION['csrf_token'] ?? '', $t); }
