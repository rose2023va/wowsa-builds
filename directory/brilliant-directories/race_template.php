<?php
/**
 * WOWSA - Race/Marathon Route/Swim Trip Listing Detail Template
 * SEO Templates > (listing detail template for post types 81/82/83)
 *
 * Fix (Aug 2026, GitHub issue #1): this template had its own separate
 * "UNCLAIMED"/"CLAIMED" badge driven by $post['claim_status'], independent
 * of and in addition to the WOWSA - Claim This Listing widget's own button
 * further down this same page. claim_status is never set during the paid
 * claim path (see directory/brilliant-directories/widget-claim-listing.php
 * for the full explanation), so this badge kept showing UNCLAIMED even
 * after the widget's own CLAIMED button was already fixed and correct.
 * Same fix applied here: check post owner (user_id) against the default
 * unclaimed owner (contact@openwaterswimming.com, Member ID 5 in BD Admin)
 * instead of claim_status. Computed after the getMetaData() reload below
 * so $post['user_id'] is reliably populated.
 */

$post_id      = isset($post['post_id'])      ? $post['post_id']      : '';
$post_title   = isset($post['title']) ? $post['title'] : (isset($post['post_title']) ? $post['post_title'] : '');
$post_url     = 'https://directory.openwaterswimming.com' . $_SERVER['REQUEST_URI'];

$waterTypeLabels = array(
    'ocean_and_sea' => 'Ocean/Sea',
    'lake'          => 'Lake',
    'river'         => 'River',
    'multiple'      => 'Multiple'
);
$tempLabels = array(
    'hot'      => 'Hot (31C+ / 88F+)',
    'warm'     => 'Warm (21.0-30.9C / 70-88F)',
    'moderate' => 'Moderate (16.0-20.9C / 61-69F)',
    'cold'     => 'Cold (5.1-15.9C / 41-60F)',
    'ice'      => 'Ice (0-5C / 32-41F)'
);
$distanceLabels = array(
    'under_1km'     => 'Under 1km',
    '1_4_9km'       => '1-4.9km',
    '5_9_9km'       => '5-9.9km',
    '10_19_9km'     => '10-19.9km',
    '20_39_9km'     => '20-39.9km',
    '40km_and_over' => '40km and over'
);
$swimmerLabels = array(
    'beginner'     => 'Beginner',
    'intermediate' => 'Intermediate',
    'advanced'     => 'Advanced',
    'all_levels'   => 'All levels'
);
$monthLabels = array(
    'january'   => 'January',   'february' => 'February',
    'march'     => 'March',     'april'    => 'April',
    'may'       => 'May',       'june'     => 'June',
    'july'      => 'July',      'august'   => 'August',
    'september' => 'September', 'october'  => 'October',
    'november'  => 'November',  'december' => 'December'
);

$post = getMetaData("data_posts", $post['post_id'], $post, $w);

// Claim status: see fix note at top of this file.
$post_owner_id      = isset($post['user_id']) ? (int)$post['user_id'] : 0;
$UNCLAIMED_OWNER_ID = 5; // contact@openwaterswimming.com, BD Admin > Members
$is_claimed         = ($post_owner_id > 0) && ($post_owner_id !== $UNCLAIMED_OWNER_ID);

echo widget("Bootstrap Theme - Detail Page - Schema Markup - Event Post Type");
echo widget("Bootstrap Theme - Display - Posted By Snippet");
?>

<div id="post-content">

