<?php
/**
 * WOWSA - Streaming - Recent Races
 * BD Widget Builder widget — inline PHP only.
 *
 * Updated May 9, 2026: data_id changed from 81 (Race post type, unpublished)
 * to 84 (Swims post type) with wowsa_swim_listing_type = 'Race' filter.
 *
 * Shows the 6 most recently added Race-type Swims listings.
 * Card: photo, state_code + country_code location, start date, title, distance pills, View listing button.
 */

$limit = 6;

$rows = $w->db->get_results("
    SELECT dp.post_id, dp.post_title, dp.post_photo, dp.post_start_date
    FROM `data_posts` dp
    INNER JOIN `users_meta` AS mlt
        ON mlt.database_id = dp.post_id
        AND mlt.`database` = 'data_posts'
        AND mlt.`key` = 'wowsa_swim_listing_type'
        AND mlt.`value` = 'Race'
    WHERE dp.data_id = 84
      AND dp.post_status = 1
    ORDER BY dp.post_id DESC
    LIMIT $limit
");

if (empty($rows)) {
    echo '<p style="color:#999;font-size:14px;">No recent races found.</p>';
    return;
}

// Bulk-fetch location and distance meta
$idList  = implode(',', array_map('intval', array_column($rows, 'post_id')));
$metaRows = $w->db->get_results(
    "SELECT database_id, `key`, `value` FROM `users_meta`"
    . " WHERE `database` = 'data_posts'"
    . " AND database_id IN ($idList)"
    . " AND `key` IN ('state_code','country_code','distance_bucket')"
);
$meta = [];
foreach ($metaRows as $r) {
    $meta[$r->database_id][$r->key] = $r->value;
}
?>

<div class="wowsa-stream-grid">
  <?php foreach ($rows as $post):
    $m          = isset($meta[$post->post_id]) ? $meta[$post->post_id] : [];
    $state      = isset($m['state_code'])      ? $m['state_code']      : '';
    $country    = isset($m['country_code'])    ? $m['country_code']    : '';
    $location   = trim(implode(', ', array_filter([$state, $country])));
    $distance   = isset($m['distance_bucket']) ? $m['distance_bucket'] : '';
    $dateDisplay = (!empty($post->post_start_date) && $post->post_start_date !== '0000-00-00')
        ? date('j M Y', strtotime($post->post_start_date)) : '';
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
      <?php if ($location):   ?><p class="wowsa-stream-location"><?= htmlspecialchars($location) ?></p><?php endif; ?>
      <?php if ($dateDisplay):?><p class="wowsa-stream-date"><?= htmlspecialchars($dateDisplay) ?></p><?php endif; ?>
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

<?php include_once(__DIR__ . '/../widgets/_stream_styles.php'); // shared CSS — see note below ?>
<style>
/* Races accent */
.wowsa-stream-date{color:#0077b6}
</style>
