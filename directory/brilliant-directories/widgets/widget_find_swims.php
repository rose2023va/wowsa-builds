<?php
/**
 * WOWSA - Find Swims
 * BD Widget Builder widget — inline PHP only, no named function definitions.
 * Deployed to: Web Page Builder > /find-swims > page content [widget=WOWSA - Find Swims]
 *
 * SQL BUG FIX (May 9, 2026):
 *   The mlt alias (listing type join) is ALWAYS added to $metaJoins before the date
 *   filter block. A previous version only added it inside $dateFilterActive, but the
 *   ORDER BY clause always references mlt.value — causing a SQL error when no date
 *   filter was applied. The permanent join below resolves this.
 *
 * Post type: Swims (data_id 84)
 * Category field: wowsa_swim_listing_type (Race | Marathon Route | Swim Trip)
 * Plan IDs: Certified 7, Featured 3, Registered 2, Member 1, Unclaimed = no plan row
 * Sort: plan tier DESC, Marathon Route floats above Race/Swim Trip, then post_start_date ASC
 */

// ── Inputs ────────────────────────────────────────────────────────────────────
$filterCategory  = isset($_GET['category'])      ? trim($_GET['category'])      : '';
$filterKeyword   = isset($_GET['q'])             ? trim($_GET['q'])             : '';
$filterLocation  = isset($_GET['location'])      ? trim($_GET['location'])      : '';
$filterMonth     = isset($_GET['month'])         ? trim($_GET['month'])         : '';
$filterCountry   = isset($_GET['country'])       ? trim($_GET['country'])       : '';
$filterWaterType = isset($_GET['water_type'])    ? trim($_GET['water_type'])    : '';
$filterLevel     = isset($_GET['swimmer_level']) ? trim($_GET['swimmer_level']) : '';
$filterDistance  = isset($_GET['distance'])      ? trim($_GET['distance'])      : '';
$filterTemp      = isset($_GET['water_temp'])    ? trim($_GET['water_temp'])    : '';

$page    = max(1, intval(isset($_GET['pg']) ? $_GET['pg'] : 1));
$perPage = 12;
$offset  = ($page - 1) * $perPage;

$monthSlugToNum = [
    'jan'=>1,'feb'=>2,'mar'=>3,'apr'=>4,'may'=>5,'jun'=>6,
    'jul'=>7,'aug'=>8,'sep'=>9,'oct'=>10,'nov'=>11,'dec'=>12,
];
$monthNames = [
    'jan'=>'January','feb'=>'February','mar'=>'March','apr'=>'April',
    'may'=>'May','jun'=>'June','jul'=>'July','aug'=>'August',
    'sep'=>'September','oct'=>'October','nov'=>'November','dec'=>'December',
];

// ── Build joins and WHERE clauses ─────────────────────────────────────────────
$metaJoins  = '';
$whereExtra = '';

// ALWAYS join mlt — required for both the category WHERE clause and the ORDER BY.
// Do NOT move this inside the date filter block.
$metaJoins .= " LEFT JOIN `users_meta` AS mlt"
    . " ON mlt.database_id = dp.post_id"
    . " AND mlt.`database` = 'data_posts'"
    . " AND mlt.`key` = 'wowsa_swim_listing_type'";

// Category filter (uses mlt which is always joined above)
if ($filterCategory !== '') {
    $safeCategory = $w->db->escape($filterCategory);
    $whereExtra  .= " AND mlt.`value` = '$safeCategory'";
}

// Country
if ($filterCountry !== '') {
    $metaJoins  .= " LEFT JOIN `users_meta` AS mco"
        . " ON mco.database_id = dp.post_id"
        . " AND mco.`database` = 'data_posts'"
        . " AND mco.`key` = 'country_code'";
    $safeCountry = $w->db->escape($filterCountry);
    $whereExtra .= " AND mco.`value` = '$safeCountry'";
}

// Water type
if ($filterWaterType !== '') {
    $metaJoins  .= " LEFT JOIN `users_meta` AS mwt"
        . " ON mwt.database_id = dp.post_id"
        . " AND mwt.`database` = 'data_posts'"
        . " AND mwt.`key` = 'water_type'";
    $safeWaterType = $w->db->escape($filterWaterType);
    $whereExtra   .= " AND mwt.`value` = '$safeWaterType'";
}

// Swimmer level
if ($filterLevel !== '') {
    $metaJoins  .= " LEFT JOIN `users_meta` AS msl"
        . " ON msl.database_id = dp.post_id"
        . " AND msl.`database` = 'data_posts'"
        . " AND msl.`key` = 'swimmer_level'";
    $safeLevel   = $w->db->escape($filterLevel);
    $whereExtra .= " AND msl.`value` = '$safeLevel'";
}

