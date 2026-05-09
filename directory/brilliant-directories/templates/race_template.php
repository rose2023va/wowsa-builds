<?php
/**
 * WOWSA - Swims Listing Detail Page Template
 * Brilliant Directories > My Content > Edit Post Settings > Swims > Detail Page Design
 *
 * Post type: Swims (data_id 84, slug /swims)
 * Key fields read from $post[]:
 *   post_title, post_content, post_photo, post_start_date, post_location,
 *   post_video (BD native — stores pre-rendered iframe embed string),
 *   post_author, post_gallery (BD native gallery field),
 *   post_lat, post_lng (for map)
 *
 * Custom meta read via widget("WOWSA - Claim This Listing") / inline getMetaData():
 *   wowsa_swim_listing_type, water_type, distance_bucket, swimmer_level,
 *   typical_water_temperature, country_code, state_sn, country_sn,
 *   wowsa_video_embed (not used — BD native post_video preferred)
 *
 * Sections:
 *   1. Hero: photo, title, category badge, plan badge, location, date
 *   2. Main content: description
 *   3. Video (post_video) — responsive, width/height/style attrs stripped
 *   4. Gallery
 *   5. Map (Google Maps — API key configured in Settings > Integrations)
 *   6. Sidebar: details, socials widget, claim widget
 */
?>

