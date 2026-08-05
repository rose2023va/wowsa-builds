<?php
echo widget("Bootstrap Theme - Detail Page - Schema Markup - Community Article");
echo widget("Bootstrap Theme - Display - Posted By Snippet");
?>

<div id="post-content">

	<div class="row post-title">
		<div class="col-md-12 tmargin">
			<h1 class="bold h2 nobmargin">
				<?php echo $post['post_title']; ?>
			</h1>
			<hr>
			<div class="clearfix"></div>
		</div>
	</div>

	<div class="post-body">

		<?php if ($post['post_image'] != "") { ?>
		<div class="alert-secondary btn-block text-center post-image-container">
			<img fetchpriority="high" class="center-block img-rounded post-image" alt="<?php echo (!empty($post['post_alt'])?$post['post_alt']:$post['post_title'])?>" title="<?php echo $post['post_title']; ?>" src="<?php echo str_replace("'","",$post['post_image']);?>" />
			<div class="clearfix"></div>
		</div>
		<hr>
		<?php
		} ?>

		<div class="clearfix"></div>

		<?php if ($post['post_content_clean'] !="") {
			echo '<div class="the-post-description">' . $post['post_content_clean'] .'<div class="clearfix"></div></div>';
		} ?>

		<div class="clearfix"></div>

		<?php
		$galleryPhotos = array_filter(array(
			$post['gallery_photo_1'],
			$post['gallery_photo_2'],
			$post['gallery_photo_3'],
		));
		if (!empty($galleryPhotos)) {
			if ($post['post_content_clean'] != "") { echo '<hr class="tmargin">'; }
		?>
		<div class="post-gallery-title">
			<h4 class="bold nobmargin">Additional Photos</h4>
		</div>
		<div class="post-gallery-grid tmargin">
			<?php foreach ($galleryPhotos as $photo) { ?>
			<a href="<?php echo str_replace("'", "", $photo); ?>" target="_blank" class="post-gallery-item">
				<img class="img-rounded post-gallery-thumb" src="<?php echo str_replace("'", "", $photo); ?>" alt="<?php echo $post['post_title']; ?>" />
			</a>
			<?php } ?>
		</div>
		<style>
			.post-gallery-grid {
				display: flex;
				flex-wrap: wrap;
				gap: 16px;
			}
			.post-gallery-item {
				display: block !important;
				overflow: hidden !important;
				flex: 1 1 calc(33.333% - 11px) !important;
				max-width: calc(33.333% - 11px) !important;
				height: 220px !important;
			}
			.post-gallery-thumb {
				display: block !important;
				width: 100% !important;
				max-width: 100% !important;
				height: 100% !important;
				max-height: none !important;
				object-fit: cover !important;
			}
			@media (max-width: 767px) {
				.post-gallery-item {
					flex: 1 1 calc(50% - 8px) !important;
					max-width: calc(50% - 8px) !important;
				}
			}
		</style>
		<?php } ?>

		<div class="clearfix"></div>

		<?php if ($tags!="") {
			if ($post['post_content_clean'] !="" || !empty($galleryPhotos)) { echo '<hr class=tmargin>'; }?>
		<div class="tags">
			<?php echo $tags;?>
		</div>
		<?php
		} ?>

		<div class="clearfix"></div>
	</div>
	<div class="clearfix"></div>
</div>
