<?php
require_once __DIR__ . '/includes/db.php';

// --- SEO / settings ---
$siteUrl   = rtrim(get_setting('site_url', 'https://nenastudio.nl'), '/');
if (!$siteUrl) $siteUrl = 'https://nenastudio.nl';
$metaTitle = get_setting('meta_title', 'Nena Studio | Personalised Embroidery & Textile Atelier in the Netherlands');
$metaDesc  = get_setting('meta_description', 'Nena Studio is a creative textile atelier in the Netherlands — personalised wedding & event accessories, embroidery and garment alterations. Every creation begins with your story.');
$metaKeys  = get_setting('meta_keywords', 'Nena Studio, embroidery Netherlands, bridal accessories, wedding veil embroidery, personalised textiles, garment alterations');
$ogImage   = get_setting('og_image', 'https://images.unsplash.com/photo-1520637102912-2df6bb2aec6d?w=1200&q=80&auto=format&fit=crop');
$instaUrl  = get_setting('instagram_url', 'https://instagram.com/nena.studio');
$contactEmail = get_setting('contact_email', 'hello@nenastudio.nl');
$canonical = $siteUrl . '/';
$robots    = get_setting('robots', 'index, follow');

// Portfolio for render + JSON
$portfolio = [];
try {
  $stmt = $pdo->query("SELECT * FROM portfolio WHERE status='published' ORDER BY sort_order ASC, created_at DESC");
  $portfolio = $stmt->fetchAll();
} catch(Throwable $e) { $portfolio = []; }

