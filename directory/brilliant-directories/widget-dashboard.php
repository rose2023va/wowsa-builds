<?php
/**
 * WOWSA - Dashboard Widget
 * Toolbox > Widget Builder > New Widget > paste full contents
 * Widget Name: WOWSA - Dashboard
 * Placement: Design > Member Homepage > Dashboard Header Content
 *
 * Plan IDs: 1 = Member, 2 = Registered, 3 = Featured, 4 = Admin, 7 = Certified
 */

if (!user::isUserLogged($_COOKIE)) return;

$member  = getUser($_COOKIE['userid'], $w);
$user_id = (int)$_COOKIE['userid'];
if (!$user_id) return;

$plan_id = (int)($member['subscription_id'] ?? 0);

// ── Setup Wizard checks ───────────────────────────────────────────────────────
$profile_done = strlen(trim($member['post_content'] ?? '')) > 0;

$photo_val  = trim($member['user_photo'] ?? '');
$photo_done = !empty($photo_val)
    && stripos($photo_val, 'no_photo') === false
    && stripos($photo_val, 'no-photo') === false
    && stripos($photo_val, 'default')  === false;

$listing_done = false; // DB not available in Widget Builder scope
$all_done     = $profile_done && $photo_done && $listing_done;

$steps = array(
    array('icon' => '&#128100;', 'label' => 'Complete your profile',    'desc' => 'Add your bio and contact details',  'url' => '/account/contact',  'done' => $profile_done),
    array('icon' => '&#128247;', 'label' => 'Add a profile photo',      'desc' => 'Put a face to your listing',        'url' => '/account/profile',  'done' => $photo_done),
    array('icon' => '&#128203;', 'label' => 'Create your first listing','desc' => 'Race, marathon route, or swim trip', 'url' => '/account/swims/add','done' => $listing_done),
);

$done_count = count(array_filter(array_column($steps, 'done')));
$total      = count($steps);
$pct        = (int)round($done_count / $total * 100);

// ── Publish Content (plan-aware) ──────────────────────────────────────────────
$announcement_url = in_array($plan_id, array(3, 4, 7)) ? '/post-announcement' : '/account/upgrade';

$promotion = array(
    array('icon' => '&#127946;', 'label' => 'Swim Listings',             'sub' => 'Race - Marathon Route - Swim Trip',       'url' => '/account/swims/add'),
    array('icon' => '&#128240;', 'label' => 'Community Announcements',   'sub' => 'Pre-event, recap, press release and more', 'url' => $announcement_url),
);

$database = array(
    array('icon' => '&#128202;', 'label' => 'Swim Results', 'sub' => 'Submit to WOWSA database', 'url' => '/account/swim-results/add'),
);
?>

<style>
.wowsa-db { font-family: inherit; }
.wowsa-wizard, .wowsa-publish {
  background: #fff;
  border: 1px solid #e0dbd3;
  border-radius: 10px;
  padding: 20px 24px;
  margin-bottom: 16px;
}
.wowsa-section-title {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #8a8278;
  margin-bottom: 10px;
}
.wowsa-wizard-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}
.wowsa-wizard-count {
  font-size: 12px;
  font-weight: 600;
  color: #2e7d5e;
  background: #f0faf5;
  border: 1px solid #c3e8d4;
  border-radius: 20px;
  padding: 2px 10px;
}
.wowsa-wizard-bar-wrap {
  background: #f0ede8;
  border-radius: 99px;
  height: 5px;
  margin-bottom: 18px;
  overflow: hidden;
}
.wowsa-wizard-bar {
  background: #2e7d5e;
  height: 5px;
  border-radius: 99px;
}
.wowsa-wizard-steps {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}
@media (max-width: 600px) {
  .wowsa-wizard-steps { grid-template-columns: 1fr; }
}
.wowsa-step {
  border-radius: 8px;
  padding: 16px 14px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  text-align: center;
  text-decoration: none;
}
.wowsa-step-todo { background: #f0faf5; border: 2px solid #2e7d5e; }
.wowsa-step-todo:hover { background: #e2f5ec; }
.wowsa-step-done { background: #f7f7f6; border: 2px solid #ddd; pointer-events: none; }
.wowsa-step-icon {
  width: 44px; height: 44px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center; font-size: 20px;
}
.wowsa-step-todo .wowsa-step-icon { background: #d0f0e2; }
.wowsa-step-done .wowsa-step-icon { background: #ebebeb; }
.wowsa-step-label { font-size: 13px; font-weight: 700; line-height: 1.3; }
.wowsa-step-todo .wowsa-step-label { color: #1a3a2e; }
.wowsa-step-done .wowsa-step-label { color: #bbb; text-decoration: line-through; }
.wowsa-step-desc { font-size: 11px; line-height: 1.4; }
.wowsa-step-todo .wowsa-step-desc { color: #5a8a72; }
.wowsa-step-done .wowsa-step-desc { color: #ccc; }
.wowsa-step-btn { font-size: 12px; font-weight: 600; padding: 4px 14px; border-radius: 20px; margin-top: 2px; }
.wowsa-step-todo .wowsa-step-btn { background: #2e7d5e; color: #fff; }
.wowsa-step-done .wowsa-step-btn { background: #e0e0e0; color: #aaa; }

/* Publish content */
.wowsa-publish-cols {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}
@media (max-width: 600px) {
  .wowsa-publish-cols { grid-template-columns: 1fr; }
}
.wowsa-publish-col-title {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: #aaa;
  margin-bottom: 8px;
}
.wowsa-type-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  border-radius: 8px;
  text-decoration: none;
  border: 1.5px solid #d0e8dc;
  background: #f7fbf9;
  margin-bottom: 8px;
}
.wowsa-type-btn:last-child { margin-bottom: 0; }
.wowsa-type-btn:hover { background: #e6f4ed; border-color: #2e7d5e; }
.wowsa-type-icon {
  width: 36px; height: 36px; border-radius: 7px;
  background: #d0eedd;
  display: flex; align-items: center; justify-content: center;
  font-size: 17px; flex-shrink: 0;
}
.wowsa-type-text { display: flex; flex-direction: column; }
.wowsa-type-label { font-size: 13px; font-weight: 700; color: #1a3a2e; line-height: 1.2; }
.wowsa-type-sub   { font-size: 11px; color: #7a9e8e; margin-top: 2px; }
</style>

<div class="wowsa-db">

<?php if (!$all_done): ?>
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
<?php endif; ?>

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
