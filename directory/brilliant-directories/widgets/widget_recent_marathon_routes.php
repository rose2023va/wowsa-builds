<?php
/**
 * WOWSA - Streaming - Recent Marathon Routes
 * BD Widget Builder widget — inline PHP only.
 *
 * Updated May 9, 2026: data_id changed from 82 (Marathon Route post type, unpublished)
 * to 84 (Swims post type) with wowsa_swim_listing_type = 'Marathon Route' filter.
 *
 * Shows the 6 most recently added Marathon Route-type Swims listings.
 * Card: photo, state_code + country_code location, title, distance pills, View listing button.
 * No start date shown (Marathon Routes are permanent / undated).
 */

$limit = 6;

$rows = $w->db->get_results("
    SELECT dp.post_id, dp.post_title, dp.post_photo
    FROM `data_posts` dp
    INNER JOIN `users_meta` AS mlt
        ON mlt.database_id = dp.post_id
        AND mlt.`database` = 'data_posts'
        AND mlt.`key` = 'wowsa_swim_listing_type'
        AND mlt.`value` = 'Marathon Route'
    WHERE dp.data_id = 84
      AND dp.post_status = 1
    ORDER BY dp.post_id DESC
    LIMIT $limit
");

if (empty($rows)) {
    echo '<p style="color:#999;font-size:14px;">No recent marathon routes found.</p>';
    return;
}

$idList  = implode(',', array_map('intval', array_column($rows, 'post_id')));
$metaRows = $w->db->get_results(
    "SELECT database_id, `key`, `value` FROM `users_meta`"
    . " WHERE `database` = 'data_posts'"
    . " AND database_id IN ($idList)"
    . " AND `key` IN ('state_code','country_code','distance_bucket','route_distance')"
);
$meta = [];
foreach ($metaRows as $r) {
    $meta[$r->database_id][$r->key] = $r->value;
}
?>

<div class="wowsa-stream-grid">
  <?php foreach ($rows as $post):
    $m        = isset($meta[$post->post_id]) ? $meta[$post->post_id] : [];
    $state    = isset($m['state_code'])      ? $m['state_code']      : '';
    $country  = isset($m['country_code'])    ? $m['country_code']    : '';
    $location = trim(implode(', ', array_filter([$state, $country])));
    $distance = isset($m['distance_bucket']) ? $m['distance_bucket']
              : (isset($m['route_distance'])  ? $m['route_distance'] . ' km' : '');
    $listingUrl = '/swims/' . intval($post->post_id);
  ?>
  <div class="wowsa-stream-card">
    <?php if (!empty($post->post_photo)): ?>
      <a href="<?= htmlspecialchars($listingUrl) ?>">
        <img src="<?= htmlspecialchars($post->post_photo) ?>"
             alt="<?= htmlspecialchars($post->post_title) ?>"
             class="wowsa-stream-photo">
      </a>
    <?php endif; ?>
    <div class="wowsa-stream-body">
      <?php if ($location): ?><p class="wowsa-stream-location"><?= htmlspecialchars($location) ?></p><?php endif; ?>
      <h4 class="wowsa-stream-title">
        <a href="<?= htmlspecialchars($listingUrl) ?>"><?= htmlspecialchars($post->post_title) ?></a>
      </h4>
      <?php if ($distance): ?>
        <div class="wowsa-stream-pills"><span class="wowsa-pill"><?= htmlspecialchars($distance) ?></span></div>
      <?php endif; ?>
      <a href="<?= htmlspecialchars($listingUrl) ?>" class="wowsa-btn-view">View listing</a>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<style>
/* ── Shared streaming card styles ─────────────────────────────── */
.wowsa-stream-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
@media(max-width:900px){.wowsa-stream-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:560px){.wowsa-stream-grid{grid-template-columns:1fr}}
.wowsa-stream-card{border:1px solid #e4e7ec;border-radius:10px;overflow:hidden;background:#fff}
.wowsa-stream-photo{width:100%;height:160px;object-fit:cover;display:block}
.wowsa-stream-body{padding:12px}
.wowsa-stream-location{font-size:12px;color:#888;margin:0 0 2px}
.wowsa-stream-date{font-size:12px;font-weight:600;margin:0 0 6px}
.wowsa-stream-title{font-size:14px;font-weight:700;margin:0 0 8px;line-height:1.3}
.wowsa-stream-title a{color:#1a1a2e;text-decoration:none}
.wowsa-stream-title a:hover{color:#2d6a4f}
.wowsa-stream-pills{display:flex;flex-wrap:wrap;gap:5px;margin-bottom:10px}
.wowsa-pill{font-size:11px;background:#f0f4f8;color:#444;padding:3px 8px;border-radius:12px}
.wowsa-btn-view{display:inline-block;padding:6px 14px;background:#2d6a4f;color:#fff;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none}
.wowsa-btn-view:hover{background:#1f4d39;color:#fff}
</style>