<div class="wowsa-listing-wrap">

  <!-- ── 1. Hero ── -->
  <div class="wowsa-listing-hero">
    <?php if (!empty($post['post_photo'])): ?>
      <img src="<?= htmlspecialchars($post['post_photo']) ?>"
           alt="<?= htmlspecialchars($post['post_title']) ?>"
           class="wowsa-hero-photo">
    <?php endif; ?>

    <div class="wowsa-hero-overlay">
      <?php
      // Category and plan badges
      $listingType = getMetaData($post['post_id'], 'wowsa_swim_listing_type', $w);
      $catColors   = ['Race'=>'#0077b6','Marathon Route'=>'#2d6a4f','Swim Trip'=>'#e76f51'];
      $catColor    = isset($catColors[$listingType]) ? $catColors[$listingType] : '#555';

      $planBadges  = [7=>'Certified',3=>'Featured'];
      $subId       = intval($post['subscription_id'] ?? 0);
      $planLabel   = isset($planBadges[$subId]) ? $planBadges[$subId] : '';
      ?>
      <div class="wowsa-hero-badges">
        <?php if ($planLabel): ?>
          <span class="wowsa-badge wowsa-badge-<?= strtolower($planLabel) ?>">
            <?= htmlspecialchars($planLabel) ?>
          </span>
        <?php endif; ?>
        <?php if ($listingType): ?>
          <span class="wowsa-badge wowsa-badge-type" style="background:<?= $catColor ?>">
            <?= htmlspecialchars($listingType) ?>
          </span>
        <?php endif; ?>
      </div>

      <h1 class="wowsa-hero-title"><?= htmlspecialchars($post['post_title']) ?></h1>

      <?php
      $stateSn   = getMetaData($post['post_id'], 'state_sn',   $w);
      $countrySn = getMetaData($post['post_id'], 'country_sn', $w);
      if (!$stateSn)   $stateSn   = getMetaData($post['post_id'], 'state_code',   $w);
      if (!$countrySn) $countrySn = getMetaData($post['post_id'], 'country_code', $w);
      $locationDisplay = trim(implode(', ', array_filter([$stateSn, $countrySn])));
      ?>
      <?php if ($locationDisplay): ?>
        <p class="wowsa-hero-location">
          <span class="wowsa-icon-pin">&#x1F4CD;</span>
          <?= htmlspecialchars($locationDisplay) ?>
        </p>
      <?php endif; ?>

      <?php if ($listingType === 'Race'
          && !empty($post['post_start_date'])
          && $post['post_start_date'] !== '0000-00-00'): ?>
        <p class="wowsa-hero-date">
          <?= date('l, j F Y', strtotime($post['post_start_date'])) ?>
        </p>
      <?php endif; ?>
    </div>
  </div><!-- .wowsa-listing-hero -->

  <!-- ── 2. Body: content + sidebar ── -->
  <div class="wowsa-listing-body">

    <!-- Main column -->
    <div class="wowsa-listing-main">

      <!-- Description -->
      <?php if (!empty($post['post_content'])): ?>
        <div class="wowsa-section">
          <h2 class="wowsa-section-heading">About this listing</h2>
          <div class="wowsa-listing-description">
            <?= $post['post_content'] ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- ── 3. Video ── -->
      <?php if (!empty($post['post_video'])): ?>
        <div class="wowsa-section">
          <h2 class="wowsa-section-heading">Video</h2>
          <div class="wowsa-video-wrap">
            <div class="embed-responsive embed-responsive-16by9">
              <?php
              // BD native post_video stores a complete iframe embed string with hardcoded
              // width, height, and style attributes. Strip them so the responsive CSS wrapper
              // controls dimensions instead.
              $videoHtml = $post['post_video'];
              $videoHtml = preg_replace('/\s+width="[^"]*"/',  '', $videoHtml);
              $videoHtml = preg_replace('/\s+height="[^"]*"/', '', $videoHtml);
              $videoHtml = preg_replace('/\s+style="[^"]*"/',  '', $videoHtml);
              echo $videoHtml;
              ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- ── 4. Gallery ── -->
      <?php if (!empty($post['post_gallery'])): ?>
        <div class="wowsa-section">
          <h2 class="wowsa-section-heading">Gallery</h2>
          <div class="wowsa-gallery">
            <?php
            $photos = is_array($post['post_gallery'])
                ? $post['post_gallery']
                : explode(',', $post['post_gallery']);
            foreach ($photos as $photo):
                $photo = trim($photo);
                if (!$photo) continue;
            ?>
              <a href="<?= htmlspecialchars($photo) ?>" class="wowsa-gallery-item"
                 target="_blank" rel="noopener">
                <img src="<?= htmlspecialchars($photo) ?>" alt="Gallery photo">
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- ── 5. Map ── -->
      <?php if (!empty($post['post_lat']) && !empty($post['post_lng'])): ?>
        <div class="wowsa-section">
          <h2 class="wowsa-section-heading">Location</h2>
          <div id="wowsa-map" class="wowsa-map-wrap"></div>
        </div>
        <script>
        (function(){
          function initWowsaMap(){
            var lat = parseFloat("<?= floatval($post['post_lat']) ?>");
            var lng = parseFloat("<?= floatval($post['post_lng']) ?>");
            var map = new google.maps.Map(document.getElementById('wowsa-map'), {
              center: {lat: lat, lng: lng},
              zoom: 10,
              mapTypeId: 'terrain'
            });
            new google.maps.Marker({
              position: {lat: lat, lng: lng},
              map: map,
              title: <?= json_encode($post['post_title']) ?>
            });
          }
          if (typeof google !== 'undefined' && google.maps) {
            initWowsaMap();
          } else {
            window.initWowsaMap = initWowsaMap;
          }
        })();
        </script>
      <?php endif; ?>

    </div><!-- .wowsa-listing-main -->

    <!-- Sidebar -->
    <aside class="wowsa-listing-sidebar">

      <!-- Details card -->
      <div class="wowsa-sidebar-card">
        <h3 class="wowsa-sidebar-heading">Details</h3>
        <dl class="wowsa-detail-list">

          <?php if ($listingType): ?>
            <dt>Type</dt>
            <dd><?= htmlspecialchars($listingType) ?></dd>
          <?php endif; ?>

          <?php
          $waterType = getMetaData($post['post_id'], 'water_type', $w);
          if ($waterType): ?>
            <dt>Water Type</dt>
            <dd><?= htmlspecialchars($waterType) ?></dd>
          <?php endif; ?>

          <?php
          $distanceBucket = getMetaData($post['post_id'], 'distance_bucket', $w);
          if ($distanceBucket): ?>
            <dt>Distance</dt>
            <dd><?= htmlspecialchars($distanceBucket) ?></dd>
          <?php endif; ?>

          <?php
          $swimmerLevel = getMetaData($post['post_id'], 'swimmer_level', $w);
          if ($swimmerLevel): ?>
            <dt>Swimmer Level</dt>
            <dd><?= htmlspecialchars($swimmerLevel) ?></dd>
          <?php endif; ?>

          <?php
          $waterTemp = getMetaData($post['post_id'], 'typical_water_temperature', $w);
          if ($waterTemp): ?>
            <dt>Water Temp</dt>
            <dd><?= htmlspecialchars($waterTemp) ?></dd>
          <?php endif; ?>

          <?php if (!empty($post['post_url'])): ?>
            <dt>Register</dt>
            <dd>
              <a href="<?= htmlspecialchars($post['post_url']) ?>"
                 target="_blank" rel="noopener nofollow">
                Registration &rarr;
              </a>
            </dd>
          <?php endif; ?>

        </dl>
      </div>

      <!-- Socials widget -->
      <?php echo widget("WOWSA - Socials"); ?>

      <!-- Claim This Listing widget -->
      <?php echo widget("WOWSA - Claim This Listing"); ?>

    </aside><!-- .wowsa-listing-sidebar -->

  </div><!-- .wowsa-listing-body -->

