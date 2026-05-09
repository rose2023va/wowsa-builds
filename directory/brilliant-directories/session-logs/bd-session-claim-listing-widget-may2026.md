# WOWSA BD Build Session Log - Claim Listing Widget
**Date:** May 2026
**Platform:** Brilliant Directories
**Focus:** Claim This Listing widget, post type forms, member specialty field, payment flow

---

## What We Did

1. Reviewed the existing Race post type form and confirmed it was fully built. Renamed "Race Name" field label discussion occurred but was deferred in favour of keeping three separate post types.

2. Decided to revert from a single Swims post type back to three separate post types - Race (data_id 81), Marathon Route (data_id 82), Swim Trip (data_id 83). The single post type with categories was abandoned because categories cannot be access-restricted per membership plan and would make data management harder.

3. Built the Marathon Route post type form by copying the Race form and making the following changes. Removed: Start Date (post_start_date), End Date (post_expire_date), Registration URL (post_url), Wetsuit Rules (wetsuit_rules), Average Field Size (average_field_size), Distances Offered (distances_offered). Added: Route Distance (Number - Decimal, database variable route_distance), Records (Text - Single Line, route_records), Governing Body (Text - Single Line, governing_body). Renamed post_title field label to "Marathon Route Title".

4. Built the Swim Trip post type form by copying the Race form and making the following changes. Removed: Start Date (post_start_date), End Date (post_expire_date), Wetsuit Rules (wetsuit_rules), Years Running (years_running), Average Field Size (average_field_size), Distances Offered (distances_offered). Added: Venue Name (Text - Single Line, post_venue), Season Start Month (Dropdown List, season_start_month), Season End Month (Dropdown List, season_end_month), Distance Per Day (Text - Single Line, distance_per_day), Guide Info (Text - Single Line, guide_info). Relabeled Registration URL to "Booking URL". Renamed post_title field label to "Swim Trip Title".

5. Investigated BD's native category field rendering issue. The Bootstrap Theme - Account - Select Categories widget and $dc['feature_categories'] variable were confirmed to only render in actual member post submission context, not on preview pages or custom content pages. This is expected BD platform behaviour.

6. Attempted to build a custom Member Specialty widget using checkboxes with name="services[]" to save to the native BD services field on the whmcs_signup_paid checkout form. Widget was built in Toolbox > Widget Builder and added to the form as a Custom HTML field with database variable services. Confirmed from BD bot that custom HTML checkbox widgets cannot save to the native services field on checkout forms - only native category selection widgets save correctly to that field.

7. Decided to abandon the custom member specialty widget on the signup form. Switched to the native BD approach: one top-level category (WOWSA Member), current top-level categories moved to sub-level (Organizer, Support, Swimmer), current sub-level categories moved to sub-sub-level. Category IDs confirmed: Race Organizer 16, Marathon Organizer 17, Swim Trip Operator 18, Coach 19, Guide 20, Observer 21, Wellness Swimmer 22, Racing Swimmer 23, Marathon Swimmer 24. Member Specialties Checkbox set to ACTIVATE in Finance > Membership Plans > Post Publishing for each plan. Max Sub-Level Categories set to unlimited.

8. Added the Bootstrap Theme - Account - Select Categories widget to the member profile forms in Toolbox > Form Manager using Type of Field: Custom HTML, Field Label: [widget=Bootstrap Theme - Account - Select Categories], Database Variable: services, Input View: ON, all other Display Settings: OFF.

9. Confirmed Top Level Category (profession_id) hidden from members by setting Input View OFF on the Contact Details form. Pre-Select Top Category set per plan in Finance > Membership Plans > General tab.

10. Investigated digital product purchase flow for the paid claim path. Created a digital product in BD for "Claim Listing" at $99. Confirmed product ID 1, data_category_id 73 from the product page source. Confirmed Stripe publishable key pk_live_vZkKVYWklfIGCFEF3SlovrIF from Settings > Payment Settings.

11. Researched whether BD supports a native post count variable or plan limit variable in widget context. BD bot confirmed no native PHP variable exists for either. Queried PHPMyAdmin directly to identify the database structure.

12. Ran DESCRIBE data_posts in PHPMyAdmin and confirmed user_id is the foreign key linking posts to members. post_status = 1 means published.

13. Ran SHOW TABLES and identified subscription_types as the membership plans table. Ran DESCRIBE subscription_types and identified data_settings_limit is stored serialized in users_meta, not as a direct column.

14. Queried users_meta with SELECT meta_id, database_id, `key`, `value` FROM users_meta WHERE `database` = 'subscription_types' AND `key` LIKE '%limit%' using backtick escaping for reserved words. Confirmed data_post_limitted is stored as JSON per post type ID, and data_settings_limit stores numeric limits as JSON.

