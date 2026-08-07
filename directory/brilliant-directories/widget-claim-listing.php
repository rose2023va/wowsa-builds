<?php
/**
 * WOWSA - Claim This Listing
 * Toolbox > Widget Builder > New Widget > paste full contents
 * Widget Name: WOWSA - Claim This Listing
 * Placement: called from race_template.php via <?php echo widget("WOWSA - Claim This Listing"); ?>
 *
 * Fix (Aug 2026, GitHub issue #1): claim status was previously driven by
 * $post['claim_status'], which nothing in the paid-claim flow ever sets — the
 * flow ends with a notification email and a MANUAL post-author reassignment
 * by admin (BD admin > Bulk Actions > Assign New Author), so claim_status
 * stayed stale and the button never disappeared on already-claimed listings.
 * Now driven directly by $post['user_id'] vs. the default "unclaimed" owner
 * (contact@openwaterswimming.com, Member ID #5 in BD Admin > Members), which
 * is exactly what the manual reassignment step changes.
 *
 * Confirmed via debug pass on the Swim Around Manhattan listing (post 685,
 * already-claimed): $post['post_author'] exists as a key but is empty in
 * this widget's context, not usable. $post['user_id'] is populated and
 * reliable — that's the real signal, compared against the hardcoded
 * unclaimed-owner ID below (same hardcoding pattern already used for plan
 * limits and post type IDs elsewhere in this widget).
 */

$post_id      = isset($post['post_id'])    ? $post['post_id']    : '';
$post_title   = isset($post['post_title']) ? $post['post_title'] : '';
$post_url     = 'https://directory.openwaterswimming.com' . $_SERVER['REQUEST_URI'];
$post_owner_id = isset($post['user_id']) ? (int)$post['user_id'] : 0;

// Post type IDs for Race, Marathon Route, Swim Trip
$counted_post_types = array(81, 82, 83);

// Plan post limits (-1 = unlimited)
$plan_limits = array(
    1 => 1,  // Member
    2 => 1,  // Registered
    3 => 3,  // Featured
    7 => -1, // Certified - unlimited
);

// ── Determine claim status from actual post ownership ─────────────────────────
// Default/unclaimed listings are owned by contact@openwaterswimming.com,
// Member ID #5 (confirmed in BD Admin > Members). If this account is ever
// recreated, update this constant.
$UNCLAIMED_OWNER_ID = 5;

$is_claimed = ($post_owner_id > 0) && ($post_owner_id !== $UNCLAIMED_OWNER_ID);
?>

<?php if ($is_claimed) : ?>
    <div class="alert alert-default text-center bold" style="margin-top:6px;">
        <i class="fa fa-check-circle fa-fw"></i> CLAIMED
    </div>

