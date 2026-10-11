<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// ------------------------------------------------------------
//  Inline SVG icons
// ------------------------------------------------------------
$ICONS = [
    'clothing'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/></svg>',
    'caps'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 17h20"/><path d="M4 17V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v11"/><path d="M9 4h6v5H9z"/></svg>',
    'shoes'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 16h20"/><path d="M2 16v-4l3-1 2-3h4l1 2 3 1 3-2h4v7"/><path d="M6 16v-3"/></svg>',
    'mugs'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/><line x1="6" y1="2" x2="6" y2="4"/><line x1="10" y1="2" x2="10" y2="4"/><line x1="14" y1="2" x2="14" y2="4"/></svg>',
    'phone-cases' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="2" width="12" height="20" rx="2"/><line x1="11" y1="18" x2="13" y2="18"/></svg>',
    'bags'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
    'keyholders'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="4.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/></svg>',
    'gift-items'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/></svg>',
    'frames'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/></svg>',
    'tags-labels' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
    'gifts-more'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>',
];
$iconFor = fn(?string $f, string $slug): string => $ICONS[$slug] ?? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/></svg>';

// ------------------------------------------------------------
//  Photo library (Unsplash — swappable with your own)
// ------------------------------------------------------------
$PHOTOS = [
    'mugs'        => 'https://images.unsplash.com/photo-1514228742587-6b1558fcca3d?w=800&q=80&auto=format&fit=crop',
    'clothing'    => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80&auto=format&fit=crop',
    'caps'        => 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=800&q=80&auto=format&fit=crop',
    'bags'        => 'https://images.unsplash.com/photo-1544816155-12df9643f363?w=800&q=80&auto=format&fit=crop',
    'phone-cases' => 'https://images.unsplash.com/photo-1601784551446-20c9e07cdbdb?w=800&q=80&auto=format&fit=crop',
    'frames'      => 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?w=800&q=80&auto=format&fit=crop',
    'gift-items'  => 'https://images.unsplash.com/photo-1513885535751-8b9238bd345a?w=800&q=80&auto=format&fit=crop',
    'shoes'       => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80&auto=format&fit=crop',
    'keyholders'  => 'https://images.unsplash.com/photo-1587145820266-a5951ee6f620?w=800&q=80&auto=format&fit=crop',
    'tags-labels' => 'https://images.unsplash.com/photo-1607083206869-4c7672e72a8a?w=800&q=80&auto=format&fit=crop',
    'gifts-more'  => 'https://images.unsplash.com/photo-1549465220-1a8b9238cd48?w=800&q=80&auto=format&fit=crop',
];
$photoFor = fn(string $slug): string => $PHOTOS[$slug] ?? $PHOTOS['gift-items'];

$HERO_IMG  = 'https://images.unsplash.com/photo-1514228742587-6b1558fcca3d?w=1400&q=85&auto=format&fit=crop';
$VIDEO_THUMB = 'https://images.unsplash.com/photo-1565793298595-6a879b1d9492?w=1200&q=80&auto=format&fit=crop';

// ------------------------------------------------------------
//  Featured products
// ------------------------------------------------------------
$categories = [];
$featured   = [];
try {
    $categories = $pdo->query("SELECT id, name, slug, icon FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC")->fetchAll();
    $featured   = $pdo->query("SELECT p.id, p.name, p.slug, p.base_price, p.image, c.name AS category_name, c.slug AS category_slug FROM products p INNER JOIN categories c ON c.id = p.category_id WHERE p.is_active = 1 ORDER BY p.sort_order ASC, p.id DESC LIMIT 8")->fetchAll();
} catch (PDOException $e) { error_log('[INDEX] ' . $e->getMessage()); }

// Price floor per category (for "From ₦X" chips)
$priceFloor = [];
try {
    $rows = $pdo->query("SELECT c.slug, MIN(p.base_price) AS min_price FROM categories c INNER JOIN products p ON p.category_id = c.id AND p.is_active = 1 GROUP BY c.slug")->fetchAll();
    foreach ($rows as $r) $priceFloor[$r['slug']] = (float)$r['min_price'];
} catch (PDOException $e) { /* non-fatal */ }

