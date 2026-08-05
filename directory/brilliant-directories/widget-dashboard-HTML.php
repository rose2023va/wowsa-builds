<?php
if (!user::isUserLogged($_COOKIE)) return;

$member  = getUser($_COOKIE['userid'], $w);
$user_id = (int)$_COOKIE['userid'];
if (!$user_id) return;

$plan_id = (int)($member['subscription_id'] ?? 0);

$profile_done = strlen(trim($member['post_content'] ?? '')) > 0;

$photo_val  = trim($member['user_photo'] ?? '');
$photo_done = !empty($photo_val)
    && stripos($photo_val, 'no_photo') === false
    && stripos($photo_val, 'no-photo') === false
    && stripos($photo_val, 'default')  === false;

$listing_done = false;

$steps = array(
    array('icon' => '&#128100;', 'label' => 'Complete your profile',    'desc' => 'Add your bio and contact details',   'url' => '/account/contact',  'done' => $profile_done),
    array('icon' => '&#128247;', 'label' => 'Add a profile photo',      'desc' => 'Put a face to your listing',         'url' => '/account/profile',  'done' => $photo_done),
    array('icon' => '&#128203;', 'label' => 'Create your first listing','desc' => 'Race, marathon route, or swim trip',  'url' => '/account/swims/add','done' => $listing_done),
);

$done_count = count(array_filter(array_column($steps, 'done')));
$total      = count($steps);
$pct        = (int)round($done_count / $total * 100);

$eligible         = in_array($plan_id, array(3, 4, 7));
$announcement_url = $eligible ? '/post-announcement'         : '/account/upgrade';
$results_url      = $eligible ? '/account/swim-results/add'  : '/account/upgrade';

$promotion = array(
    array('icon' => '&#127946;', 'label' => 'Swim Listings',           'sub' => 'Race - Marathon Route - Swim Trip',       'url' => '/account/swims/add'),
    array('icon' => '&#128240;', 'label' => 'Community Announcements', 'sub' => 'Pre-event, recap, press release and more', 'url' => $announcement_url),
);

$database = array(
    array('icon' => '&#128202;', 'label' => 'Swim Results', 'sub' => 'Submit to WOWSA database', 'url' => $results_url),
);
?>

<div class="wowsa-db">

<div class="wowsa-wizard">
  <div class="wowsa-wizard-header">
    <div class="wowsa-section-title">Get started - complete your setup</div>
    <div class="wowsa-wizard-count"><?php echo $done_count; ?>/<?php echo $total; ?> complete</div>
  </div>
  <div class="wowsa-wizard-bar-wrap">
    <div class="wowsa-wizard-bar" style="width:<?php echo $pct; ?>%"></div>
  </div>
  <div class="wowsa-wizard-steps">
    <?php foreach ($steps as $step): ?>
      <?php if ($step['done']): ?>
        <div class="wowsa-step wowsa-step-done">
          <div class="wowsa-step-icon">&#10003;</div>
          <div class="wowsa-step-label"><?php echo htmlspecialchars($step['label']); ?></div>
          <div class="wowsa-step-desc"><?php echo htmlspecialchars($step['desc']); ?></div>
          <div class="wowsa-step-btn">Done</div>
        </div>
      <?php else: ?>
        <a class="wowsa-step wowsa-step-todo" href="<?php echo $step['url']; ?>">
          <div class="wowsa-step-icon"><?php echo $step['icon']; ?></div>
          <div class="wowsa-step-label"><?php echo htmlspecialchars($step['label']); ?></div>
          <div class="wowsa-step-desc"><?php echo htmlspecialchars($step['desc']); ?></div>
          <div class="wowsa-step-btn">Go &rarr;</div>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>

<div class="wowsa-publish">
  <div class="wowsa-section-title">Publish content</div>
  <div class="wowsa-publish-cols">

    <div class="wowsa-publish-col">
      <div class="wowsa-publish-col-title">Promotion</div>
      <?php foreach ($promotion as $type): ?>
        <a class="wowsa-type-btn" href="<?php echo $type['url']; ?>">
          <div class="wowsa-type-icon"><?php echo $type['icon']; ?></div>
          <div class="wowsa-type-text">
            <span class="wowsa-type-label"><?php echo htmlspecialchars($type['label']); ?></span>
            <span class="wowsa-type-sub"><?php echo htmlspecialchars($type['sub']); ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="wowsa-publish-col">
      <div class="wowsa-publish-col-title">Database</div>
      <?php foreach ($database as $type): ?>
        <a class="wowsa-type-btn" href="<?php echo $type['url']; ?>">
          <div class="wowsa-type-icon"><?php echo $type['icon']; ?></div>
          <div class="wowsa-type-text">
            <span class="wowsa-type-label"><?php echo htmlspecialchars($type['label']); ?></span>
            <span class="wowsa-type-sub"><?php echo htmlspecialchars($type['sub']); ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

  </div>
</div>

</div>