// Distance bucket
if ($filterDistance !== '') {
    $metaJoins  .= " LEFT JOIN `users_meta` AS mdb"
        . " ON mdb.database_id = dp.post_id"
        . " AND mdb.`database` = 'data_posts'"
        . " AND mdb.`key` = 'distance_bucket'";
    $safeDistance = $w->db->escape($filterDistance);
    $whereExtra  .= " AND mdb.`value` = '$safeDistance'";
}

// Water temperature
if ($filterTemp !== '') {
    $metaJoins  .= " LEFT JOIN `users_meta` AS mwtp"
        . " ON mwtp.database_id = dp.post_id"
        . " AND mwtp.`database` = 'data_posts'"
        . " AND mwtp.`key` = 'typical_water_temperature'";
    $safeTemp    = $w->db->escape($filterTemp);
    $whereExtra .= " AND mwtp.`value` = '$safeTemp'";
}

// Location (state name or country name text search)
if ($filterLocation !== '') {
    $metaJoins  .= " LEFT JOIN `users_meta` AS mstn"
        . " ON mstn.database_id = dp.post_id"
        . " AND mstn.`database` = 'data_posts'"
        . " AND mstn.`key` = 'state_sn'";
    $metaJoins  .= " LEFT JOIN `users_meta` AS mctn"
        . " ON mctn.database_id = dp.post_id"
        . " AND mctn.`database` = 'data_posts'"
        . " AND mctn.`key` = 'country_sn'";
    $safeLoc     = $w->db->escape($filterLocation);
    $whereExtra .= " AND (mstn.`value` LIKE '%$safeLoc%'"
        . " OR mctn.`value` LIKE '%$safeLoc%'"
        . " OR dp.post_location LIKE '%$safeLoc%')";
}

// Keyword (title / content)
if ($filterKeyword !== '') {
    $safeKw      = $w->db->escape($filterKeyword);
    $whereExtra .= " AND (dp.post_title LIKE '%$safeKw%'"
        . " OR dp.post_content LIKE '%$safeKw%')";
}

// Date / month filter
// - Race:           filter by MONTH(post_start_date)
// - Swim Trip:      filter by season_months meta (comma-sep slugs, e.g. "jan,feb")
// - Marathon Route: always shown regardless of month filter
$dateFilterActive = ($filterMonth !== '' && isset($monthSlugToNum[$filterMonth]));
if ($dateFilterActive) {
    $monthNum    = intval($monthSlugToNum[$filterMonth]);
    $safeMonthSl = $w->db->escape($filterMonth);
    // season_months join — only needed when date filter is active
    $metaJoins  .= " LEFT JOIN `users_meta` AS msm"
        . " ON msm.database_id = dp.post_id"
        . " AND msm.`database` = 'data_posts'"
        . " AND msm.`key` = 'season_months'";
    $whereExtra .= " AND ("
        . "(mlt.`value` = 'Race' AND MONTH(dp.post_start_date) = $monthNum)"
        . " OR (mlt.`value` = 'Swim Trip' AND msm.`value` LIKE '%$safeMonthSl%')"
        . " OR mlt.`value` = 'Marathon Route'"
        . ")";
}

// ── Queries ───────────────────────────────────────────────────────────────────
$selectBase = "
    FROM `data_posts` dp
    LEFT JOIN `users_data` ud ON ud.user_id = dp.user_id
    $metaJoins
    WHERE dp.data_id = 84
      AND dp.post_status = 1
      $whereExtra
";

$countRow   = $w->db->get_row("SELECT COUNT(*) AS total $selectBase");
$totalCount = intval(isset($countRow->total) ? $countRow->total : 0);
$totalPages = (int) ceil($totalCount / $perPage);

