<?php
/**
 * WOWSA - Publish Content
 * Toolbox > Widget Builder > New Widget > paste full contents
 * Placement: Design > Member Homepage > Dashboard Footer Content
 *             (or wherever "Publish Post Listing" currently sits)
 *
 * Shows plan-aware content type buttons.
 * Currently configured for: Certified (plan ID 7)
 * Add more $plan_content entries below as other plans are confirmed.
 *
 * Plan IDs (from Finance > Membership Plans):
 *   1 = Member, 2 = Registered, 3 = Featured, 7 = Certified
 *
 * NOTE: Verify the field name for subscription/plan ID.
 * Likely: $member['subscription_type_id'] — adjust if different.
 */

if (!user::isUserLogged($_COOKIE)) return;

$member  = getUser($_COOKIE['userid'], $w);
$user_id = (int)($member['id'] ?? 0);
if (!$user_id) return;

// ── Get plan ID ───────────────────────────────────────────────────────────────
$plan_id = (int)($member['subscription_id'] ?? 0);

// ── Plan-specific content types ───────────────────────────────────────────────
// Add or remove entries per plan as needed.
// 'sub' is the small descriptor line shown under the button label.
$plan_content = [

    7 => [ // Certified
        'plan_name' => 'Certified',
        'plan_desc' => 'Your <strong>Certified plan</strong> includes unlimited swim listings plus community articles, pre/post-event blog posts, and swim results submissions to the WOWSA database.',
        'types' => [
            [
                'icon'  => '🏊',
                'label' => 'Swim Listing',
                'sub'   => 'Race · Marathon Route · Swim Trip',
                'url'   => '/account/swims/add',
            ],
            [
                'icon'  => '📰',
                'label' => 'Community Article',
                'sub'   => 'Press release',
                'url'   => '/account/press-releases/add',
            ],
            [
                'icon'  => '📝',
                'label' => 'Pre-Event Blog Post',
                'sub'   => 'Published before your event',
                'url'   => '/account/pre-event-blog-posts/add',
            ],
            [
                'icon'  => '✍️',
                'label' => 'Post-Event Blog Post',
                'sub'   => 'Published after your event',
                'url'   => '/account/post-event-blog-posts/add',
            ],
            [
                'icon'  => '📊',
                'label' => 'Swim Results',
                'sub'   => 'Submit to WOWSA database',
                'url'   => '/account/swim-results/add',
            ],
        ],
    ],

    3 => [ // Featured — update types when confirmed
        'plan_name' => 'Featured',
        'plan_desc' => 'Your <strong>Featured plan</strong> includes up to 3 swim listings.',
        'types' => [
            [
                'icon'  => '🏊',
                'label' => 'Swim Listing',
                'sub'   => 'Race · Marathon Route · Swim Trip',
                'url'   => '/account/swims/add',
            ],
        ],
    ],

];

// ── Fall back gracefully if plan not configured ───────────────────────────────
if (!isset($plan_content[$plan_id])) return;

$content   = $plan_content[$plan_id];
$types     = $content['types'];
$plan_desc = $content['plan_desc'];
?>

<style>
.wowsa-publish {
  background: #fff;
  border: 1px solid #e0dbd3;
  border-radius: 10px;
  padding: 20px 24px;
  margin-bottom: 20px;
  font-family: inherit;
}
.wowsa-publish-title {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #8a8278;
  margin-bottom: 10px;
}
.wowsa-publish-desc {
  font-size: 14px;
  color: #4a4a4a;
  line-height: 1.7;
  margin-bottom: 16px;
}
.wowsa-publish-desc strong { color: #1a3a2e; }
.wowsa-publish-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
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
  transition: background 0.15s, border-color 0.15s;
}
.wowsa-type-btn:hover {
  background: #e6f4ed;
  border-color: #2e7d5e;
}
.wowsa-type-icon {
  width: 36px;
  height: 36px;
  border-radius: 7px;
  background: #d0eedd;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 17px;
  flex-shrink: 0;
}
.wowsa-type-text { display: flex; flex-direction: column; }
.wowsa-type-label {
  font-size: 13px;
  font-weight: 700;
  color: #1a3a2e;
  line-height: 1.2;
}
.wowsa-type-sub {
  font-size: 11px;
  color: #7a9e8e;
  margin-top: 2px;
}
</style>

<div class="wowsa-publish">
  <div class="wowsa-publish-title">Publish content</div>
  <p class="wowsa-publish-desc"><?= $plan_desc ?></p>
  <div class="wowsa-publish-grid">
    <?php foreach ($types as $type): ?>
      <a class="wowsa-type-btn" href="<?= $type['url'] ?>">
        <div class="wowsa-type-icon"><?= $type['icon'] ?></div>
        <div class="wowsa-type-text">
          <span class="wowsa-type-label"><?= htmlspecialchars($type['label']) ?></span>
          <span class="wowsa-type-sub"><?= htmlspecialchars($type['sub']) ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</div>