<?php
// -------------------------------------------------------
// HERO BANNER
// -------------------------------------------------------
?>
<div class="row vmargin" style="background:var(--color-bg,#fff);border:1px solid #e5e5e5;border-radius:8px;overflow:hidden;margin-bottom:20px;">

    <div class="col-sm-8" style="padding:0;">
        <?php if ($post['post_image'] != "") { ?>
        <img class="img-responsive" style="width:100%;height:350px;object-fit:cover;display:block;"
            alt="<?php echo (!empty($post['post_alt']) ? $post['post_alt'] : $post['post_title']); ?>"
            title="<?php echo $post['post_title']; ?>"
            src="<?= str_replace("'", "", $post['post_image']); ?>" />
        <?php } else { ?>
        <div style="width:100%;height:350px;background:#f4f4f4;display:flex;align-items:center;justify-content:center;">
            <i class="fa fa-image fa-3x" style="color:#ccc;"></i>
        </div>
        <?php } ?>
    </div>

    <div class="col-sm-4" style="padding:20px;display:flex;flex-direction:column;justify-content:space-between;">

        <div>
            <?php
            $listing_type = !empty($post['post_category']) ? $post['post_category'] : '';
            if ($listing_type != "") { ?>
            <span style="display:inline-block;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:#e6f1fb;color:#185fa5;margin-bottom:10px;">
                <?php echo $listing_type; ?>
            </span>
            <?php } ?>

            <?php if ($is_claimed) : ?>
            <p style="margin:0 0 6px 0;font-size:11px;font-weight:700;letter-spacing:1px;color:#0E7C7B;text-transform:uppercase;">CLAIMED</p>
            <?php else : ?>
            <p style="margin:0 0 6px 0;font-size:11px;font-weight:700;letter-spacing:1px;color:#cc0000;text-transform:uppercase;">UNCLAIMED</p>
            <?php endif; ?>

            <h1 class="bold h3" style="margin-top:0;margin-bottom:10px;">
                <?php echo $post['post_title']; ?>
            </h1>

            <div style="font-size:13px;color:#666;line-height:1.8;">

                <?php if ($post['post_start_date'] != "") { ?>
                <div><i class="fa fa-calendar" style="width:16px;"></i> <?php echo transformDate($post['post_start_date'], "QBTIME"); ?></div>
                <?php } ?>

                <?php if (!empty($post['season_months'])) {
                    $smSlugs = array_filter(array_map('trim', explode(',', strtolower($post['season_months']))));
                    $smNames = array();
                    foreach ($smSlugs as $ms) {
                        if (isset($monthLabels[$ms])) $smNames[] = $monthLabels[$ms];
                    }
                    if (!empty($smNames)) { ?>
                <div><i class="fa fa-calendar" style="width:16px;"></i> <?php echo implode(', ', $smNames); ?></div>
                <?php } } ?>

                <?php
                $cityDisplay    = !empty($post['city'])         ? $post['city']         : '';
                $stateDisplay   = !empty($post['state_code'])   ? $post['state_code']   : '';
                $countryDisplay = !empty($post['country_code']) ? $post['country_code'] : '';
                $heroParts = array_filter(array($cityDisplay, $stateDisplay, $countryDisplay));
                if (!empty($heroParts)) { ?>
                <div><i class="fa fa-globe" style="width:16px;"></i> <?php echo implode(', ', $heroParts); ?></div>
                <?php } ?>

                <?php if (!empty($user['member_name']) && $user['member_name'] != 'WOWSA') { ?>
                <div><i class="fa fa-user" style="width:16px;"></i> Hosted by <a href="/<?php echo $user['filename']; ?>" style="color:#185fa5;font-weight:600;"><?php echo $user['member_name']; ?></a></div>
                <?php } ?>

            </div>
        </div>

        <div style="margin-top:15px;">
            <?php if ($post['post_url'] != "") { ?>
            <a class="btn btn-primary btn-block bold"
                href="<?php if (strpos($post['post_url'], 'http') !== false) { echo $post['post_url']; } else { echo '//' . $post['post_url']; } ?>"
                target="_blank"
                <?php if ($subscription['nofollow_links'] == "1") { ?>rel="nofollow"<?php } ?>>
                <i class="fa fa-flag"></i> Register
            </a>
            <?php } ?>

            <?php if (!empty($post['website_url'])) { ?>
            <a class="btn btn-default btn-block"
                style="margin-top:6px;"
                href="<?php if (strpos($post['website_url'], 'http') !== false) { echo $post['website_url']; } else { echo '//' . $post['website_url']; } ?>"
                target="_blank">
                <i class="fa fa-external-link"></i> Website
            </a>
            <?php } ?>

            <?php echo widget("WOWSA - Claim This Listing"); ?>

            <?php if ($subscription['receive_messages'] != 1 && $user['active'] == 2) { ?>
            <a data-toggle="modal" data-target="#contactModal" class="btn btn-success btn-block" style="margin-top:6px;">
                %%%contact_member_label%%%
            </a>
            [widget=Bootstrap Theme - Contact Member Modal]
            <?php } ?>
        </div>

    </div>