<?php else : ?>

    <?php if (user::isUserLogged($_COOKIE)) : ?>
        <?php
        $loggedInUser = getUser($_COOKIE['userid'], $w);
        $planId       = $loggedInUser['subscription_id'];
        $memberId     = $loggedInUser['user_id'];
        $memberName   = $loggedInUser['member_name'];
        $memberEmail  = $loggedInUser['email'];

        // Get plan limit for this member
        $postLimit = isset($plan_limits[$planId]) ? $plan_limits[$planId] : 1;


		// Count published posts for Race, Marathon Route, Swim Trip only
		$postTypeList = implode(',', $counted_post_types);
		$postCountResult = mysql(
			brilliantDirectories::getDatabaseConfiguration('database'),
			"SELECT COUNT(*) as cnt FROM data_posts
			 WHERE user_id = " . intval($memberId) . "
			 AND post_status = 1
			 AND data_id IN (" . $postTypeList . ")"
		);
		$row = mysqli_fetch_assoc($postCountResult);
		$publishedCount = isset($row['cnt']) ? (int)$row['cnt'] : 0;

        // Determine if member has posts remaining
        $hasAllowance = ($postLimit === -1) || ($publishedCount < $postLimit);
        ?>

        <?php if ($hasAllowance) : ?>
            <a class="btn btn-block"
               style="margin-top:6px;background:#cc0000;color:#ffffff;font-weight:600;"
               href="#"
               onclick="document.getElementById('wowsa-claim-form').style.display='block';this.style.display='none';return false;">
                <i class="fa fa-flag fa-fw"></i> Claim This Listing
            </a>
            <div id="wowsa-claim-form" style="display:none;margin-top:12px;">
                [form=claim_this_listing]
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                function setField(name, value) {
                    var field = document.querySelector('#wowsa-claim-form input[name="' + name + '"]');
                    if (field && value) { field.value = value; }
                }
                setField('claim_post_id',       <?php echo json_encode($post_id); ?>);
                setField('claim_listing_title', <?php echo json_encode($post_title); ?>);
                setField('claim_post_url',      <?php echo json_encode($post_url); ?>);
            });
            </script>

        <?php else : ?>
            <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/6.11.2/sweetalert2.min.css">
            <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/6.11.2/sweetalert2.min.js"></script>
            <script src="https://js.stripe.com/v3/"></script>
            <script src="/include/js/buy_digital_product.js?ver=15.3"></script>
            <script src="https://www.optimizecdn.com/directory/cdn/assets/bootstrap/js/stripe.js"></script>

            <!-- Hidden notification form submitted silently on button click -->
            <form id="wowsa-claim-notify-form"
                  action="/api/widget/json/get/Bootstrap%20Theme%20-%20Function%20-%20Save%20Form"
                  method="post"
                  style="display:none;">
                <input type="hidden" name="formname" value="claim_this_listing">
                <input type="hidden" name="dowiz" value="1">
                <input type="hidden" name="save" value="1">
                <input type="hidden" name="claim_name" value="<?php echo htmlspecialchars($memberName); ?>">
                <input type="hidden" name="claimer_email" value="<?php echo htmlspecialchars($memberEmail); ?>">
                <input type="hidden" name="claim_post_id" value="<?php echo htmlspecialchars($post_id); ?>">
                <input type="hidden" name="claim_listing_title" value="<?php echo htmlspecialchars($post_title); ?>">
                <input type="hidden" name="claim_post_url" value="<?php echo htmlspecialchars($post_url); ?>">
                <input type="hidden" name="claim_org" value="PAYMENT INTENT - DO NOT PROCESS UNTIL PAYMENT IS CONFIRMED. Check Finance > Transactions in BD admin to verify payment was successful before transferring this listing. Member ID: <?php echo htmlspecialchars($memberId); ?>, Published Posts: <?php echo $publishedCount; ?>, Plan Limit: <?php echo $postLimit; ?>">
            </form>

            <a id="wowsa-claim-paid-btn"
               class="btn btn-block bold buy-digital-product-button digital-download-1"
               style="margin-top:6px;background:#cc0000;color:#ffffff;font-weight:600;"
               href="#"
               data-post-id="<?php echo htmlspecialchars($post_id); ?>"
               data-post-title="<?php echo htmlspecialchars($post_title); ?>"
               data-post-url="<?php echo htmlspecialchars($post_url); ?>"
               data-member-id="<?php echo htmlspecialchars($memberId); ?>"
               data-member-name="<?php echo htmlspecialchars($memberName); ?>"
               data-member-email="<?php echo htmlspecialchars($memberEmail); ?>">
                <i class="fa fa-flag fa-fw"></i> Claim This Listing
            </a>

            <script>
            if (typeof optionsHolder === 'undefined') { var optionsHolder = {}; }

            var onCompleteFunction = function() {
                window.location.href = window.location.href;
            }

            optionsHolder['options_1'] = {
                stripeKey           : 'pk_live_vZkKVYWklfIGCFEF3SlovrIF',
                purchase_action     : 'buy_post',
                usertoken           : '<?php echo $memberId; ?>',
                data_id             : '1',
                data_category_id    : '73',
                completedAction     : onCompleteFunction,
                completedTitle      : 'Continue To Next Section',
                continueTitle       : 'Continue To Next Section',
                cardErrorTitle      : 'Error',
                cardErrorMessage    : 'Error: Double Check Card Info And Submit Again.',
                showConfirmButton   : false,
                data_type           : '4',
                urlToRedirect       : '',
                clientId            : '<?php echo $memberId; ?>',
                confirmPurchase     : 'Yes, continue with purchase',
                cancelPurchase      : 'No, cancel',
                processingOrder     : 'Please Wait a Moment',
                processingOrderText : 'Processing Your Request...',
                saveCard            : 'Save credit card & continue',
                cancelSaveCard      : 'Cancel',
                avs                 : '0',
                first_click         : 1
            };

            document.getElementById('wowsa-claim-paid-btn').addEventListener('click', function(e) {
                e.preventDefault();

                var notifyForm = document.getElementById('wowsa-claim-notify-form');
                var formData = new FormData(notifyForm);
                fetch(notifyForm.action, {
                    method: 'POST',
                    body: formData
                }).catch(function() {
                    // Notification failure should not block payment
                });

                optionsHolder['options_1']['first_click'] = 1;
                processPayment(true, optionsHolder['options_1']);
            });
            </script>

        <?php endif; ?>

    <?php else : ?>
        <a href="/join"
           class="btn btn-block"
           style="margin-top:6px;background:#cc0000;color:#ffffff;font-weight:600;"
           onclick="sessionStorage.setItem('claim_post_id', <?php echo json_encode($post_id); ?>);sessionStorage.setItem('claim_post_title', <?php echo json_encode($post_title); ?>);sessionStorage.setItem('claim_post_url', <?php echo json_encode($post_url); ?>);">
            <i class="fa fa-flag fa-fw"></i> Claim This Listing
        </a>
    <?php endif; ?>

<?php endif; ?>