</div><!-- .wowsa-listing-wrap -->

<style>
/* ── Swims listing detail page ──────────────────────────────── */
.wowsa-listing-wrap{max-width:1100px;margin:0 auto;padding:0 16px 48px}

/* Hero */
.wowsa-listing-hero{position:relative;border-radius:12px;overflow:hidden;margin-bottom:32px;background:#1a1a2e}
.wowsa-hero-photo{width:100%;height:400px;object-fit:cover;display:block;opacity:.7}
.wowsa-hero-overlay{position:absolute;bottom:0;left:0;right:0;padding:24px 28px;background:linear-gradient(transparent,rgba(0,0,0,.75))}
.wowsa-hero-badges{display:flex;gap:8px;margin-bottom:10px}
.wowsa-badge{font-size:11px;font-weight:700;padding:4px 10px;border-radius:4px;text-transform:uppercase;letter-spacing:.4px}
.wowsa-badge-certified{background:#f0c040;color:#5a3e00}
.wowsa-badge-featured{background:#e0e7ff;color:#3730a3}
.wowsa-badge-type{color:#fff}
.wowsa-hero-title{color:#fff;font-size:28px;font-weight:800;margin:0 0 8px;line-height:1.2}
.wowsa-hero-location{color:rgba(255,255,255,.85);font-size:15px;margin:0 0 4px}
.wowsa-hero-date{color:rgba(255,255,255,.85);font-size:14px;font-weight:600;margin:0}
@media(max-width:680px){
  .wowsa-hero-photo{height:240px}
  .wowsa-hero-title{font-size:20px}
}

/* Body layout */
.wowsa-listing-body{display:grid;grid-template-columns:1fr 300px;gap:32px;align-items:start}
@media(max-width:860px){.wowsa-listing-body{grid-template-columns:1fr}}

/* Sections */
.wowsa-section{margin-bottom:32px}
.wowsa-section-heading{font-size:18px;font-weight:700;margin:0 0 14px;padding-bottom:8px;border-bottom:2px solid #e4e7ec;color:#1a1a2e}
.wowsa-listing-description{font-size:15px;line-height:1.7;color:#333}

/* Video */
.wowsa-video-wrap{max-width:640px;margin:0 auto}
@media(max-width:768px){.wowsa-video-wrap{max-width:100%}}
.wowsa-video-wrap .embed-responsive{position:relative;padding-bottom:56.25%;height:0;overflow:hidden}
.wowsa-video-wrap .embed-responsive iframe{position:absolute;top:0;left:0;width:100% !important;height:100% !important}

/* Gallery */
.wowsa-gallery{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
@media(max-width:560px){.wowsa-gallery{grid-template-columns:repeat(2,1fr)}}
.wowsa-gallery-item img{width:100%;height:140px;object-fit:cover;border-radius:6px;display:block}

/* Map */
.wowsa-map-wrap{width:100%;height:360px;border-radius:8px;overflow:hidden;border:1px solid #e4e7ec}

/* Sidebar */
.wowsa-sidebar-card{background:#fff;border:1px solid #e4e7ec;border-radius:10px;padding:20px;margin-bottom:20px}
.wowsa-sidebar-heading{font-size:15px;font-weight:700;margin:0 0 14px;color:#1a1a2e}
.wowsa-detail-list{margin:0;padding:0}
.wowsa-detail-list dt{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#888;margin:10px 0 2px}
.wowsa-detail-list dt:first-child{margin-top:0}
.wowsa-detail-list dd{font-size:14px;color:#333;margin:0}
.wowsa-detail-list a{color:#0077b6;text-decoration:none}
.wowsa-detail-list a:hover{text-decoration:underline}
</style>
