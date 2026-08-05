<?php
$result = mysql(brilliantDirectories::getDatabaseConfiguration('database'), "
    SELECT post_title, post_content, post_image, post_filename,
           post_author, revision_timestamp
    FROM data_posts
    WHERE data_id IN (86, 87)
    AND post_status = 1
    ORDER BY revision_timestamp DESC
    LIMIT 30
");

$posts = array();
while ($row = mysql_fetch_assoc($result)) {
    $posts[] = $row;
}
?>

<div class="ca-archive">
  <div class="ca-archive-sub">News, events, recaps and press releases from the open water swimming community.</div>

  <?php if (empty($posts)): ?>
    <div class="ca-empty">No announcements yet. Be the first to <a href="/post-announcement">post one</a>.</div>
  <?php else: ?>
    <div class="ca-archive-grid">
      <?php foreach ($posts as $post): ?>
        <?php
          $excerpt = strip_tags($post['post_content'] ?? '');
          $excerpt = strlen($excerpt) > 120 ? substr($excerpt, 0, 120) . '...' : $excerpt;
          $date    = date('M j, Y', strtotime($post['revision_timestamp'] ?? ''));
          $url     = '/' . ltrim($post['post_filename'] ?? '#', '/');
        ?>
        <a class="ca-post" href="<?php echo htmlspecialchars($url); ?>">
          <div class="ca-post-img">
            <?php if (!empty($post['post_image'])): ?>
              <img src="<?php echo htmlspecialchars($post['post_image']); ?>" alt="">
            <?php else: ?>
              &#127754;
            <?php endif; ?>
          </div>
          <div class="ca-post-body">
            <div class="ca-post-title"><?php echo htmlspecialchars($post['post_title'] ?? ''); ?></div>
            <?php if ($excerpt): ?>
              <div class="ca-post-excerpt"><?php echo htmlspecialchars($excerpt); ?></div>
            <?php endif; ?>
            <div class="ca-post-meta">
              <?php if (!empty($post['post_author'])): ?><?php echo htmlspecialchars($post['post_author']); ?> &middot; <?php endif; ?>
              <?php echo $date; ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