$posts = $w->db->get_results("
    SELECT dp.post_id, dp.post_title, dp.post_photo, dp.post_start_date,
           dp.post_location, dp.post_author, dp.user_id,
           ud.subscription_id,
           mlt.`value` AS listing_type
    $selectBase
    ORDER BY
        FIELD(ud.subscription_id, 7, 3, 2, 1) DESC,
        CASE WHEN mlt.`value` = 'Marathon Route' THEN 0 ELSE 1 END ASC,
        dp.post_start_date ASC
    LIMIT $perPage OFFSET $offset
");

// ── Bulk-fetch meta for result set ────────────────────────────────────────────
$metaByPost = [];
if (!empty($posts)) {
    $idList  = implode(',', array_map('intval', array_column($posts, 'post_id')));
    $metaRows = $w->db->get_results(
        "SELECT database_id, `key`, `value`"
        . " FROM `users_meta`"
        . " WHERE `database` = 'data_posts'"
        . " AND database_id IN ($idList)"
        . " AND `key` IN ('state_sn','country_sn','state_code','country_code',"
        .     "'water_type','distance_bucket','season_months','wowsa_swim_listing_type')"
    );
    foreach ($metaRows as $r) {
        $metaByPost[$r->database_id][$r->key] = $r->value;
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
$planBadge    = [7=>'Certified', 3=>'Featured', 2=>'', 1=>'', 0=>'Unclaimed'];
$catColors    = ['Race'=>'#0077b6','Marathon Route'=>'#2d6a4f','Swim Trip'=>'#e76f51'];
$wowsaAccount = 'wowsa'; // post_author value used for unclaimed listings

// Country list for filter dropdown (distinct values from meta)
$countryRows = $w->db->get_results(
    "SELECT DISTINCT `value` FROM `users_meta`"
    . " WHERE `database` = 'data_posts' AND `key` = 'country_code'"
    . " ORDER BY `value` ASC"
);

// Current filter query string without 'pg' for use in pagination links
$filterParams = $_GET;
unset($filterParams['pg']);
?>

<div class="wowsa-find-swims">

  <!-- ── Filter bar ── -->
  <form method="GET" action="" class="wowsa-filter-form">
    <div class="wowsa-filter-row">

      <select name="category">
        <option value="">All Types</option>
        <?php foreach (['Race','Marathon Route','Swim Trip'] as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>"
            <?= $filterCategory === $cat ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <input type="text" name="q" placeholder="Keyword"
             value="<?= htmlspecialchars($filterKeyword) ?>">

      <input type="text" name="location" placeholder="Location (state or country)"
             value="<?= htmlspecialchars($filterLocation) ?>">

      <select name="month">
        <option value="">Any Month</option>
        <?php foreach ($monthNames as $slug => $name): ?>
          <option value="<?= $slug ?>"
            <?= $filterMonth === $slug ? 'selected' : '' ?>>
            <?= htmlspecialchars($name) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="country">
        <option value="">Any Country</option>
        <?php foreach ($countryRows as $cr): ?>
          <option value="<?= htmlspecialchars($cr->value) ?>"
            <?= $filterCountry === $cr->value ? 'selected' : '' ?>>
            <?= htmlspecialchars($cr->value) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="water_type">
        <option value="">Any Water Type</option>
        <?php foreach (['Ocean','Lake','River','Bay','Reservoir','Canal','Open Sea'] as $wt): ?>
          <option value="<?= $wt ?>"
            <?= $filterWaterType === $wt ? 'selected' : '' ?>>
            <?= $wt ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="swimmer_level">
        <option value="">Any Level</option>
        <?php foreach (['Beginner','Intermediate','Advanced','Elite'] as $lvl): ?>
          <option value="<?= $lvl ?>"
            <?= $filterLevel === $lvl ? 'selected' : '' ?>>
            <?= $lvl ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="distance">
        <option value="">Any Distance</option>
        <?php foreach (['Under 1km','1-5km','5-10km','10-25km','25km+'] as $dist): ?>
          <option value="<?= $dist ?>"
            <?= $filterDistance === $dist ? 'selected' : '' ?>>
            <?= $dist ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="water_temp">
        <option value="">Any Water Temp</option>
        <?php
        $temps = [
            'Hot (31C+ / 88F+)',
            'Warm (21.0-30.9C / 70-88F)',
            'Moderate (16.0-20.9C / 61-69F)',
            'Cold (5.1-15.9C / 41-60F)',
            'Ice (0-5C / 32-41F)',
        ];
        foreach ($temps as $tmp): ?>
          <option value="<?= htmlspecialchars($tmp) ?>"
            <?= $filterTemp === $tmp ? 'selected' : '' ?>>
            <?= htmlspecialchars($tmp) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button type="submit" class="wowsa-btn-search">Search</button>
      <a href="?" class="wowsa-clear-filters">Clear all</a>

    </div>
  </form>

  <!-- ── Result count ── -->
  <p class="wowsa-result-count">
    <?= number_format($totalCount) ?> listing<?= $totalCount !== 1 ? 's' : '' ?> found
  </p>

  <!-- ── Cards grid ── -->
  <div class="wowsa-cards-grid">
    <?php if (empty($posts)): ?>
      <p class="wowsa-no-results">
        No listings match your filters.
        <a href="?">Clear all filters</a> to see all swims.
      </p>

    <?php else: ?>
      <?php foreach ($posts as $post):
        $meta        = isset($metaByPost[$post->post_id]) ? $metaByPost[$post->post_id] : [];
        // listing_type from SELECT alias; fallback to meta if NULL
        $listingType = !empty($post->listing_type)
            ? $post->listing_type
            : (isset($meta['wowsa_swim_listing_type']) ? $meta['wowsa_swim_listing_type'] : 'Race');

        // Location: prefer full name fields, fall back to codes
        $stateSn   = isset($meta['state_sn'])    ? $meta['state_sn']    : '';
        $countrySn = isset($meta['country_sn'])  ? $meta['country_sn']  : '';
        if (!$stateSn)   $stateSn   = isset($meta['state_code'])   ? $meta['state_code']   : '';
        if (!$countrySn) $countrySn = isset($meta['country_code']) ? $meta['country_code'] : '';
        $locationDisplay = trim(implode(', ', array_filter([$stateSn, $countrySn])));

        // Date display logic
        $dateDisplay = '';
        if ($listingType === 'Race'
            && !empty($post->post_start_date)
            && $post->post_start_date !== '0000-00-00') {
            $dateDisplay = date('j M Y', strtotime($post->post_start_date));
        } elseif ($listingType === 'Swim Trip') {
            $seasonRaw = isset($meta['season_months']) ? $meta['season_months'] : '';
            if ($seasonRaw) {
                $slugs = array_filter(array_map('trim', explode(',', $seasonRaw)));
                $dateDisplay = implode(', ', array_map(
                    function($s) use ($monthNames) { return isset($monthNames[$s]) ? $monthNames[$s] : $s; },
                    $slugs
                ));
            }
        }
        // Marathon Routes: no date shown

        $subId     = intval(isset($post->subscription_id) ? $post->subscription_id : 0);
        $planLabel = isset($planBadge[$subId]) ? $planBadge[$subId] : '';
        $catColor  = isset($catColors[$listingType]) ? $catColors[$listingType] : '#555';

        $waterType = isset($meta['water_type'])     ? $meta['water_type']     : '';
        $distance  = isset($meta['distance_bucket'])? $meta['distance_bucket']: '';

        // Hide "Hosted by" when the post_author is the WOWSA pipeline account (unclaimed)
        $authorDisplay = '';
        if (!empty($post->post_author) && strtolower($post->post_author) !== $wowsaAccount) {
            $authorDisplay = $post->post_author;
        }

        $listingUrl = '/swims/' . intval($post->post_id);
      ?>

      <div class="wowsa-card">
        <?php if (!empty($post->post_photo)): ?>
          <a href="<?= htmlspecialchars($listingUrl) ?>">
            <img src="<?= htmlspecialchars($post->post_photo) ?>"
                 alt="<?= htmlspecialchars($post->post_title) ?>"
                 class="wowsa-card-photo">
          </a>
        <?php endif; ?>

        <div class="wowsa-card-badges">
          <?php if ($planLabel): ?>
            <span class="wowsa-badge wowsa-badge-<?= strtolower($planLabel) ?>">
              <?= htmlspecialchars($planLabel) ?>
            </span>
          <?php else: ?>
            <span></span>
          <?php endif; ?>
          <span class="wowsa-badge wowsa-badge-type"
                style="background:<?= $catColor ?>">
            <?= htmlspecialchars($listingType) ?>
          </span>
        </div>

        <div class="wowsa-card-body">
          <?php if ($locationDisplay): ?>
            <p class="wowsa-card-location"><?= htmlspecialchars($locationDisplay) ?></p>
          <?php endif; ?>
          <?php if ($dateDisplay): ?>
            <p class="wowsa-card-date"><?= htmlspecialchars($dateDisplay) ?></p>
          <?php endif; ?>
          <h3 class="wowsa-card-title">
            <a href="<?= htmlspecialchars($listingUrl) ?>">
              <?= htmlspecialchars($post->post_title) ?>
            </a>
          </h3>
          <?php if ($authorDisplay): ?>
            <p class="wowsa-card-host">Hosted by <?= htmlspecialchars($authorDisplay) ?></p>
          <?php endif; ?>
          <div class="wowsa-card-pills">
            <?php if ($waterType): ?>
              <span class="wowsa-pill"><?= htmlspecialchars($waterType) ?></span>
            <?php endif; ?>
            <?php if ($distance): ?>
              <span class="wowsa-pill"><?= htmlspecialchars($distance) ?></span>
            <?php endif; ?>
          </div>
          <a href="<?= htmlspecialchars($listingUrl) ?>" class="wowsa-btn-view">View listing</a>
        </div>
      </div>

      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- ── Pagination ── -->
  <?php if ($totalPages > 1): ?>
    <div class="wowsa-pagination">
      <?php if ($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($filterParams, ['pg' => $page - 1])) ?>"
           class="wowsa-page-btn">&laquo; Prev</a>
      <?php endif; ?>

      <?php
      $rangeStart = max(1, $page - 2);
      $rangeEnd   = min($totalPages, $page + 2);
      for ($i = $rangeStart; $i <= $rangeEnd; $i++): ?>
        <a href="?<?= http_build_query(array_merge($filterParams, ['pg' => $i])) ?>"
           class="wowsa-page-btn <?= $i === $page ? 'active' : '' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <a href="?<?= http_build_query(array_merge($filterParams, ['pg' => $page + 1])) ?>"
           class="wowsa-page-btn">Next &raquo;</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div><!-- .wowsa-find-swims -->

<style>
/* ── Find Swims page ─────────────────────────────────────────── */
.wowsa-find-swims{max-width:1200px;margin:0 auto;padding:24px 16px}

/* Filter bar */
.wowsa-filter-form{background:#f5f7fa;border-radius:8px;padding:20px;margin-bottom:24px}
.wowsa-filter-row{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
.wowsa-filter-row select,
.wowsa-filter-row input[type=text]{padding:8px 12px;border:1px solid #d0d5dd;border-radius:6px;font-size:14px;flex:1 1 160px;background:#fff}
.wowsa-btn-search{padding:9px 22px;background:#0077b6;color:#fff;border:none;border-radius:6px;font-size:14px;font-weight:600;cursor:pointer}
.wowsa-btn-search:hover{background:#005f8e}
.wowsa-clear-filters{font-size:13px;color:#666;text-decoration:underline;white-space:nowrap}

/* Result count */
.wowsa-result-count{font-size:14px;color:#666;margin-bottom:16px}

/* Cards grid */
.wowsa-cards-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
@media(max-width:900px){.wowsa-cards-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:580px){.wowsa-cards-grid{grid-template-columns:1fr}}

/* Card */
.wowsa-card{border:1px solid #e4e7ec;border-radius:10px;overflow:hidden;background:#fff;position:relative;display:flex;flex-direction:column}
.wowsa-card-photo{width:100%;height:180px;object-fit:cover;display:block}
.wowsa-card-badges{position:absolute;top:10px;left:10px;right:10px;display:flex;justify-content:space-between;align-items:flex-start;pointer-events:none}
.wowsa-badge{font-size:11px;font-weight:700;padding:3px 8px;border-radius:4px;text-transform:uppercase;letter-spacing:.4px}
.wowsa-badge-certified{background:#f0c040;color:#5a3e00}
.wowsa-badge-featured{background:#e0e7ff;color:#3730a3}
.wowsa-badge-unclaimed{background:#f3f4f6;color:#6b7280}
.wowsa-badge-type{color:#fff}

/* Card body */
.wowsa-card-body{padding:14px;flex:1;display:flex;flex-direction:column}
.wowsa-card-location{font-size:12px;color:#888;margin:0 0 2px}
.wowsa-card-date{font-size:12px;color:#0077b6;margin:0 0 6px;font-weight:600}
.wowsa-card-title{font-size:15px;font-weight:700;margin:0 0 6px;line-height:1.3}
.wowsa-card-title a{color:#1a1a2e;text-decoration:none}
.wowsa-card-title a:hover{color:#0077b6}
.wowsa-card-host{font-size:12px;color:#666;margin:0 0 8px}
.wowsa-card-pills{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px;margin-top:auto}
.wowsa-pill{font-size:11px;background:#f0f4f8;color:#444;padding:3px 8px;border-radius:12px}
.wowsa-btn-view{display:inline-block;padding:7px 16px;background:#0077b6;color:#fff;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;text-align:center}
.wowsa-btn-view:hover{background:#005f8e;color:#fff}

/* No results */
.wowsa-no-results{grid-column:1/-1;text-align:center;padding:48px 20px;color:#666;font-size:15px}

/* Pagination */
.wowsa-pagination{display:flex;gap:8px;justify-content:center;margin-top:36px;flex-wrap:wrap}
.wowsa-page-btn{padding:7px 14px;border:1px solid #d0d5dd;border-radius:6px;color:#0077b6;text-decoration:none;font-size:14px;background:#fff}
.wowsa-page-btn:hover{background:#f0f4f8}
.wowsa-page-btn.active{background:#0077b6;color:#fff;border-color:#0077b6}
</style>