// JSON for JS (escape safely)
$portfolioJson = json_encode(array_map(function($r){
  return [
    'id'=>$r['id'], 'title'=>$r['title'], 'slug'=>$r['slug'], 'category'=>$r['category'],
    'summary'=>$r['summary'], 'story'=>$r['story'], 'cover_image'=>$r['cover_image'],
    'images'=>$r['images'], 'video'=>$r['video'], 'alt_text'=>$r['alt_text']
  ];
}, $portfolio), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

// Structured data
$schemaLocalBusiness = [
  '@context'=>'https://schema.org',
  '@type'=>['LocalBusiness','ProfessionalService','ArtGallery'],
  'name'=>'Nena Studio',
  'url'=>$siteUrl.'/',
  'image'=>$ogImage,
  'description'=>$metaDesc,
  'address'=>['@type'=>'PostalAddress','addressCountry'=>'NL','addressRegion'=>'Netherlands'],
  'areaServed'=>['@type'=>'Country','name'=>'Netherlands'],
  'makesOffer'=>[
    ['@type'=>'Offer','itemOffered'=>['@type'=>'Service','name'=>'Weddings & Special Occasions','description'=>'Embroidered bridal veils, personalised shoes, groom accessories, wedding-party details and keepsakes with birth flowers, initials and meaningful dates.']],
    ['@type'=>'Offer','itemOffered'=>['@type'=>'Service','name'=>'Made for You','description'=>'Embroidered gifts, personalised accessories and textile commissions for celebrations, cultural events and small businesses.']],
    ['@type'=>'Offer','itemOffered'=>['@type'=>'Service','name'=>'Alterations & Reimagining','description'=>'Garment alterations, repairs and creative transformations — shortening, repairing and reimagining pieces you already own.']],
  ],
  'sameAs'=> array_values(array_filter([$instaUrl])),
];
$schemaWebsite = [
  '@context'=>'https://schema.org','@type'=>'WebSite','name'=>'Nena Studio','url'=>$siteUrl.'/',
  'potentialAction'=>['@type'=>'SearchAction','target'=>$siteUrl.'/?q={search_term_string}','query-input'=>'required name=search_term_string']
];
$schemaFAQ = [
  '@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[
    ['@type'=>'Question','name'=>'What does Nena Studio create?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Wedding & event accessories, embroidered gifts and personalised textile pieces, plus thoughtful alterations and reimagining of garments you already own.']],
    ['@type'=>'Question','name'=>'Do I need to have a finished idea before I get in touch?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Not at all — you can share inspiration photos, sketches, colours, or simply describe a feeling or memory. We will explore the possibilities together.']],
    ['@type'=>'Question','name'=>'How does the process work?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Share your story → explore possibilities and agree a design and quote → we craft your piece and keep you updated → collection or delivery when ready.']],
  ]
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($metaTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
<meta name="keywords" content="<?= htmlspecialchars($metaKeys) ?>">
<meta name="robots" content="<?= htmlspecialchars($robots) ?>">
<link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
<meta name="geo.region" content="NL">
<meta name="geo.placename" content="Netherlands">
<meta name="language" content="en">
<meta name="theme-color" content="#006D77">
<meta name="author" content="Nena Studio">

<!-- Open Graph / WhatsApp / Pinterest -->
<meta property="og:type" content="website">
<meta property="og:locale" content="en_NL">
<meta property="og:site_name" content="Nena Studio">
<meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?>">
<meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
<meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
<meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Nena Studio — embroidered textile creations in the Netherlands">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($metaTitle) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($metaDesc) ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://images.unsplash.com">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><circle cx='16' cy='16' r='15' fill='%23006D77'/><path d='M10 22 L22 10 M9 10 Q16 8 23 10' stroke='white' stroke-width='1.6' fill='none' stroke-linecap='round'/><circle cx='22' cy='10' r='1.4' fill='white'/></svg>">
<link rel="stylesheet" href="assets/css/style.css">
<script type="application/ld+json"><?= json_encode($schemaLocalBusiness, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) ?></script>
<script type="application/ld+json"><?= json_encode($schemaWebsite, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) ?></script>
<script type="application/ld+json"><?= json_encode($schemaFAQ, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) ?></script>
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<div class="bg-auras" aria-hidden="true"><div class="aura aura--1"></div><div class="aura aura--2"></div><div class="aura aura--3"></div><div class="aura aura--4"></div></div>

<!-- NAV -->
<nav class="nav" aria-label="Primary">
  <div class="nav-inner">
    <a class="brand" href="#" aria-label="Nena Studio — home">
      <span class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="#006D77" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 20 L18 8"/><path d="M7 8 Q12 6 17 8"/><circle cx="18" cy="7.2" r="1.2" fill="#2A9D8F" stroke="none"/><path d="M4.5 9.5 Q6 7 8.2 9 Q6 11 4.5 9.5Z" fill="none"/><path d="M9 9 Q11.2 6.8 13.6 9 Q11.2 11.2 9 9Z" fill="none"/></svg>
      </span>
      Nena Studio
    </a>
    <button class="hamburger" id="hamb" aria-expanded="false" aria-controls="navLinks" aria-label="Open menu"><span></span><span></span><span></span></button>
    <div class="nav-links" id="navLinks">
      <a href="#home">Home</a>
      <a href="#our-story">Our Story</a>
      <a href="#what-we-create">What We Create</a>
      <a href="#behind-the-stitches">Behind the Stitches</a>
      <a class="btn btn--primary btn--sm" href="#lets-create-together">Let's Create Together</a>
    </div>
  </div>
</nav>

<main id="main">

<!-- HERO -->
<section class="section hero" id="home">
  <div class="wrap">
    <div class="hero-grid">
      <div class="hero-copy reveal">
        <div class="eyebrow">
          <svg viewBox="0 0 24 24" fill="none" stroke="#2A9D8F" stroke-width="1.6" aria-hidden="true"><path d="M12 7.5 C12 7.5 14.2 5.8 15.9 7.2 C17.6 8.6 16.6 11.1 12 14.2 C7.4 11.1 6.4 8.6 8.1 7.2 C9.8 5.8 12 7.5 12 7.5Z"/></svg>
          Atelier in the Netherlands — slow-crafted, story-led
        </div>
        <h1>Your Story, One Stitch <em>at a Time.</em></h1>
        <p class="hero-sub">Personalised textile creations, embroidery, and thoughtful alterations.</p>
        <p class="hero-lead">At Nena, I bring your ideas to life through sewing, embroidery, and creative craftsmanship. From meaningful details for special occasions to everyday pieces made personal, and beloved garments given a new beginning, every project starts with you.</p>
        <div class="hero-ctas">
          <a class="btn btn--primary" href="#lets-create-together">Start Your Story</a>
          <a class="btn btn--glass" href="#behind-the-stitches">Explore Our Creations</a>
        </div>
        <div class="mini-trust" aria-label="Highlights">
          <span>✦ Bespoke embroidery</span><span>✦ Bridal & keepsakes</span><span>✦ Alterations with care</span>
        </div>
      </div>
      <div class="hero-visual reveal">
        <div class="hero-card glass stitch float" aria-hidden="true">
          <img src="https://images.unsplash.com/photo-1520637102912-2df6bb2aec6d?w=900&q=80&auto=format&fit=crop" alt="Soft ivory fabric with delicate embroidery detail — Nena Studio atelier" width="900" height="960" loading="eager" decoding="async" fetchpriority="high">
        </div>
        <div class="hero-float glass stitch">
          <span class="dot" aria-hidden="true"></span>
          <div>
            <h4>A Little More About Nena</h4>
            <p>We believe the things we wear, use, and keep can carry stories. Nena is where I bring my love for craftsmanship and storytelling together, creating pieces inspired by the people they belong to.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="intro-card glass stitch reveal" style="margin-top:22px">
      <div>
        <h3 style="margin-bottom:6px">A Little More About Nena</h3>
        <p>We believe the things we wear, use, and keep can carry stories. Nena is where I bring my love for craftsmanship and storytelling together, creating pieces inspired by the people they belong to.</p>
      </div>
      <div class="intro-art" aria-hidden="true">
        <svg viewBox="0 0 220 140" width="220" height="140" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Delicate floral line art with needle and thread">
          <path d="M30 100 Q70 20 110 100 T190 100" stroke="#4DB6AC" stroke-width="1.4" stroke-dasharray="6 6" opacity="0.9"/>
          <g stroke="#2A9D8F" stroke-width="1.5" stroke-linecap="round">
            <path d="M60 72 Q56 58 62 48 Q68 58 64 72"/>
            <path d="M62 48 Q70 44 76 52 Q68 56 62 48"/>
            <path d="M62 48 Q54 44 48 52 Q56 56 62 48"/>
            <path d="M64 72 Q72 74 76 68 Q70 64 64 72"/>
            <path d="M60 72 Q52 74 48 68 Q54 64 60 72"/>
            <path d="M110 86 Q106 70 112 58 Q118 70 114 86"/>
            <path d="M112 58 Q122 54 128 64 Q118 68 112 58"/>
            <path d="M112 58 Q102 54 96 64 Q106 68 112 58"/>
            <circle cx="62" cy="62" r="3.2" fill="#A8DADC" stroke="none"/>
            <circle cx="112" cy="72" r="3.2" fill="#A8DADC" stroke="none"/>
            <path d="M150 44 L170 86" stroke="#4DB6AC" stroke-width="1.4"/>
            <circle cx="170" cy="86" r="2.2" fill="#4DB6AC" stroke="none"/>
            <path d="M170 40 L174 48 L170 46 L166 48 Z" fill="none" stroke="#4DB6AC"/>
          </g>
        </svg>
      </div>
    </div>
  </div>
</section>

<!-- OUR STORY -->
<section class="section" id="our-story">
  <div class="wrap">
    <div style="text-align:center; max-width:760px; margin:0 auto 18px" class="reveal">
      <div class="eyebrow">Our Story</div>
      <h2 style="margin:10px 0 0">Every Creation Begins with a Story.</h2>
    </div>
    <div class="story-grid">
      <article class="story-panel glass stitch reveal">
        <p>Nena began with something I've always loved: making things with my hands.</p>
        <p>I grew up watching my mother sew, learning from her and gradually discovering my own love for fabrics, colours, textures, and the endless possibilities of creating something from scratch. Over the years, I explored embroidery, beadwork, and different ways of turning ordinary materials into something personal.</p>
        <p>But what has always fascinated me just as much as making things is the stories people carry.</p>
        <p>My background in culture, storytelling, and event management has taught me to look beyond objects and appreciate the memories, emotions, and connections behind them.</p>
        <p>A piece of fabric might be part of a mother's wedding dress, a favourite shirt someone isn't ready to let go of, or an accessory waiting to become part of a special occasion.</p>
        <p>That's what inspired Nena: a space where people's ideas, memories, and personal styles meet craftsmanship.</p>
        <p>I don't believe there's one definition of beauty or one right way to create something meaningful. What matters to me is understanding what makes something special to the person it belongs to.</p>
        <p><strong style="color:var(--teal-dark)">Nena is my way of bringing stories to life through the things we make, wear, and keep.</strong></p>
        <div class="badge-row" aria-label="Atelier values">
          <span class="badge">✦ Slow-crafted</span><span class="badge">✦ Made to be kept</span><span class="badge">✦ Story-led</span>
        </div>
      </article>
      <div class="reveal" style="display:flex; flex-direction:column; gap:16px">
        <div class="glass stitch" style="border-radius:22px; overflow:hidden; aspect-ratio: 1.1/0.95; background: linear-gradient(135deg,#FFF4EA,#EAF6F4); display:grid; place-items:center; padding:14px">
          <img src="https://images.unsplash.com/photo-1452860606245-08befc0ff44b?w=800&q=80&auto=format&fit=crop" alt="Hands sewing delicate embroidery on ivory fabric in a sunlit atelier" width="800" height="680" loading="lazy" decoding="async" style="width:100%; height:100%; object-fit:cover; border-radius:16px">
        </div>
        <div class="vision-callout glass stitch">
          <div class="icon" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#006D77" stroke-width="1.6"><path d="M12 7.5 C12 7.5 14.4 5.4 16.6 7 C18.6 8.5 17.8 11.8 12 15.6 C6.2 11.8 5.4 8.5 7.4 7 C9.6 5.4 12 7.5 12 7.5Z"/><path d="M12 15.6 V18"/><path d="M8.5 18 H15.5"/></svg>
          </div>
          <div>
            <p class="quote">“Nena is a creative textile studio based in the Netherlands focused on unique, slow-crafted pieces rather than mass production, fostering craftsmanship, storytelling, and community.”</p>
            <p class="muted" style="margin:6px 0 0; font-size:13px">Based in the Netherlands · Every piece made by hand, made to last.</p>
          </div>
        </div>
        <div class="glass" style="border-radius:18px; padding:14px; display:flex; gap:12px; align-items:center" aria-hidden="true">
          <svg width="44" height="44" viewBox="0 0 44 44" fill="none"><circle cx="22" cy="22" r="20" fill="rgba(42,157,143,0.08)" stroke="rgba(42,157,143,0.14)"/><path d="M14 22 L30 14 L30 30 Z" fill="none" stroke="#4DB6AC" stroke-width="1.4"/></svg>
          <div style="font-size:13px; color:var(--muted)"><strong style="color:var(--teal-dark)">Tactile &amp; romantic</strong><br>Needle, thread, botanical line art &amp; soft stitching details — throughout the atelier.</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- WHAT WE CREATE -->
<section class="section" id="what-we-create">
  <div class="wrap">
    <div class="services-head reveal">
      <div class="eyebrow">What We Create</div>
      <h2 style="margin:10px 0 8px">Your Ideas, Made Tangible.</h2>
      <p class="muted" style="margin:0 auto; max-width:66ch">Every piece starts with an idea. Sometimes it's a clear vision, sometimes just a feeling, a memory, or a little detail you'd like to make your own. At Nena, I work with you to turn those ideas into something you can wear, use, gift, or keep. Whether you're celebrating something special, personalising an everyday object, or giving a favourite piece a new life, I'd love to hear what you have in mind.</p>
    </div>
    <div class="services-grid">
      <article class="service-card glass stitch reveal">
        <div class="service-icon" aria-hidden="true">
          <svg width="28" height="28" viewBox="0 0 28 28" fill="none" stroke="#2A9D8F" stroke-width="1.6" stroke-linecap="round"><path d="M14 6 C14 6 17.2 3.6 19.6 5.4 C22 7.2 21 11.2 14 15.8 C7 11.2 6 7.2 8.4 5.4 C10.8 3.6 14 6 14 6Z"/><path d="M9 18 Q14 21 19 18"/><path d="M7 8 L10 11"/><path d="M21 8 L18 11"/></svg>
        </div>
        <h3>Weddings &amp; Special Occasions</h3>
        <div class="subtitle">Little Details, Personal Stories.</div>
        <p>From embroidered bridal veils and personalised shoes to groom's accessories, wedding-party details, and meaningful keepsakes, Nena helps you add a personal touch to your special occasions. Birth flowers, initials, meaningful dates, colours, or even a piece of fabric from someone you love can become part of your design. Each project is created around your ideas, your style, and the details that matter to you.</p>
        <div class="spacer"></div>
        <button class="btn btn--primary" data-service="Weddings & Special Occasions" type="button">Request This Service →</button>
      </article>
      <article class="service-card glass stitch reveal">
        <div class="service-icon" aria-hidden="true">
          <svg width="28" height="28" viewBox="0 0 28 28" fill="none" stroke="#2A9D8F" stroke-width="1.6"><rect x="7" y="7" width="14" height="14" rx="3"/><path d="M10.5 14 H17.5"/><path d="M14 10.5 V17.5"/><circle cx="14" cy="14" r="7.2" stroke-dasharray="3 4" opacity="0.7"/></svg>
        </div>
        <h3>Made for You</h3>
        <div class="subtitle">Have an Idea? Let's Make It Real.</div>
        <p>Not every meaningful creation needs a special occasion. Whether it's an embroidered gift, a personalised accessory, a unique textile detail, or an idea you haven't quite figured out how to bring to life, Nena is a place to explore the possibilities. I also welcome creative commissions for celebrations, cultural events, and small businesses. Have something unusual in mind? Tell me about it. I'd love to explore what we can create together.</p>
        <div class="spacer"></div>
        <button class="btn btn--primary" data-service="Made for You" type="button">Request This Service →</button>
      </article>
      <article class="service-card glass stitch reveal">
        <div class="service-icon" aria-hidden="true">
          <svg width="28" height="28" viewBox="0 0 28 28" fill="none" stroke="#2A9D8F" stroke-width="1.6" stroke-linecap="round"><path d="M9 10 L19 18"/><path d="M19 10 L9 18"/><path d="M14 6 V9"/><path d="M14 19 V22"/><circle cx="14" cy="14" r="9" stroke-dasharray="4 4" opacity="0.5"/></svg>
        </div>
        <h3>Alterations &amp; Reimagining</h3>
        <div class="subtitle">Some Things Deserve a Second Story.</div>
        <p>Some clothes need a better fit. Others need a little repair. And some deserve to become something entirely new. From everyday alterations and repairs to creative transformations of existing garments, I help you make the most of pieces you already own. Whether it's shortening a pair of trousers, repairing a favourite sweater, or finding a new purpose for an old dress, the goal is simple: to help you keep wearing, using, and enjoying the things that matter to you.</p>
        <div class="spacer"></div>
        <button class="btn btn--primary" data-service="Alterations & Reimagining" type="button">Request This Service →</button>
      </article>
    </div>
  </div>
</section>

<!-- BEHIND THE STITCHES -->
<section class="section" id="behind-the-stitches">
  <div class="wrap">
    <div style="text-align:center; max-width:760px; margin:0 auto" class="reveal">
      <div class="eyebrow">Behind the Stitches</div>
      <h2 style="margin:10px 0 8px">Every Piece Has a Story. Here's How It Comes to Life.</h2>
      <p class="muted" style="margin:0 0 6px">Every piece has a story. Discover the ideas, memories, and creative processes behind Nena's projects, from the first inspiration to the final stitch.</p>
      <p class="muted" style="margin:0">There's often more to a handmade piece than what you see at first glance. Behind every stitch, there's an idea, a conversation, a choice of colour or material, and sometimes a memory that makes the whole creation meaningful. Here, I share the stories behind my projects: where the ideas came from, how they developed, the little decisions along the way, and the process of turning them into something real. From the first sketch to the final stitch, this is where you can discover what goes into each creation.</p>
    </div>
    <div class="filters reveal" role="toolbar" aria-label="Filter stories">
      <button class="pill" data-filter="all" aria-pressed="true" type="button">All Stories</button>
      <button class="pill" data-filter="Weddings & Special Occasions" aria-pressed="false" type="button">Weddings &amp; Special Occasions</button>
      <button class="pill" data-filter="Made for You" aria-pressed="false" type="button">Made for You</button>
      <button class="pill" data-filter="Alterations & Reimagining" aria-pressed="false" type="button">Alterations &amp; Reimagining</button>
    </div>
    <div class="portfolio-grid" id="portfolioGrid" aria-live="polite"></div>
    <!-- Fallback noscript grid -->
    <noscript>
      <div class="portfolio-grid">
        <?php foreach($portfolio as $p): ?>
          <article class="p-card glass">
            <div class="p-media"><img src="<?= htmlspecialchars($p['cover_image']) ?>" alt="<?= htmlspecialchars($p['alt_text'] ?: $p['title']) ?>" width="640" height="520" loading="lazy" decoding="async"></div>
            <div class="p-body"><div class="p-cat"><?= htmlspecialchars($p['category']) ?></div><h3 class="p-title"><?= htmlspecialchars($p['title']) ?></h3><p class="p-summary"><?= htmlspecialchars($p['summary']) ?></p></div>
          </article>
        <?php endforeach; ?>
      </div>
    </noscript>
  </div>
</section>

<!-- HOW WE WORK + FORM -->
<section class="section steps" id="lets-create-together">
  <div class="wrap">
    <div style="text-align:center; max-width:760px; margin:0 auto 14px" class="reveal">
      <div class="eyebrow">Let's Create Together</div>
      <h2 style="margin:10px 0 8px">It All Starts with an Idea.</h2>
      <p class="muted" style="margin:0">Have something in mind? Maybe you know exactly what you want, or perhaps you just have a feeling, a memory, or a little inspiration you'd like to explore. Either way, We'd love to hear about it. Every project is different. Some are simple and straightforward, while others involve more design, discussion, and collaboration. Here's a general idea of how we can work together.</p>
    </div>
    <div class="steps-grid reveal" style="margin-bottom:22px">
      <div class="thread" aria-hidden="true"></div>
      <article class="step glass stitch"><div class="step-num">01</div><h3>Tell Us Your Story</h3><p>Share your idea, the occasion, or the piece you'd like to personalise, repair, or transform. You can send inspiration photos, sketches, colours, or simply describe what you have in mind. You don't need to have everything figured out.</p></article>
      <article class="step glass stitch"><div class="step-num">02</div><h3>Let's Explore the Possibilities</h3><p>We'll talk about your ideas, materials, preferences, and timeline. For custom projects, I'll help you explore the possibilities and work towards a design that feels right for you. Once we've agreed on the details, We'll provide a quote and an estimated completion date.</p></article>
      <article class="step glass stitch"><div class="step-num">03</div><h3>Bringing It to Life</h3><p>Once everything is agreed, We'll get to work. Depending on the project, this might involve embroidery, sewing, embellishment, or transforming something you already own. We'll take care of the details and keep you updated when needed.</p></article>
      <article class="step glass stitch"><div class="step-num">04</div><h3>Made for You, Ready to Keep</h3><p>When your piece is ready, we'll arrange collection or delivery. Whether it's something to wear, gift, celebrate with, or simply enjoy again, We hope it becomes part of your story for years to come.</p></article>
    </div>

    <div class="form-shell glass stitch reveal">
      <div class="form-head">
        <h2 style="font-size:28px">Let's Create Something Together</h2>
        <p>Have something in mind? Whether it's a fully formed idea or just the beginning of one, We'd love to hear about it.</p>
        <ul>
          <li>No checkout — this is a consultation &amp; enquiry form.</li>
          <li>We reply within 1–2 working days with ideas &amp; next steps.</li>
          <li>Share photos or sketches — they help us understand your vision.</li>
        </ul>
        <div class="glass" style="border-radius:14px; padding:12px; margin-top:12px; display:flex; gap:10px; align-items:center">
          <span aria-hidden="true" style="width:38px; height:38px; border-radius:50%; display:grid; place-items:center; background:rgba(42,157,143,0.10); border:1px solid rgba(42,157,143,0.14)">✉️</span>
          <div style="font-size:13px"><strong style="color:var(--teal-dark)"><?= htmlspecialchars($contactEmail) ?></strong><br><span class="muted">Prefer Instagram? <a href="<?= htmlspecialchars($instaUrl) ?>" target="_blank" rel="noopener">DM us</a></span></div>
        </div>
      </div>

      <form id="enquiryForm" class="form-panel" action="api/submit_enquiry.php" method="post" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="text" name="website" class="honey" tabindex="-1" autocomplete="off" aria-hidden="true">
        <input type="hidden" name="contact_method" id="contact_method" value="">

        <div class="field"><label for="name">Your Name <span aria-hidden="true" style="color:#b42318">*</span></label><input id="name" name="name" type="text" required autocomplete="name" placeholder="e.g. Sophie"></div>
        <div class="field"><label for="email">Email Address <span aria-hidden="true" style="color:#b42318">*</span></label><input id="email" name="email" type="email" required autocomplete="email" placeholder="you@example.com"></div>

        <fieldset class="field" style="border:none; padding:0; margin-bottom:12px">
          <legend style="font-size:13px; font-weight:700; color:var(--teal-dark); margin-bottom:6px">What Type of Project Is It? <span aria-hidden="true" style="color:#b42318">*</span></legend>
          <div class="radio-grid" role="radiogroup" aria-label="Project type">
            <?php $types=['Weddings & Special Occasions','Made for You','Alterations & Reimagining','Other']; foreach($types as $t): ?>
              <label class="radio-card"><input type="radio" name="project_type" value="<?= htmlspecialchars($t) ?>" <?= $t==='Weddings & Special Occasions'?'required':'' ?>> <?= htmlspecialchars($t) ?></label>
            <?php endforeach; ?>
          </div>
        </fieldset>

        <div class="field"><label for="message">What Do You Have in Mind? <span aria-hidden="true" style="color:#b42318">*</span></label><textarea id="message" name="message" required placeholder="Share your story, idea, memory, colours, or details — whatever feels important…"></textarea></div>

        <div class="field">
          <label for="target_date">When Do You Need It?</label>
          <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap">
            <input id="target_date" name="target_date" type="date" style="flex:1; min-width:180px">
            <label style="display:flex; gap:6px; align-items:center; font-size:13px; font-weight:600; color:var(--muted); cursor:pointer"><input type="checkbox" id="flexible" name="flexible" value="1"> Flexible / Not sure yet</label>
          </div>
        </div>

        <div class="field">
          <label>Inspiration &amp; References <span class="muted" style="font-weight:500">(JPG, PNG, WEBP, PDF — up to 10MB each, max 8)</span></label>
          <div class="drop" id="dropZone" role="button" tabindex="0" aria-label="Upload inspiration files — drag and drop or click to browse">
            <div style="font-weight:700; color:var(--teal-dark)">Drag &amp; drop files here</div>
            <div class="muted" style="font-size:12.5px">or click to browse — thumbnails appear below</div>
          </div>
          <input id="inspiration" name="inspiration[]" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" multiple hidden>
          <div class="thumbs" id="thumbs" aria-live="polite"></div>
        </div>

        <div class="field">
          <label>Preferred Contact Method <span class="muted" style="font-weight:500">(optional)</span></label>
          <div class="contact-pills" role="toolbar" aria-label="Preferred contact method">
            <button type="button" data-value="Email" aria-pressed="false">Email</button>
            <button type="button" data-value="WhatsApp / Phone" aria-pressed="false">WhatsApp / Phone</button>
            <button type="button" data-value="Instagram" aria-pressed="false">Instagram</button>
          </div>
          <div id="contactDetailWrap" style="display:none; margin-top:8px"><input id="contact_detail" name="contact_detail" type="text" placeholder=""></div>
        </div>

        <button type="submit" class="btn btn--primary" style="width:100%; margin-top:4px">Send My Story →</button>
        <div id="formMsg" class="form-msg" role="status" aria-live="polite"></div>
        <p class="muted" style="font-size:11.5px; margin:8px 0 0; text-align:center">By sending, you agree we may contact you about your enquiry. No spam — just stitches ✨</p>
      </form>
    </div>
  </div>
</section>

</main>

<!-- Modal -->
<div class="modal" id="storyModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
  <div class="modal-backdrop" id="modalBackdrop"></div>
  <div class="modal-card">
    <div class="modal-head"><h3 class="modal-title" id="modalTitle">Story</h3><button class="modal-close" id="modalClose" aria-label="Close story">✕</button></div>
    <div class="modal-body">
      <div class="modal-media" id="modalMedia"></div>
      <div class="carousel" id="modalCarousel" style="display:none"></div>
      <div class="modal-story" id="modalStory"></div>
      <div class="modal-video" id="modalVideoWrap" style="display:none"></div>
    </div>
  </div>
</div>

<footer class="footer">
  <div class="wrap">
    <div class="footer-grid">
      <div>
        <div style="display:flex; gap:10px; align-items:center; margin-bottom:8px">
          <span style="width:36px; height:36px; border-radius:50%; display:grid; place-items:center; background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.18)">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.6"><path d="M6 20 L18 8"/><path d="M7 8 Q12 6 17 8"/><circle cx="18" cy="7.2" r="1.2" fill="white" stroke="none"/></svg>
          </span>
          <strong style="font-family:'Cormorant Garamond',serif; font-size:20px; color:#fff">Nena Studio</strong>
        </div>
        <p>Creative textile studio in the Netherlands — personalised wedding &amp; event accessories, embroidery and garment alterations. Every creation begins with your story.</p>
        <p style="margin-top:8px"><a href="<?= htmlspecialchars($instaUrl) ?>" target="_blank" rel="noopener">Instagram</a> · <a href="mailto:<?= htmlspecialchars($contactEmail) ?>"><?= htmlspecialchars($contactEmail) ?></a></p>
      </div>
      <div><h4>Explore</h4><ul><li><a href="#our-story">Our Story</a></li><li><a href="#what-we-create">What We Create</a></li><li><a href="#behind-the-stitches">Behind the Stitches</a></li><li><a href="#lets-create-together">Let's Create Together</a></li></ul></div>
      <div><h4>Atelier</h4><p>Netherlands · By appointment<br>Slow-crafted · Story-led<br>Commissions &amp; alterations welcome</p><p style="margin-top:8px"><a href="admin/login.php" rel="nofollow">Admin</a> · <a href="sitemap.xml">Sitemap</a></p></div>
    </div>
    <div class="footer-bottom"><span>© <?= date('Y') ?> Nena Studio · All stories stitched with care.</span><span>Made with ✦ in the Netherlands · <span style="opacity:0.9">Your Story, One Stitch at a Time.</span></span></div>
  </div>
</footer>

<script type="application/json" id="portfolioData"><?= $portfolioJson ?></script>
<script src="assets/js/main.js" defer></script>
</body>
</html>
