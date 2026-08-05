<?php
$posts = $w->db->get_results("
    SELECT post_title, post_content, post_image_full_url, full_filename,
           posted_by_first_name, posted_by_last_name, revision_timestamp
    FROM data_posts
    WHERE data_id IN (86, 87)
    AND post_status = 1
    ORDER BY revision_timestamp DESC
    LIMIT 30
");
?>

<style>
.ca-archive { max-width: 960px; margin: 0 auto; padding: 32px 16px; font-family: inherit; }
.ca-archive-title { font-size: 26px; font-weight: 700; color: #1a3a2e; margin-bottom: 6px; }
.ca-archive-sub { font-size: 14px; color: #6b7b74; margin-bottom: 32px; }
.ca-archive-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}
@media (max-width: 768px) { .ca-archive-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 500px) { .ca-archive-grid { grid-template-columns: 1fr; } }

.ca-post {
  background: #fff;
  border: 1px solid #e0dbd3;
  border-radius: 10px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  text-decoration: none;
  transition: box-shadow 0.15s;
}
.ca-post:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.10); }
.ca-post-img {
  width: 100%;
  height: 160px;
  object-fit: cover;
  background: #e8f5ee;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 36px;
  color: #aacfbe;
}
.ca-post-img img { width: 100%; height: 160px; object-fit: cover; display: block; }
.ca-post-body { padding: 16px; display: flex; flex-direction: column; flex-grow: 1; gap: 8px; }
.ca-post-title { font-size: 15px; font-weight: 700; color: #1a3a2e; line-height: 1.4; }
.ca-post-excerpt { font-size: 13px; color: #5a6e65; line-height: 1.6; flex-grow: 1; }
.ca-post-meta { font-size: 11px; color: #9aafa6; margin-top: 4px; }
.ca-empty { text-align: center; padding: 60px 20px; color: #8a9e94; font-size: 15px; }
</style>

<div class="ca-archive">
  <div class="ca-archive-title">Community Announcements</div>
  <div class="ca-archive-sub">News, events, recaps and press releases from the open water swimming community.</div>

  <?php if (empty($posts)): ?>
    <div class="ca-empty">No announcements yet. Be the first to <a href="/post-announcement" style="color:#2e7d5e">post one</a>.</div>
  <?php else: ?>
    <div class="ca-archive-grid">
      <?php foreach ($posts as $post): ?>
        <?php
          $excerpt = strip_tags($post->post_content ?? '');
          $excerpt = strlen($excerpt) > 120 ? substr($excerpt, 0, 120) . '...' : $excerpt;
          $author  = trim(($post->posted_by_first_name ?? '') . ' ' . ($post->posted_by_last_name ?? ''));
          $date    = date('M j, Y', strtotime($post->revision_timestamp ?? ''));
        ?>
        <a class="ca-post" href="<?php echo htmlspecialchars($post->full_filename ?? '#'); ?>">
          <div class="ca-post-img">
            <?php if (!empty($post->post_image_full_url)): ?>
              <img src="<?php echo htmlspecialchars($post->post_image_full_url); ?>" alt="">
            <?php else: ?>
              &#127754;
            <?php endif; ?>
          </div>
          <div class="ca-post-body">
            <div class="ca-post-title"><?php echo htmlspecialchars($post->post_title ?? ''); ?></div>
            <?php if ($excerpt): ?>
              <div class="ca-post-excerpt"><?php echo htmlspecialchars($excerpt); ?></div>
            <?php endif; ?>
            <div class="ca-post-meta">
              <?php if ($author): ?><?php echo htmlspecialchars($author); ?> &middot; <?php endif; ?>
              <?php echo $date; ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