$siteName = get_setting('site_name', 'Darkest Custom');
$tagline  = get_setting('site_tagline', 'Your Ideas. Our Creativity. Your Style. Your Way.');
$whatsapp = get_setting('whatsapp_number', '2347035770759');
$address  = get_setting('business_address', 'Asaba, Delta State, Nigeria');
$waLink   = 'https://wa.me/' . preg_replace('/\D/', '', $whatsapp);

$cartCount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $line) $cartCount += (int)($line['qty'] ?? 1);
}

// ------------------------------------------------------------
//  Curated homepage content — swap with real content later
// ------------------------------------------------------------

// Testimonials (replace with real ones from DB later)
$testimonials = [
    ['name' => 'Chidinma O.', 'city' => 'Lagos',   'text' => 'Ordered 20 branded mugs for our staff. The quality was far better than I expected — everyone loved them. Delivered in 3 days.', 'photo' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=120&q=80&auto=format&fit=crop&crop=faces'],
    ['name' => 'Emeka A.',    'city' => 'Asaba',   'text' => 'Best print shop in Delta. I sent my logo on WhatsApp at night, saw a preview the next morning, had my shirts by Friday.', 'photo' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=120&q=80&auto=format&fit=crop&crop=faces'],
    ['name' => 'Fatima B.',   'city' => 'Abuja',   'text' => 'I ordered a customized frame for my mum\'s birthday. She cried. That\'s the review.', 'photo' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=120&q=80&auto=format&fit=crop&crop=faces'],
    ['name' => 'Tunde K.',    'city' => 'Port Harcourt', 'text' => 'Used them for our company retreat — 50 caps and 50 tees. Bulk discount, on-time delivery, professional all round.', 'photo' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=120&q=80&auto=format&fit=crop&crop=faces'],
];

// FAQ
$faqs = [
    ['q' => 'How long does delivery take?', 'a' => 'Standard orders are ready in 2–5 working days. Bulk or corporate orders may take up to 7 days. We deliver to all 36 states.'],
    ['q' => 'Can I see a preview before printing?', 'a' => 'Yes. After you upload your design, we send a preview on WhatsApp for your approval before we print.'],
    ['q' => 'What file formats do you accept?',     'a' => 'PNG, JPG, WEBP, and SVG. For the best quality, send PNG with a transparent background at 300 DPI or higher.'],
    ['q' => 'Do you do bulk or corporate orders?',  'a' => 'Absolutely. Orders of 50+ units get up to 20% off, plus dedicated account handling. Request a quote below.'],
    ['q' => 'How do I pay?',                        'a' => 'Card, bank transfer, and USSD — all secured by Paystack. You can also chat with us on WhatsApp to arrange a quote first.'],
    ['q' => 'What if I don\'t like the result?',    'a' => 'We don\'t print until you approve the preview. If a printed item has a defect, we remake it free of charge.'],
];

// Instagram posts — drop real screenshots into /assets/img/instagram/
$igPosts = [];
$igDir = ROOT_PATH . '/assets/img/instagram';
if (is_dir($igDir)) {
    foreach (glob($igDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) as $f) {
        $igPosts[] = APP_URL . '/assets/img/instagram/' . basename($f);
    }
    $igPosts = array_slice($igPosts, 0, 8);
}

// Client logos
$clientLogos = [];
$clientsDir = ROOT_PATH . '/assets/img/clients';
if (is_dir($clientsDir)) {
    foreach (glob($clientsDir . '/*.{svg,png}', GLOB_BRACE) as $f) {
        $clientLogos[] = APP_URL . '/assets/img/clients/' . basename($f);
    }
    $clientLogos = array_slice($clientLogos, 0, 6);
}

// Sample products fallback
$samples = [
    ['Custom White Mug',  'Mugs',        'mugs',        2500,  3000],
    ['Branded Cap',       'Caps',        'caps',        4000,  5000],
    ['Printed T-Shirt',   'Clothing',    'clothing',    6000,  7500],
    ['Custom Tote Bag',   'Bags',        'bags',        5500,  6500],
    ['Phone Case Print',  'Phone Cases', 'phone-cases', 3500,  4500],
    ['Framed Print',      'Frames',      'frames',      7500,  9000],
    ['Gift Box Set',      'Gift Items',  'gift-items',  8000,  9500],
    ['Custom Sneakers',   'Shoes',       'shoes',       12000, 15000],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0B1E3F">
<title><?= e($siteName) ?> — Personalized Products for Every Occasion</title>
<meta name="description" content="Custom-printed clothing, mugs, caps, bags and gifts. Made in Asaba, delivered across Nigeria.">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://images.unsplash.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg site-navbar fixed-top" id="siteNavbar">
  <div class="container">
    <a class="navbar-brand brand" href="<?= e(APP_URL) ?>/index.php">
      <span class="brand-mark">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M3 17l4-8 5 5 5-9 4 6"/>
          <circle cx="3" cy="17" r="1" fill="currentColor"/>
          <circle cx="7" cy="9" r="1" fill="currentColor"/>
          <circle cx="12" cy="14" r="1" fill="currentColor"/>
          <circle cx="17" cy="5" r="1" fill="currentColor"/>
          <circle cx="21" cy="11" r="1" fill="currentColor"/>
        </svg>
      </span>
      <span class="brand-text"><?= e($siteName) ?></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="<?= e(APP_URL) ?>/shop.php">Shop</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Categories</a>
          <ul class="dropdown-menu">
            <?php foreach ($categories as $c): ?>
              <li><a class="dropdown-item" href="<?= e(APP_URL) ?>/shop.php?cat=<?= e($c['slug']) ?>"><?= e($c['name']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link" href="#quote">Get a quote</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($waLink) ?>" target="_blank" rel="noopener">Contact</a></li>
        <li class="nav-item ms-lg-2">
          <a class="btn btn-brand btn-sm nav-order-btn" href="<?= e(APP_URL) ?>/shop.php">
            Start order
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </a>
        </li>
      </ul>
      <a href="<?= e(APP_URL) ?>/cart.php" class="cart-link ms-lg-3" aria-label="Cart">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
        </svg>
        <span class="cart-count"<?= $cartCount === 0 ? ' hidden' : '' ?>><?= (int)$cartCount ?></span>
      </a>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-blob hero-blob-1" aria-hidden="true"></div>
  <div class="hero-blob hero-blob-2" aria-hidden="true"></div>
  <div class="hero-blob hero-blob-3" aria-hidden="true"></div>
  <div class="container position-relative">
    <div class="row align-items-center g-4 g-lg-5">
      <div class="col-lg-6 hero-copy">
        <span class="eyebrow reveal"><span class="eyebrow-dot"></span> Personalized products for every occasion</span>
        <h1 class="hero-title reveal" data-delay="1">Your ideas.<br>Our creativity.<br><em>Your style. Your way.</em></h1>
        <p class="hero-sub reveal" data-delay="2">Custom-printed clothing, mugs, caps, bags and gifts — designed by you, crafted in Asaba, delivered anywhere in Nigeria.</p>
        <div class="hero-cta reveal" data-delay="3">
          <a href="<?= e(APP_URL) ?>/shop.php" class="btn btn-brand btn-lg">
            Start customizing
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </a>
          <a href="<?= e($waLink) ?>?text=Hi%2C%20I%27d%20like%20a%20quote%20for%20a%20custom%20order" target="_blank" rel="noopener" class="btn btn-outline-brand btn-lg">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
            Get a quick quote
          </a>
        </div>
        <ul class="hero-trust reveal" data-delay="4">
          <li><span class="check">✓</span> Printed in Asaba</li>
          <li><span class="check">✓</span> Delivered in 2–5 days</li>
          <li><span class="check">✓</span> Pay with card, transfer, or USSD</li>
        </ul>
      </div>
      <div class="col-lg-6">
        <div class="hero-visual reveal" data-delay="2">
          <div class="hero-image-wrap">
            <img src="<?= e($HERO_IMG) ?>" alt="Custom printed mug on a table" loading="eager" fetchpriority="high">
            <div class="hero-image-gradient" aria-hidden="true"></div>
            <div class="hero-price-chip hero-price-chip-1"><span class="chip-dot"></span><div><small>Best seller</small><strong>Custom Mug · <?= e(money(2500, false)) ?></strong></div></div>
            <div class="hero-price-chip hero-price-chip-2"><span class="chip-emoji">🎨</span><div><small>Upload</small><strong>Your design</strong></div></div>
            <button class="hero-video-btn" type="button" data-bs-toggle="modal" data-bs-target="#videoModal" aria-label="Watch how we make it">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
              <span>Watch how we make it</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- TRUST BAR -->
<section class="trustbar">
  <div class="container">
    <div class="row g-3">
      <div class="col-6 col-md-3"><div class="trust-item"><div class="trust-icon">🎁</div><div><strong>Perfect for any occasion</strong><small>Birthdays · weddings · corporate</small></div></div></div>
      <div class="col-6 col-md-3"><div class="trust-item"><div class="trust-icon">⚡</div><div><strong>Printed in Asaba</strong><small>On your doorstep in 2–5 days</small></div></div></div>
      <div class="col-6 col-md-3"><div class="trust-item"><div class="trust-icon">🛡️</div><div><strong>Card, transfer, or USSD</strong><small>Secured by Paystack</small></div></div></div>
      <div class="col-6 col-md-3"><div class="trust-item"><div class="trust-icon">🚚</div><div><strong>All 36 states</strong><small>Free over ₦50,000</small></div></div></div>
    </div>
  </div>
</section>

<!-- LIVE ACTIVITY TICKER -->
<div class="activity-ticker">
  <div class="container ticker-inner">
    <span class="ticker-dot"></span>
    <span class="ticker-text" id="activityTicker">Someone in Lagos just ordered a Custom Mug · 12 orders in the last 24 hours</span>
  </div>
</div>

<!-- PRODUCT SHOWCASE CAROUSEL -->
<section class="section section-showcase-products">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="section-eyebrow">Made by us</span>
        <h2 class="section-title">Real pieces. Real customers.</h2>
      </div>
      <a href="<?= e(APP_URL) ?>/shop.php" class="link-more">See all <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>
  </div>
  <div class="carousel-track" id="showcaseTrack">
    <?php
    $showcase = array_merge(
        array_map(fn($s) => ['name' => $s[0], 'slug' => $s[2], 'cat' => $s[1]], $samples),
        array_map(fn($s) => ['name' => $s[0], 'slug' => $s[2], 'cat' => $s[1]], $samples),
        array_map(fn($s) => ['name' => $s[0], 'slug' => $s[2], 'cat' => $s[1]], $samples)
    );
    foreach (array_slice($showcase, 0, 10) as $i => $s): ?>
      <a href="<?= e(APP_URL) ?>/shop.php?cat=<?= e($s['slug']) ?>" class="showcase-item">
        <img src="<?= e($photoFor($s['slug'])) ?>" alt="<?= e($s['name']) ?>" loading="lazy">
        <div class="showcase-item-overlay">
          <span class="showcase-item-cat"><?= e($s['cat']) ?></span>
          <span class="showcase-item-name"><?= e($s['name']) ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- CATEGORIES -->
<section class="section" id="categories">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="section-eyebrow">Browse</span>
        <h2 class="section-title">Shop by category</h2>
      </div>
      <a href="<?= e(APP_URL) ?>/shop.php" class="link-more">View all <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>
    <?php if ($categories): ?>
      <div class="row g-3 g-md-4">
        <?php foreach ($categories as $i => $c): ?>
          <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <a href="<?= e(APP_URL) ?>/shop.php?cat=<?= e($c['slug']) ?>" class="cat-card reveal" data-delay="<?= ($i % 6) + 1 ?>">
              <span class="cat-icon"><?= $iconFor($c['icon'], $c['slug']) ?></span>
              <span class="cat-name"><?= e($c['name']) ?></span>
              <?php if (!empty($priceFloor[$c['slug']])): ?>
                <span class="cat-price">From <?= e(money($priceFloor[$c['slug']], false)) ?></span>
              <?php endif; ?>
              <span class="cat-arrow">→</span>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="text-muted">Categories coming soon.</p>
    <?php endif; ?>
  </div>
</section>

<!-- POPULAR -->
<section class="section section-alt">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="section-eyebrow">Trending</span>
        <h2 class="section-title">Popular right now</h2>
      </div>
      <a href="<?= e(APP_URL) ?>/shop.php" class="link-more">Shop all <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>
    <div class="row g-3 g-md-4">
      <?php if ($featured): ?>
        <?php foreach ($featured as $i => $p): ?>
          <div class="col-6 col-md-4 col-lg-3">
            <a href="<?= e(APP_URL) ?>/product.php?slug=<?= e($p['slug']) ?>" class="product-card reveal" data-delay="<?= ($i % 4) + 1 ?>">
              <div class="product-media">
                <?php if (!empty($p['image']) && is_file(ROOT_PATH . '/assets/img/products/' . $p['image'])): ?>
                  <img src="<?= e(APP_URL) ?>/assets/img/products/<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
                <?php else: ?>
                  <img src="<?= e($photoFor($p['category_slug'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
                <?php endif; ?>
                <span class="product-tag"><?= e($p['category_name']) ?></span>
                <button class="product-wish" aria-label="Save for later" type="button" data-wish="<?= (int)$p['id'] ?>">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                </button>
              </div>
              <div class="product-body">
                <h3 class="product-name"><?= e($p['name']) ?></h3>
                <div class="product-foot">
                  <span class="product-price"><?= e(money((float)$p['base_price'], false)) ?></span>
                  <span class="product-cta">Customize →</span>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <?php foreach ($samples as $i => [$name, $catName, $catSlug, $price, $oldPrice]): ?>
          <div class="col-6 col-md-4 col-lg-3">
            <a href="<?= e(APP_URL) ?>/shop.php?cat=<?= e($catSlug) ?>" class="product-card reveal" data-delay="<?= ($i % 4) + 1 ?>">
              <div class="product-media">
                <img src="<?= e($photoFor($catSlug)) ?>" alt="<?= e($name) ?>" loading="lazy">
                <span class="product-tag"><?= e($catName) ?></span>
                <button class="product-wish" aria-label="Save for later" type="button" data-wish="<?= e($catSlug) ?>-<?= $i ?>">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                </button>
              </div>
              <div class="product-body">
                <h3 class="product-name"><?= e($name) ?></h3>
                <div class="product-foot">
                  <span class="product-price">
                    <?= e(money($price, false)) ?>
                    <span class="product-old"><?= e(money($oldPrice, false)) ?></span>
                  </span>
                  <span class="product-cta">Customize →</span>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- CUSTOMIZER TEASER -->
<section class="section section-customizer">
  <div class="container">
    <div class="customizer-grid">
      <div class="customizer-copy">
        <span class="section-eyebrow">Try it now</span>
        <h2 class="section-title">See your design on the product — in 10 seconds.</h2>
        <p class="customizer-sub">Pick a product, upload your logo or artwork, and watch it land on a real mockup. No sign-up. No commitment.</p>
        <ul class="customizer-steps">
          <li><span class="check">1</span> Choose mug, cap, tee, or bag</li>
          <li><span class="check">2</span> Upload PNG, JPG, or SVG</li>
          <li><span class="check">3</span> We send a quote on WhatsApp</li>
        </ul>
        <a href="<?= e(APP_URL) ?>/customize.php" class="btn btn-brand btn-lg">
          Open the customizer
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
      </div>
      <div class="customizer-demo">
        <div class="customizer-frame">
          <div class="customizer-stage" id="customizerStage">
            <div class="customizer-mug"></div>
            <div class="customizer-logo" id="customizerLogo">YOUR<br>LOGO</div>
          </div>
          <div class="customizer-controls">
            <label for="demoLogoUpload" class="btn btn-outline-brand btn-sm">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              Upload design
            </label>
            <input type="file" id="demoLogoUpload" accept="image/png,image/jpeg,image/svg+xml" hidden>
            <div class="customizer-slider">
              <small>Size</small>
              <input type="range" id="demoLogoSize" min="40" max="180" value="90">
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="section">
  <div class="container">
    <div class="section-head center">
      <div>
        <span class="section-eyebrow">Simple process</span>
        <h2 class="section-title">From your idea to your door in 3 steps</h2>
        <p class="section-sub">No back-and-forth. No surprises.</p>
      </div>
    </div>
    <div class="row g-4 steps-row">
      <div class="col-md-4"><div class="step reveal" data-delay="1"><div class="step-image"><img src="<?= e($photoFor('caps')) ?>" alt="Pick your product" loading="lazy"></div><div class="step-content"><span class="step-num">1</span><h3>Pick your product</h3><p>Choose from mugs, apparel, bags, frames and more.</p></div></div></div>
      <div class="col-md-4"><div class="step reveal" data-delay="2"><div class="step-image"><img src="<?= e($photoFor('clothing')) ?>" alt="Upload your design" loading="lazy"></div><div class="step-content"><span class="step-num">2</span><h3>Upload your design</h3><p>Add your logo or artwork and preview it live on the product.</p></div></div></div>
      <div class="col-md-4"><div class="step reveal" data-delay="3"><div class="step-image"><img src="<?= e($photoFor('bags')) ?>" alt="We make and deliver" loading="lazy"></div><div class="step-content"><span class="step-num">3</span><h3>We make & deliver</h3><p>Pay securely online. We print, pack and ship to your door.</p></div></div></div>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="section section-alt">
  <div class="container">
    <div class="section-head center">
      <div>
        <span class="section-eyebrow">Loved by customers</span>
        <h2 class="section-title">What people are saying</h2>
      </div>
    </div>
    <div class="testimonials">
      <?php foreach ($testimonials as $i => $t): ?>
        <div class="testimonial-card reveal" data-delay="<?= ($i % 4) + 1 ?>">
          <div class="testimonial-stars" aria-label="5 out of 5 stars">★★★★★</div>
          <p class="testimonial-text">"<?= e($t['text']) ?>"</p>
          <div class="testimonial-author">
            <img src="<?= e($t['photo']) ?>" alt="" loading="lazy">
            <div>
              <strong><?= e($t['name']) ?></strong>
              <small><?= e($t['city']) ?></small>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CLIENT LOGOS -->
<?php if ($clientLogos): ?>
<section class="section section-clients">
  <div class="container">
    <p class="clients-label">Trusted by teams at</p>
    <div class="clients-row">
      <?php foreach ($clientLogos as $logo): ?>
        <img src="<?= e($logo) ?>" alt="Client logo" loading="lazy">
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- BULK / CORPORATE -->
<section class="section section-bulk">
  <div class="container">
    <div class="bulk-card">
      <div class="bulk-copy">
        <span class="section-eyebrow light">For teams & events</span>
        <h2>Bulk orders? Get up to 20% off.</h2>
        <p>Branded mugs, caps, tees, and gift boxes for staff, events, and corporate gifting. Dedicated account handling from quote to delivery.</p>
        <ul class="bulk-list">
          <li><span class="check-light">✓</span> 50+ units · volume pricing</li>
          <li><span class="check-light">✓</span> Free design review</li>
          <li><span class="check-light">✓</span> Invoice & PO accepted</li>
        </ul>
      </div>
      <a href="#quote" class="btn btn-white btn-lg bulk-btn">
        Request a bulk quote
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </div>
  </div>
</section>

<!-- QUOTE FORM -->
<section class="section" id="quote">
  <div class="container">
    <div class="quote-wrap">
      <div class="quote-copy">
        <span class="section-eyebrow">Get a quote</span>
        <h2 class="section-title">Tell us what you need. We reply in minutes.</h2>
        <p class="section-sub">Drop your details below and we'll send a quote on WhatsApp — no obligation, no spam.</p>
        <ul class="quote-bullets">
          <li><span class="check">✓</span> Custom orders of any size</li>
          <li><span class="check">✓</span> Design help included</li>
          <li><span class="check">✓</span> Usually replies within 10 minutes</li>
        </ul>
      </div>
      <form class="quote-form" id="quoteForm" novalidate>
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="quote">
        <label class="sr-only" for="qName">Your name</label>
        <input type="text" id="qName" name="name" placeholder="Your name" required maxlength="100" autocomplete="name">

        <label class="sr-only" for="qPhone">WhatsApp number</label>
        <input type="tel" id="qPhone" name="phone" placeholder="WhatsApp number (e.g. 070 3577 0759)" required autocomplete="tel">

        <label class="sr-only" for="qInterest">What do you need?</label>
        <select id="qInterest" name="interest" required>
          <option value="">What do you need?</option>
          <option>Mugs</option>
          <option>Caps</option>
          <option>T-Shirts / Clothing</option>
          <option>Bags</option>
          <option>Phone Cases</option>
          <option>Frames</option>
          <option>Gift Items</option>
          <option>Bulk / Corporate</option>
          <option>Something else</option>
        </select>

        <label class="sr-only" for="qQty">Estimated quantity</label>
        <input type="text" id="qQty" name="quantity" placeholder="Estimated quantity (e.g. 20)" maxlength="20" inputmode="numeric">

        <label class="sr-only" for="qMessage">Additional details</label>
        <textarea id="qMessage" name="message" placeholder="Anything else? (optional)" maxlength="500" rows="3"></textarea>

        <button type="submit" class="btn btn-brand btn-lg quote-submit">
          Send my request
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        </button>
        <p class="quote-note">🔒 Your number stays with us. No spam, ever.</p>
      </form>
    </div>
  </div>
</section>

<!-- INSTAGRAM -->
<?php if ($igPosts): ?>
<section class="section section-alt">
  <div class="container">
    <div class="section-head center">
      <div>
        <span class="section-eyebrow">Follow us</span>
        <h2 class="section-title">As seen on Instagram</h2>
        <p class="section-sub">@darkestcustom — real orders, real customers, real moments.</p>
      </div>
    </div>
    <div class="ig-grid">
      <?php foreach ($igPosts as $i => $url): ?>
        <a href="https://instagram.com/darkestcustom" target="_blank" rel="noopener" class="ig-tile reveal" data-delay="<?= ($i % 4) + 1 ?>">
          <img src="<?= e($url) ?>" alt="" loading="lazy">
        </a>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
      <a href="https://instagram.com/darkestcustom" target="_blank" rel="noopener" class="btn btn-outline-brand">Follow @darkestcustom</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- FAQ -->
<section class="section">
  <div class="container container-narrow">
    <div class="section-head center">
      <div>
        <span class="section-eyebrow">Good to know</span>
        <h2 class="section-title">Frequently asked</h2>
      </div>
    </div>
    <div class="faq-list">
      <?php foreach ($faqs as $i => $f): ?>
        <details class="faq-item"<?= $i === 0 ? ' open' : '' ?>>
          <summary>
            <span><?= e($f['q']) ?></span>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
          </summary>
          <div class="faq-body"><p><?= e($f['a']) ?></p></div>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- SHOWCASE -->
<section class="showcase">
  <div class="container">
    <div class="showcase-inner">
      <div class="showcase-copy">
        <span class="section-eyebrow light">The Darkest promise</span>
        <h2>Customize · Personalize · Make it yours</h2>
        <p>Every piece is made to order — printed, pressed, packed, and delivered with care.</p>
        <a href="<?= e($waLink) ?>?text=Hi%2C%20I%27d%20like%20to%20place%20a%20custom%20order" target="_blank" rel="noopener" class="btn btn-white btn-lg">
          Chat on WhatsApp
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
      </div>
      <div class="showcase-stats">
        <div class="stat"><strong>11+</strong><span>Product categories</span></div>
        <div class="stat"><strong>2–5</strong><span>Days turnaround</span></div>
        <div class="stat"><strong>36</strong><span>States delivered</span></div>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer class="site-footer">
  <div class="container">
    <div class="row g-4 footer-top">
      <div class="col-lg-4">
        <a href="<?= e(APP_URL) ?>/index.php" class="brand brand-footer">
          <span class="brand-mark"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 17l4-8 5 5 5-9 4 6"/><circle cx="3" cy="17" r="1" fill="currentColor"/><circle cx="7" cy="9" r="1" fill="currentColor"/><circle cx="12" cy="14" r="1" fill="currentColor"/><circle cx="17" cy="5" r="1" fill="currentColor"/><circle cx="21" cy="11" r="1" fill="currentColor"/></svg></span>
          <span class="brand-text"><?= e($siteName) ?></span>
        </a>
        <p class="footer-tagline"><?= e($tagline) ?></p>
        <div class="footer-social">
          <a href="https://instagram.com/darkestcustom" target="_blank" rel="noopener" aria-label="Instagram"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></a>
          <a href="https://tiktok.com/@darkestcustom" target="_blank" rel="noopener" aria-label="TikTok"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg></a>
          <a href="<?= e($waLink) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg></a>
        </div>
        <div class="footer-payments">
          <span class="pay-badge">VISA</span>
          <span class="pay-badge">Mastercard</span>
          <span class="pay-badge">Verve</span>
          <span class="pay-badge">USSD</span>
          <span class="pay-badge">Transfer</span>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <h4>Shop</h4>
        <ul>
          <?php foreach (array_slice($categories, 0, 6) as $c): ?>
            <li><a href="<?= e(APP_URL) ?>/shop.php?cat=<?= e($c['slug']) ?>"><?= e($c['name']) ?></a></li>
          <?php endforeach; ?>
          <?php if (empty($categories)): ?><li><a href="<?= e(APP_URL) ?>/shop.php">Shop all</a></li><?php endif; ?>
        </ul>
      </div>
      <div class="col-6 col-lg-3">
        <h4>Company</h4>
        <ul>
          <li><a href="#quote">Get a quote</a></li>
          <li><a href="#categories">Categories</a></li>
          <li><a href="#faq" onclick="document.querySelector('.faq-item')?.scrollIntoView({behavior:'smooth'});return false;">FAQ</a></li>
          <li><a href="<?= e($waLink) ?>" target="_blank" rel="noopener">WhatsApp: <?= e($whatsapp) ?></a></li>
          <li><?= e($address) ?></li>
        </ul>
      </div>
      <div class="col-lg-3">
        <h4>Start a custom order</h4>
        <p class="footer-cta-text">Send us your idea and we'll quote you in minutes.</p>
        <a href="<?= e(APP_URL) ?>/shop.php" class="btn btn-brand btn-sm w-100">Browse products</a>
        <div class="footer-ticker" id="footerTicker">🔥 12 orders in the last 24 hours</div>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</span>
      <span class="footer-strip">Customize · Personalize · Make it yours</span>
    </div>
  </div>
</footer>

<!-- Smart WhatsApp float -->
<a href="<?= e($waLink) ?>" class="wa-float" id="waFloat" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
  <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
  <span class="wa-float-label" id="waLabel">Need a quote?</span>
</a>

<!-- Mobile sticky CTA -->
<a href="<?= e(APP_URL) ?>/shop.php" class="mobile-sticky-cta" id="mobileStickyCta">
  <span>Start customizing</span>
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
</a>

<!-- Video modal -->
<div class="modal fade video-modal" id="videoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <button type="button" class="video-modal-close" data-bs-dismiss="modal" aria-label="Close">×</button>
      <div class="video-modal-body">
        <img src="<?= e($VIDEO_THUMB) ?>" alt="">
        <div class="video-modal-overlay">
          <p class="video-modal-label">Video coming soon</p>
          <p class="video-modal-sub">Drop your 15–30 second process clip here once it's shot.</p>
          <a href="<?= e($waLink) ?>" target="_blank" rel="noopener" class="btn btn-brand">Chat to order instead</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Quote success toast -->
<div class="toast-wrap" id="quoteToast" hidden>
  <div class="toast-card">
    <div class="toast-icon">✓</div>
    <div>
      <strong>Got it!</strong>
      <p>We'll reply on WhatsApp in a few minutes.</p>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="<?= e(APP_URL) ?>/assets/js/main.js" defer></script>
</body>
</html>