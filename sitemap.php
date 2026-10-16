<?php
require_once __DIR__ . '/includes/db.php';
$base = rtrim(get_setting('site_url', 'https://nenastudio.nl'), '/');
if (empty($base)) $base = 'https://nenastudio.nl';
header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc><?= htmlspecialchars($base . '/') ?></loc><lastmod><?= date('Y-m-d') ?></lastmod><changefreq>weekly</changefreq><priority>1.0</priority></url>
  <url><loc><?= htmlspecialchars($base . '/#our-story') ?></loc><lastmod><?= date('Y-m-d') ?></lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>
  <url><loc><?= htmlspecialchars($base . '/#what-we-create') ?></loc><lastmod><?= date('Y-m-d') ?></lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>
  <url><loc><?= htmlspecialchars($base . '/#behind-the-stitches') ?></loc><lastmod><?= date('Y-m-d') ?></lastmod><changefreq>weekly</changefreq><priority>0.9</priority></url>
  <url><loc><?= htmlspecialchars($base . '/#lets-create-together') ?></loc><lastmod><?= date('Y-m-d') ?></lastmod><changefreq>monthly</changefreq><priority>0.7</priority></url>
<?php
try {
  $stmt = $pdo->query("SELECT slug, updated_at FROM portfolio WHERE status='published' ORDER BY sort_order ASC, created_at DESC");
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $loc = $base . '/#story-' . htmlspecialchars($row['slug']);
    $lastmod = $row['updated_at'] ? date('Y-m-d', strtotime($row['updated_at'])) : date('Y-m-d');
    echo "  <url>\n    <loc>{$loc}</loc>\n    <lastmod>{$lastmod}</lastmod>\n    <changefreq>monthly</changefreq>\n    <priority>0.6</priority>\n  </url>\n";
  }
} catch (Throwable $e) {}
?>
</urlset>