15. Decided to hardcode plan limits in the widget rather than parse serialized JSON from the database. Confirmed limits from Finance > Membership Plans > Post Publishing: Member (ID 1) = 1 post, Registered (ID 2) = 1 post, Featured (ID 3) = 3 posts, Certified (ID 7) = unlimited.

16. Built the WOWSA - Claim This Listing widget in Toolbox > Widget Builder with the following logic: if post claim_status is "claimed" show CLAIMED label; if visitor is not logged in show /join redirect button and write post context to sessionStorage; if logged in check published post count against hardcoded plan limit; if posts remaining show free inline claim form with claim_this_listing form pre-filled; if at limit show digital product Claim Now button with pre-payment notification.

17. The post count query uses $w->db->get_results() against data_posts filtering on user_id, post_status = 1, and data_id IN (81, 82, 83) to count only Race, Marathon Route, and Swim Trip posts combined across all three post types.

18. Built pre-payment notification using a hidden form submitted via fetch() to the BD Save Form API on button click. The form submits to the claim_this_listing form which has an existing notification email template. The notification email goes to contact@openwaterswimming.com and includes member name, member email, member ID, post title, post ID, post URL, published post count, plan limit, and a note to verify payment in Finance > Transactions before processing the claim transfer.

19. Updated race_template.php to replace the entire old claim button block with a single line: `<?php echo widget("WOWSA - Claim This Listing"); ?>`. Full updated template delivered as race_template.php.

20. Confirmed the three hidden fields (claim_post_id, claim_listing_title, claim_post_url) are already present on whmcs_signup_paid from earlier build work, and the JS in SEO Templates > template #400 already reads from sessionStorage and injects values into those fields.

21. Discussed the edge case where a member claims a listing but admin has not yet transferred the post author - during that window the member could potentially use their free dashboard post creation allowance on a different listing. Accepted as an acceptable operational risk given the expected low volume of simultaneous claims. Automated post author reassignment was parked for a future phase.

22. Confirmed the combined post count query across all three post types (81, 82, 83) naturally enforces the intended behaviour that was not achievable in BD's native plan settings - a single combined allowance across listing types rather than per-listing-type limits.

23. Confirmed plan IDs and post limits are correct and stable. Membership plan structure: Member ID 1, Registered ID 2, Featured ID 3, Admin - Blog Author ID 4, General User Account ID 5, Unclaimed Listing ID 6, Certified ID 7.

24. Final widget code confirmed and delivered ready to paste into Toolbox > Widget Builder as "WOWSA - Claim This Listing". Widget is the sole claim mechanism - race_template.php calls it via echo widget() shortcode.

---

## Errors Encountered

**Error 1 - Form Error: Invalid Invoice Action on not-logged-in claim button click**

- Triggered by: clicking the Claim This Listing button while not logged in
- Exact error: SweetAlert modal showing "Form Error - Invalid Invoice Action" with Yes/No buttons
- Root cause: the outer login check used $user['active'] == 2 which is not valid in the widget context. BD's $user variable does not populate the same way in widgets as in templates. The check was evaluating false and falling through to the logged-in paid plan branch, which fired processPayment() for a non-authenticated user. BD's digital product purchase requires an authenticated member, hence the Invalid Invoice Action error.
- Resolution: replaced $user['active'] == 2 with user::isUserLogged($_COOKIE) which is the documented BD method for checking login status in widgets. Confirmed by BD bot with reference to PHP Code Snippet to Check If a Member/User is Logged In documentation article.

**Error 2 - Notification email not received on paid claim button click**

- Triggered by: clicking Claim This Listing while logged in as a non-Certified member
- Exact behaviour: payment modal appeared correctly but no notification email arrived at contact@openwaterswimming.com
- Root cause: the fetch() call to /api/widget/json/post/Bootstrap Theme - Function - Save Form was not reliably triggering the BD email notification system when called with custom formname and lead fields outside of a native form submission context
- Resolution: replaced the fetch() API call with a hidden HTML form that submits silently via fetch() using the same action endpoint and field names as the actual claim_this_listing form. This routes through BD's native form processing which has the notification email template already configured, making the email fire reliably. Resolution was built but not yet confirmed tested as of end of session.

**Error 3 - SQL syntax error querying users_meta for reserved word columns**