</div>

<?php
// -------------------------------------------------------
// TWO-COLUMN LAYOUT: main content left, sidebar right
// -------------------------------------------------------
?>
<div class="row">

    <div class="col-sm-8">

        <?php
        // -------------------------------------------------------
        // OVERVIEW
        // -------------------------------------------------------
        ?>
        <div class="well vmargin">
            <h3 class="bold" style="margin-top:0;border-bottom:1px solid #e5e5e5;padding-bottom:8px;">Overview</h3>

            <?php if ($post['post_start_date'] != "") { ?>
            <div style="margin-bottom:15px;">
                <div style="font-size:12px;color:#999;margin-bottom:2px;">Start Date</div>
                <div style="font-weight:600;"><?php echo transformDate($post['post_start_date'], "QBTIME"); ?></div>
            </div>
            <?php } ?>

            <?php if ($post['post_content_clean'] != "") { ?>
            <div class="the-post-description">
                <?php echo $post['post_content_clean']; ?>
                <div class="clearfix"></div>
            </div>
            <?php } ?>
        </div>

        <?php
        // -------------------------------------------------------
        // SWIM DETAILS
        // -------------------------------------------------------
        ?>
        <div class="well vmargin">
            <h3 class="bold" style="margin-top:0;border-bottom:1px solid #e5e5e5;padding-bottom:8px;">Swim Details</h3>

            <div class="row">

                <?php if (!empty($post['swimmer_level'])) {
                    $slSlugs = array_filter(array_map('trim', explode(',', $post['swimmer_level'])));
                    $slLabels = array();
                    foreach ($slSlugs as $sl) {
                        $slLabels[] = isset($swimmerLabels[$sl]) ? $swimmerLabels[$sl] : ucwords(str_replace('_', ' ', $sl));
                    } ?>
                <div class="col-sm-6" style="margin-bottom:12px;">
                    <div style="font-size:12px;color:#999;margin-bottom:2px;">Swimmer Level</div>
                    <div style="font-weight:600;"><?php echo implode(', ', $slLabels); ?></div>
                </div>
                <?php } ?>

                <?php if (!empty($post['water_type'])) { ?>
                <div class="col-sm-6" style="margin-bottom:12px;">
                    <div style="font-size:12px;color:#999;margin-bottom:2px;">Water Type</div>
                    <div style="font-weight:600;"><?php echo isset($waterTypeLabels[$post['water_type']]) ? $waterTypeLabels[$post['water_type']] : $post['water_type']; ?></div>
                </div>
                <?php } ?>

                <?php if (!empty($post['typical_water_temperature'])) { ?>
                <div class="col-sm-6" style="margin-bottom:12px;">
                    <div style="font-size:12px;color:#999;margin-bottom:2px;">Typical Water Temperature</div>
                    <div style="font-weight:600;"><?php echo isset($tempLabels[$post['typical_water_temperature']]) ? $tempLabels[$post['typical_water_temperature']] : $post['typical_water_temperature']; ?></div>
                </div>
                <?php } ?>

                <?php if (!empty($post['wetsuit_rules'])) { ?>
                <div class="col-sm-6" style="margin-bottom:12px;">
                    <div style="font-size:12px;color:#999;margin-bottom:2px;">Wetsuit Rules</div>
                    <div style="font-weight:600;"><?php echo $post['wetsuit_rules']; ?></div>
                </div>
                <?php } ?>

                <?php if (!empty($post['years_running'])) { ?>
                <div class="col-sm-6" style="margin-bottom:12px;">
                    <div style="font-size:12px;color:#999;margin-bottom:2px;">Years Running</div>
                    <div style="font-weight:600;"><?php echo $post['years_running']; ?></div>
                </div>
                <?php } ?>

                <?php if (!empty($post['average_field_size'])) { ?>
                <div class="col-sm-6" style="margin-bottom:12px;">
                    <div style="font-size:12px;color:#999;margin-bottom:2px;">Average Field Size</div>
                    <div style="font-weight:600;"><?php echo $post['average_field_size']; ?></div>
                </div>
                <?php } ?>

                <?php if (!empty($post['route_distance'])) { ?>
                <div class="col-sm-6" style="margin-bottom:12px;">
                    <div style="font-size:12px;color:#999;margin-bottom:2px;">Route Distance (km)</div>
                    <div style="font-weight:600;"><?php echo $post['route_distance']; ?></div>
                </div>
                <?php } ?>

                <?php if (!empty($post['governing_body'])) { ?>
                <div class="col-sm-6" style="margin-bottom:12px;">
                    <div style="font-size:12px;color:#999;margin-bottom:2px;">Governing Body</div>
                    <div style="font-weight:600;"><?php echo $post['governing_body']; ?></div>
                </div>
                <?php } ?>

            </div>

            <?php if (!empty($post['distance_bucket'])) {
                $distances = array_filter(array_map('trim', explode(',', $post['distance_bucket'])));
                if (!empty($distances)) { ?>
            <div style="margin-top:8px;">
                <div style="font-size:12px;color:#999;margin-bottom:8px;">Distances Offered</div>
                <div>
                <?php foreach ($distances as $dv) {
                    $dlabel = isset($distanceLabels[$dv]) ? $distanceLabels[$dv] : ucwords(str_replace('_', ' ', $dv)); ?>
                <span style="display:inline-block;font-size:12px;padding:3px 12px;border-radius:20px;border:1px solid #ddd;color:#555;margin:3px 4px 3px 0;"><?php echo $dlabel; ?></span>
                <?php } ?>
                </div>
            </div>
            <?php } } ?>

        </div>

        <?php
        // -------------------------------------------------------
        // GALLERY
        // -------------------------------------------------------
        $g1 = !empty($post['gallery_photo_1']) ? $post['gallery_photo_1'] : '';
        $g2 = !empty($post['gallery_photo_2']) ? $post['gallery_photo_2'] : '';
        $g3 = !empty($post['gallery_photo_3']) ? $post['gallery_photo_3'] : '';
        if ($g1 != "" || $g2 != "" || $g3 != "") { ?>
        <div class="well vmargin">
            <h3 class="bold" style="margin-top:0;border-bottom:1px solid #e5e5e5;padding-bottom:8px;">Gallery</h3>
            <div class="row">
                <?php if ($g1 != "") { ?>
                <div class="col-sm-4" style="margin-bottom:10px;">
                    <img src="<?php echo $g1; ?>" class="img-responsive img-rounded" style="width:100%;height:160px;object-fit:cover;" alt="<?php echo $post['post_title']; ?> photo 1" />
                </div>
                <?php } ?>
                <?php if ($g2 != "") { ?>
                <div class="col-sm-4" style="margin-bottom:10px;">
                    <img src="<?php echo $g2; ?>" class="img-responsive img-rounded" style="width:100%;height:160px;object-fit:cover;" alt="<?php echo $post['post_title']; ?> photo 2" />
                </div>
                <?php } ?>
                <?php if ($g3 != "") { ?>
                <div class="col-sm-4" style="margin-bottom:10px;">
                    <img src="<?php echo $g3; ?>" class="img-responsive img-rounded" style="width:100%;height:160px;object-fit:cover;" alt="<?php echo $post['post_title']; ?> photo 3" />
                </div>
                <?php } ?>
            </div>
        </div>
        <?php } ?>

        <?php
        // -------------------------------------------------------
        // VIDEO
        // -------------------------------------------------------
        ?>
        <?php if (isset($post['post_video']) && !empty($post['post_video'])) { ?>
        <div class="well vmargin">
            <h3 class="bold" style="margin-top:0;border-bottom:1px solid #e5e5e5;padding-bottom:8px;">Video</h3>
            <div class="wowsa-video-wrap">
                <div class="embed-responsive embed-responsive-16by9">
                    <?php
                    $video_html = $post['post_video'];
                    $video_html = preg_replace('/\s*width="[^"]*"/', '', $video_html);
                    $video_html = preg_replace('/\s*height="[^"]*"/', '', $video_html);
                    $video_html = preg_replace('/\s*style="[^"]*"/', '', $video_html);
                    echo $video_html;
                    ?>
                </div>
            </div>
        </div>
        <?php } ?>

        <?php
        // -------------------------------------------------------
        // LOCATION
        // -------------------------------------------------------
        ?>
        <div class="well vmargin">
            <h3 class="bold" style="margin-top:0;border-bottom:1px solid #e5e5e5;padding-bottom:8px;">Location</h3>

            <?php
            $city        = !empty($post['city'])          ? $post['city']          : '';
            $stateCode   = !empty($post['state_code'])    ? $post['state_code']    : '';
            $countryCode = !empty($post['country_code'])  ? $post['country_code']  : '';
            $zipCode     = !empty($post['zip_code'])      ? $post['zip_code']      : '';
            $postLoc     = !empty($post['post_location']) ? $post['post_location'] : '';
            $addressParts = array_filter(array($city, $stateCode, $countryCode, $zipCode));
            ?>

            <?php if (!empty($addressParts)) { ?>
            <div class="row" style="margin-bottom:15px;">
                <?php if ($city != "") { ?>
                <div class="col-sm-6" style="margin-bottom:8px;">
                    <div style="font-size:12px;color:#999;margin-bottom:2px;">City</div>
                    <div style="font-weight:600;"><?php echo $city; ?></div>
                </div>
                <?php } ?>
                <?php if ($stateCode != "") { ?>
                <div class="col-sm-6" style="margin-bottom:8px;">
                    <div style="font-size:12px;color:#999;margin-bottom:2px;">State / Province</div>
                    <div style="font-weight:600;"><?php echo $stateCode; ?></div>
                </div>
                <?php } ?>
                <?php if ($countryCode != "") { ?>
                <div class="col-sm-6" style="margin-bottom:8px;">
                    <div style="font-size:12px;color:#999;margin-bottom:2px;">Country</div>
                    <div style="font-weight:600;"><?php echo $countryCode; ?></div>
                </div>
                <?php } ?>
                <?php if ($zipCode != "") { ?>
                <div class="col-sm-6" style="margin-bottom:8px;">
                    <div style="font-size:12px;color:#999;margin-bottom:2px;">Postcode</div>
                    <div style="font-weight:600;"><?php echo $zipCode; ?></div>
                </div>
                <?php } ?>
            </div>
            <?php } elseif ($postLoc != "") { ?>
            <div style="margin-bottom:15px;font-weight:600;"><?php echo $postLoc; ?></div>
            <?php } ?>

            <?php if (!empty($post['lat']) && !empty($post['lon'])) { ?>
            [widget=Bootstrap Theme - Detail Page - Map]
            <?php } ?>

        </div>

        <?php
        // -------------------------------------------------------
        // CONTACT
        // -------------------------------------------------------
        ?>
        <div class="well vmargin">
            <h3 class="bold" style="margin-top:0;border-bottom:1px solid #e5e5e5;padding-bottom:8px;">Contact</h3>

            <?php if (!empty($user['member_name']) && $user['member_name'] != 'WOWSA') { ?>
            <div style="margin-bottom:12px;">
                <div style="font-size:12px;color:#999;margin-bottom:2px;">Organizer</div>
                <div style="font-weight:600;"><a href="/<?php echo $user['filename']; ?>" style="color:#185fa5;"><?php echo $user['member_name']; ?></a></div>
            </div>
            <?php } ?>

            <?php if ($post['post_url'] != "") { ?>
            <div style="margin-bottom:12px;">
                <a class="btn btn-primary"
                    href="<?php if (strpos($post['post_url'], 'http') !== false) { echo $post['post_url']; } else { echo '//' . $post['post_url']; } ?>"
                    target="_blank"
                    <?php if ($subscription['nofollow_links'] == "1") { ?>rel="nofollow"<?php } ?>>
                    <i class="fa fa-flag"></i> Register
                </a>
            </div>
            <?php } ?>

            <?php if (!empty($post['website_url'])) { ?>
            <div style="margin-bottom:12px;">
                <div style="font-size:12px;color:#999;margin-bottom:2px;">Website</div>
                <a href="<?php if (strpos($post['website_url'], 'http') !== false) { echo $post['website_url']; } else { echo '//' . $post['website_url']; } ?>" target="_blank" style="color:#185fa5;">
                    <?php echo $post['website_url']; ?>
                </a>
            </div>
            <?php } ?>

            <?php
            $fb = !empty($post['facebook_url'])  ? $post['facebook_url']  : '';
            $ig = !empty($post['instagram_url']) ? $post['instagram_url'] : '';
            $li = !empty($post['linkedin_url'])  ? $post['linkedin_url']  : '';
            $th = !empty($post['threads_url'])   ? $post['threads_url']   : '';
            if ($fb != "" || $ig != "" || $li != "" || $th != "") { ?>
            <div style="margin-top:8px;">
                <div style="font-size:12px;color:#999;margin-bottom:8px;">Socials</div>
                <div>
                    <?php if ($fb != "") { ?>
                    <a href="<?php echo $fb; ?>" target="_blank" style="display:inline-block;padding:5px 14px;border:1px solid #ddd;border-radius:20px;font-size:13px;color:#555;margin:3px 4px 3px 0;text-decoration:none;">
                        <i class="fa fa-facebook"></i> Facebook
                    </a>
                    <?php } ?>
                    <?php if ($ig != "") { ?>
                    <a href="<?php echo $ig; ?>" target="_blank" style="display:inline-block;padding:5px 14px;border:1px solid #ddd;border-radius:20px;font-size:13px;color:#555;margin:3px 4px 3px 0;text-decoration:none;">
                        <i class="fa fa-instagram"></i> Instagram
                    </a>
                    <?php } ?>
                    <?php if ($li != "") { ?>
                    <a href="<?php echo $li; ?>" target="_blank" style="display:inline-block;padding:5px 14px;border:1px solid #ddd;border-radius:20px;font-size:13px;color:#555;margin:3px 4px 3px 0;text-decoration:none;">
                        <i class="fa fa-linkedin"></i> LinkedIn
                    </a>
                    <?php } ?>
                    <?php if ($th != "") { ?>
                    <a href="<?php echo $th; ?>" target="_blank" style="display:inline-block;padding:5px 14px;border:1px solid #ddd;border-radius:20px;font-size:13px;color:#555;margin:3px 4px 3px 0;text-decoration:none;">
                        <i class="fa fa-at"></i> Threads
                    </a>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>

        </div>

        <?php if ($tags != "") { ?>
        <div class="well vmargin">
            <div class="tags"><?php echo $tags; ?></div>
        </div>
        <?php } ?>

    </div>

    <?php
    // -------------------------------------------------------
    // SIDEBAR
    // -------------------------------------------------------
    ?>
    <div class="col-sm-4">
        [sidebar=WOWSA Post Sidebar]
    </div>

</div>

<div class="clearfix"></div>
</div>
