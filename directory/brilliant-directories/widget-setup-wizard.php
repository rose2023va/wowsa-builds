<?php
/**
 * WOWSA - Setup Wizard
 * Toolbox > Widget Builder > New Widget > paste full contents
 * Placement: Design > Member Homepage > Dashboard Header Content
 *
 * Detects completion of each step automatically:
 *   1. Complete profile  — post_content (bio) is not empty
 *   2. Profile photo     — user_photo field is set and non-default
 *   3. Create listing    — at least 1 published post in data_id 81, 82, or 83
 *
 * Hides itself entirely once all 3 steps are complete.
 */

if (!user::isUserLogged($_COOKIE)) return;

$member  = getUser($_COOKIE['userid'], $w);
$user_id = (int)($member['id'] ?? 0);
if (!$user_id) return;

// ── Step 1: Profile complete (bio filled) ─────────────────────────────────────
$profile_done = strlen(trim($member['post_content'] ?? '')) > 0;

// ── Step 2: Profile photo uploaded ───────────────────────────────────────────
// BD stores the photo path in user_photo. Empty or containing 'no_photo' /
// 'no-photo' / 'default' means the member hasn't uploaded one yet.
$photo_val   = trim($member['user_photo'] ?? '');
$photo_done  = !empty($photo_val)
    && stripos($photo_val, 'no_photo')  === false
    && stripos($photo_val, 'no-photo')  === false
    && stripos($photo_val, 'default')   === false;

// ── Step 3: Has at least one published swim listing ───────────────────────────
$rows         = $w->db->get_results(
    "SELECT id FROM data_posts
      WHERE user_id    = $user_id
        AND post_status = 1
        AND data_id    IN (81, 82, 83)
      LIMIT 1"
);
$listing_done = !empty($rows);

// ── Hide widget if everything is done ────────────────────────────────────────
if ($profile_done && $photo_done && $listing_done) return;

$steps = [
    [
        'icon'  => '👤',
        'label' => 'Complete your profile',
        'desc'  => 'Add your bio and contact details',
        'url'   => '/account/contact',
        'done'  => $profile_done,
    ],
    [
        'icon'  => '📷',
        'label' => 'Add a profile photo',
        'desc'  => 'Put a face to your listing',
        'url'   => '/account/profile',
        'done'  => $photo_done,
    ],
    [
        'icon'  => '📋',
        'label' => 'Create your first listing',
        'desc'  => 'Race, marathon route, or swim trip',
        'url'   => '/account/swims/add',
        'done'  => $listing_done,
    ],
];

$done_count = count(array_filter(array_column($steps, 'done')));
$total      = count($steps);
$pct        = (int)round($done_count / $total * 100);
?>

<style>
.wowsa-wizard {
  background: #fff;
  border: 1px solid #e0dbd3;
  border-radius: 10px;
  padding: 20px 24px;
  margin-bottom: 20px;
  font-family: inherit;
}
.wowsa-wizard-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}
.wowsa-wizard-title {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #8a8278;
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
  width: <?= $pct ?>%;
}
.wowsa-wizard-steps {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
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
.wowsa-step.todo {
  background: #f0faf5;
  border: 2px solid #2e7d5e;
  transition: transform 0.15s, box-shadow 0.15s;
}
.wowsa-step.todo:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(46,125,94,0.15);
}
.wowsa-step.done {
  background: #f7f7f6;
  border: 2px solid #ddd;
  pointer-events: none;
}
.wowsa-step-icon {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}
.wowsa-step.todo .wowsa-step-icon { background: #d0f0e2; }
.wowsa-step.done .wowsa-step-icon { background: #ebebeb; }
.wowsa-step-label {
  font-size: 13px;
  font-weight: 700;
  line-height: 1.3;
}
.wowsa-step.todo .wowsa-step-label { color: #1a3a2e; }
.wowsa-step.done .wowsa-step-label { color: #bbb; text-decoration: line-through; }
.wowsa-step-desc {
  font-size: 11px;
  line-height: 1.4;
}
.wowsa-step.todo .wowsa-step-desc { color: #5a8a72; }
.wowsa-step.done .wowsa-step-desc { color: #ccc; }
.wowsa-step-btn {
  font-size: 12px;
  font-weight: 600;
  padding: 4px 14px;
  border-radius: 20px;
  margin-top: 2px;
}
.wowsa-step.todo .wowsa-step-btn { background: #2e7d5e; color: #fff; }
.wowsa-step.done .wowsa-step-btn { background: #e0e0e0; color: #aaa; }
</style>

<div class="wowsa-wizard">
  <div class="wowsa-wizard-header">
    <div class="wowsa-wizard-title">Get started — complete your setup</div>
    <div class="wowsa-wizard-count"><?= $done_count ?>/<?= $total ?> complete</div>
  </div>
  <div class="wowsa-wizard-bar-wrap">
    <div class="wowsa-wizard-bar"></div>
  </div>
  <div class="wowsa-wizard-steps">
    <?php foreach ($steps as $step): ?>
      <?php if ($step['done']): ?>
        <div class="wowsa-step done">
          <div class="wowsa-step-icon">✓</div>
          <div class="wowsa-step-label"><?= htmlspecialchars($step['label']) ?></div>
          <div class="wowsa-step-desc"><?= htmlspecialchars($step['desc']) ?></div>
          <div class="wowsa-step-btn">Done</div>
        </div>
      <?php else: ?>
        <a class="wowsa-step todo" href="<?= $step['url'] ?>">
          <div class="wowsa-step-icon"><?= $step['icon'] ?></div>
          <div class="wowsa-step-label"><?= htmlspecialchars($step['label']) ?></div>
          <div class="wowsa-step-desc"><?= htmlspecialchars($step['desc']) ?></div>
          <div class="wowsa-step-btn">Go →</div>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>