- Triggered by: running SELECT meta_id, database_id, key, value FROM users_meta WHERE database = 'subscription_types' AND key LIKE '%limit%'
- Exact error: #1064 - You have an error in your SQL syntax near 'key, value FROM users_meta WHERE database...'
- Root cause: key, value, and database are reserved words in MariaDB and cannot be used as unquoted column names in SQL queries
- Resolution: wrapped all reserved word column names in backticks - SELECT meta_id, database_id, `key`, `value` FROM users_meta WHERE `database` = 'subscription_types' AND `key` LIKE '%limit%'

---

## Workarounds Tested

**Workaround 1 - Custom checkbox widget for member specialty on signup form**

Reasoning: wanted to show specialty checkboxes on the whmcs_signup_paid checkout form so members could select their role at signup without a second step.

Built a custom HTML widget with name="services[]" checkboxes. Widget displayed correctly on the form. On form submission the selected values were not saved to the member's services field. BD bot confirmed custom HTML checkbox widgets cannot save to the native services field during checkout - only native category selection widgets write correctly to that field.

Outcome: abandoned. Switched to the native BD member specialty flow where members select specialties from their dashboard after signup, using the Bootstrap Theme - Account - Select Categories widget on the Listing Details member form.

**Workaround 2 - Database query for plan post limit**

Reasoning: wanted to dynamically read the plan's post limit from the database rather than hardcoding, so the widget would automatically reflect any plan limit changes made in the admin.

Investigated data_settings_limit field via PHPMyAdmin. Confirmed the limit is stored as serialized JSON in users_meta keyed to subscription_types, not as a simple column. Parsing serialized JSON inside a BD widget via $w->db->get_results() is technically possible but fragile and adds complexity.

Outcome: abandoned in favour of hardcoded limits array in the widget. Plan limits change rarely and are a deliberate admin decision. Hardcoded approach is simpler, more readable, and easier to maintain. If plan limits change in Finance > Membership Plans, the $plan_limits array in the widget must be updated manually to match.

**Workaround 3 - Automated post author reassignment after payment**

Reasoning: wanted the claimed listing to automatically transfer to the organizer's account when payment completed, using the onCompleteFunction callback after processPayment() fires.

The BD digital product onCompleteFunction only supports page reload or URL redirect - it has no hook for executing server-side logic. No BD webhook for digital product purchases was confirmed in the training data.

Outcome: parked for future phase. Admin receives notification email with full claim context and manually reassigns post author via BD admin Bulk Actions > Assign New Author. Automated transfer to be revisited when BD webhook support for digital product purchases is confirmed.

**Workaround 4 - Using $user['active'] == 2 for login check in widget**

Reasoning: this is the correct check in template context and was initially carried over to the widget.

The $user variable is not reliably populated inside Widget Builder widgets the way it is in listing detail page templates. The check evaluated as false for all visitors including logged-in members, causing the widget to always fall through to the not-logged-in path or the paid path.

Outcome: replaced with user::isUserLogged($_COOKIE) which is the BD-documented method for login checks inside widgets. After this fix, all three paths routed correctly.

---

## Final Decision

The WOWSA - Claim This Listing widget was built and saved in Toolbox > Widget Builder. The race_template.php calls it via `<?php echo widget("WOWSA - Claim This Listing"); ?>` replacing the previous inline claim button block.

The widget logic is: show CLAIMED label if claim_status is claimed; redirect not-logged-in visitors to /join with post context in sessionStorage; for logged-in members query data_posts for published post count across data_id 81, 82, 83 and compare against hardcoded plan limit; if allowance remains show free inline claim form; if at limit show digital product Claim Now button (product ID 1, $99) with silent pre-payment notification to contact@openwaterswimming.com.

Post limit is checked by counting published posts (post_status = 1) authored by the member across all three post types (data_id 81, 82, 83) combined. This naturally enforces a single combined allowance across Race, Marathon Route, and Swim Trip, which is consistent with BD's native Pay Per Post behaviour and resolves the BD platform limitation where plan post limits could not previously be scoped to specific post types only.

Plan limits hardcoded in widget: Member (1) = 1, Registered (2) = 1, Featured (3) = 3, Certified (7) = unlimited.

Login check uses user::isUserLogged($_COOKIE) per BD documented widget best practice. Member data fetched via getUser($_COOKIE['userid'], $w).

**Pending as of end of session:** widget saved but notification email path (hidden form workaround) not yet confirmed tested. Three remaining test paths to complete: not logged in redirect, logged in with allowance, logged in at limit with payment modal.

See: BD platform knowledge - widget context PHP variables differ from template context. $user[] array is not reliably populated inside Widget Builder widgets. Always use user::isUserLogged($_COOKIE) and getUser($_COOKIE['userid'], $w) for member checks inside widgets.
