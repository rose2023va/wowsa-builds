# Session Log: BD Build - Maps, Filters, Search Architecture, Post Type Consolidation
**File:** directory/bd/session-logs/2026-05-07-maps-filters-search-consolidation.md
**Date:** May 7-9, 2026
**Sessions:** Two days across May 7 afternoon through May 9 evening

---

## What We Did

### 1. Google Maps API Setup and Fix

1. Opened BD admin > Settings > General Settings > Integrations and confirmed the Google Maps Javascript API Key field had a key entered but maps were not rendering.
2. Identified the error "This page can't load Google Maps correctly" in the browser - standard Google error for missing or invalid key configuration.
3. Checked Google Cloud Console > Billing and confirmed the account was on Free Trial with a declined payment method. Updated billing and clicked Activate Full Account to convert from Free Trial to Active.
4. After activation, new error appeared: "This page didn't load Google Maps correctly. See the JavaScript console for technical details."
5. Browser console showed: `Google Maps JavaScript API error: RefererNotAllowedMapError - Your site URL to be authorized: https://directory.openwaterswimming.com/account/races/add`
6. Went to Google Cloud Console > APIs & Services > Credentials > Maps Platform API Key > Key restrictions > Website restrictions.
7. Existing referrer entry was `*directory.openwaterswimming.com*` - missing the dot, incorrect pattern for a subdomain.
8. Edited the entry to `directory.openwaterswimming.com/*` and added a second entry `*.directory.openwaterswimming.com/*`.
9. Confirmed also present: `*.managemydirectory.com/*` and `managemydirectory.com` as required by BD documentation.
10. Waited approximately 5 minutes for referrer changes to propagate.
11. Maps loaded correctly on all Race form pages and the Race detail page template.

**Key learning:** Google Maps API requires the site to be on an active billing account (not Free Trial) even for free-tier usage. The referrer pattern for a BD site on a subdomain (not a subfolder) requires both `subdomain.domain.com/*` and `*.subdomain.domain.com/*`.

---

### 2. Dynamic Country-to-State Filtering on Race Form

1. Identified that the State dropdown on the Race form was showing only US states regardless of which country was selected.
2. Compared Race form field configuration to the BD payment/checkout form where dynamic filtering worked correctly.
3. Payment form used database variable `country_code` for Country field and `state_code` for State field, with Outer Element IDs `country-chained` and `state-chained` respectively.
4. Checked Race form Country and State fields - confirmed matching field types, labels, database variables, and CSS wrapper divs were already in place from a previous attempt.
5. Identified that the Outer Element IDs `country-chained` and `state-chained` were not set on the Race form fields.
6. In Toolbox > Form Manager > Race form, opened the Country field settings and entered `country-chained` in the Outer Element ID field. Saved.
7. Opened the State field settings and entered `state-chained` in the Outer Element ID field. Saved.
8. Tested on the Race form - State dropdown now filters dynamically based on Country selection.

**Key learning:** BD's dynamic country-to-state filtering is triggered purely by the Outer Element IDs `country-chained` and `state-chained` on the respective fields. No wrapper divs or CSS classes are required for post type forms. These IDs are functional hooks in BD's front-end JavaScript, not just CSS selectors.

---

### 3. Race Listing Detail Page - Video Fix

1. Identified that the video URL entered in the Race form was not saving - field appeared blank after save.
2. Inspected WOWSA - Video Embed widget code - found the input field used `name="video_url"` and `$post['video_url']` but the form field database variable was `wowsa_video_embed`. Mismatch prevented saving.
3. Updated widget to use `name="wowsa_video_embed"` throughout. Saved.
4. Tested again - URL still not saving.
5. Checked Toolbox > Form Manager > Race form > Video field. Found a native BD video field with database variable `post_video` and type Text - Single Line already in the form.
6. Identified that BD's native video field stores a pre-rendered iframe embed string in `$post['post_video']`, not a raw URL.
7. Switched approach: removed WOWSA - Video Embed widget dependency. Updated race_template.php to read from `$post['post_video']` instead of `$post['wowsa_video_embed']`.
8. Replaced the entire video rendering block in race_template.php with:
   - `if (isset($post['post_video']) && !empty($post['post_video']))` check
   - Direct `echo $post['post_video']` inside `embed-responsive embed-responsive-16by9` wrapper
9. Video rendered but BD outputs the iframe with hardcoded `width`, `height`, and `style` attributes that override the responsive wrapper.
10. Added PHP `preg_replace` calls to strip `width`, `height`, and `style` attributes from the BD-generated iframe before echoing.
11. Video still displayed too large on desktop and got cut off on mobile.
12. Moved video section out of the Gallery `if` block into its own standalone section in race_template.php.
13. Added `.wowsa-video-wrap` CSS wrapper with `max-width: 640px` and `margin: 0 auto`, with `@media (max-width: 768px)` override to `max-width: 100%`.
14. Added CSS targeting `.wowsa-video-wrap .embed-responsive iframe` with `width: 100% !important` and `height: 100% !important` to override BD's inline styles.
15. Video rendered correctly at 640px max width on desktop, full width on mobile with no cutoff.

**Key learning:** BD's native `post_video` field stores a complete rendered iframe embed string, not a raw URL. Do not try to parse or rebuild the embed - strip the hardcoded dimension attributes and echo directly.

---

### 4. Custom Search Filter Investigation for Race Archive

1. BD support enabled the "Allow Custom Search Query Widgets for Post Types" setting on the account.
2. Built WOWSA - Race Search Filters widget with keyword, location, date, country, water type, swimmer level, distance, and water temperature filters. Assigned to Race post type via Content > Edit Post Settings > Race > Search Results Design > Additional Settings > Custom Search Engine Widget Name.
3. Widget rendered in sidebar correctly but Race archive returned no results when any custom filter was applied.
4. Investigated whether the Custom Search Engine Widget could inject WHERE clauses into BD's native query.
5. Retrieved full native Bootstrap Theme - Module - Event Search widget code (HTML, CSS, JS files). Confirmed the widget is purely a form and JS initializer with no query injection mechanism. BD's query engine reads only its own native parameters: `q`, `location_value`, `daterange`, `category`, `price`.
6. Examined BD database structure via phpMyAdmin. Exported `data_posts`, `users_meta`, and `form_fields` tables. Confirmed custom field values are stored in `users_meta` table with `database = 'data_posts'`, `database_id = post_id`, `key = field_variable_name`, `value = stored_value`. Comma-separated values for checkbox/multi-select fields.
7. Confirmed that `users_meta` is BD's universal meta table - used for both member profile meta and post type custom field values, distinguished by the `database` column.
8. Confirmed BD's native search query does not read `users_meta` for custom fields. The Custom Search Engine Widget setting only replaces the search form display - it does not expose a hook for injecting custom WHERE clauses.
9. Noted that third-party marketplace plugins (Business Labs Custom Search Filters, $195) exist specifically to solve this problem, confirming BD does not support it natively.

**Key learning:** BD's Custom Search Engine Widget for post types is display-only. It replaces the sidebar search form but has no mechanism to modify the underlying query. Custom field filtering requires either a marketplace plugin or a fully standalone custom results page built outside BD's native search pipeline.

---

### 5. Post Type Architecture Consolidation Decision

1. During the search investigation, Rose raised with Quinn the option of consolidating the three post types (Race ID 81, Marathon Route ID 82, Swim Trip ID 83) into a single Swims post type (ID 84) with a category dropdown.
2. Quinn approved: "yes, please - my instinct is that's simpler long term."
3. Confirmed existing Swims post type (data_id 84, slug `/swims`, form `swim_fields`) was already in BD from a previous build phase. Unpublished the three separate post types rather than deleting, in case of future revisit.
4. Confirmed Swims post type categories: Race, Marathon Route, Swim Trip stored in `wowsa_swim_listing_type` field. Category URL parameter format: `category[]=Race`, `category[]=Marathon+Route`, `category[]=Swim+Trip`.
5. Reviewed consolidated `swim_fields` form. Confirmed all fields from Race, Marathon Route, and Swim Trip forms are present. Route-specific fields (`route_distance`, `governing_body`) and Swim Trip-specific fields (`season_months` checkboxes for all 12 months, `swim_month`) coexist with Race-specific fields (`post_start_date`, `distance_bucket`). All non-universal fields are optional.
6. Updated `typical_water_temperature` display labels to include Fahrenheit equivalents: Warm (21.0-30.9C / 70-88F), Moderate (16.0-20.9C / 61-69F), Cold (5.1-15.9C / 41-60F), Ice (0-5C / 32-41F), Hot (31C+ / 88F+). Slugs unchanged.
7. Confirmed `season_months` database variable and checkboxes for Swim Trip operating months. Decided on "Season Months" as the field label with `season_months` as the database variable.

---

### 6. /find-swims Page Widget Built

1. Designed sort order logic with Quinn: within each plan tier (Certified 7, Featured 3, Registered 2, Member 1, Unclaimed 0), Marathon Routes float above Races and Swim Trips.
2. Designed date filter logic: when a month filter is applied, Races match against `post_start_date`, Swim Trips match against `season_months` meta field (LIKE '%month_slug%'), Marathon Routes are always shown regardless of date filter.
3. Built WOWSA - Find Swims widget with:
   - Full SQL query joining `data_posts`, `users_data`, `subscription_types`, and multiple `users_meta` aliases for each custom field filter
   - Category, keyword, location, month/date, country, water type, swimmer level, distance, and water temperature filters
   - 3-column grid, 12 per page, page number pagination
   - Cards showing: category badge (top right), plan badge Certified/Featured/Unclaimed (top left), photo, location from `state_sn` and `country_sn`, date (post_start_date for Races, season_months for Swim Trips, none for Marathon Routes), title, hosted by (hidden if post_author is WOWSA), water type, distance pills, View listing button
   - ORDER BY: `FIELD(ud.subscription_id, 7, 3, 2, 1)` then `CASE WHEN mlt.value = 'Marathon Route' THEN 0 ELSE 1 END` then `dp.post_start_date ASC`
4. Built WOWSA - Homepage Search Bar widget with keyword, location, and month dropdown pointing to `/find-swims`. Dropdown groups: current year remaining months, next year months plus a "next year any month" option. No daterangepicker dependency.
5. Widget saved to Toolbox > Widget Builder. Page `/find-swims` to be created in Web Page Builder with `[widget=WOWSA - Find Swims]` as page content, full width, no sidebar.

**Known open issue:** The `mlt` alias in the ORDER BY clause references the listing type join that is only added when a date filter is active. When no date filter is applied, the `mlt` alias is undefined and the ORDER BY will cause a SQL error. Fix required before production testing: add a permanent `mlt` join outside the date filter condition block, or use a subquery approach.

---

### 7. Homepage Search Bar Mobile CSS Fix

1. Placed WOWSA - Homepage Search Bar widget in its own homepage section (Section 1) below the hero.
2. On mobile, the three search fields stacked vertically correctly but the Search Now button appeared disproportionately small.
3. Added CSS targeting `.wowsa-hero-search-submit` with `height: 66px !important`, `line-height: 66px !important`, `font-size: 16px !important`, and `display: block !important; width: 100% !important`.
4. `line-height` was the critical property - without it the button text was vertically misaligned and the button appeared collapsed.
5. Confirmed rendering correct on mobile after fix.

---

### 8. Streaming Widgets Updated

1. Updated WOWSA - Streaming - Recent Races widget with new card design: photo, state_code + country_code location, start date, title, distance pills, View listing button. Removed organizer avatar and plan label. Updated to read `state_code` and `country_code` from `getMetaData` rather than `post_location`.
2. Built WOWSA - Streaming - Recent Marathon Routes widget (data_id 82, featureUrl `marathon-routes`) - same card design without start date.
3. Built WOWSA - Streaming - Recent Swim Trips widget (data_id 83, featureUrl `swim-trips`) - same card design without start date.
4. Note: all three streaming widgets reference data_ids 81, 82, 83. These need updating to data_id 84 with category filter now that the three post types have been consolidated into Swims.

**Known open issue:** Location fields (state_code, country_code) not confirmed displaying on cards - devmode check on a test listing required to verify exact meta key names stored in users_meta.

---

## Errors Encountered

### Maps: RefererNotAllowedMapError
- Triggered by: loading any Race form or detail page after billing was activated
- Error: `Google Maps JavaScript API error: RefererNotAllowedMapError - Your site URL to be authorized: https://directory.openwaterswimming.com/account/races/add`
- Tried: adjusting referrer pattern from `*directory.openwaterswimming.com*` to `*.directory.openwaterswimming.com/*`
- What worked: adding `directory.openwaterswimming.com/*` (the subdomain itself without wildcard prefix) alongside `*.directory.openwaterswimming.com/*`

### Video: Not Saving
- Triggered by: entering a Vimeo URL in the Video field on the Race form and saving
- Behavior: URL disappeared after save, field appeared blank
- Tried: fixing variable name mismatch in WOWSA - Video Embed widget (video_url to wowsa_video_embed)
- What worked: switching to BD's native post_video field entirely and removing the custom widget dependency

### Video: Padding Gap
- Triggered by: video section inside the Gallery `if` block with `embed-responsive-16by9`
- Behavior: large green gap below video equal to 56.25% of page width
- What worked: moving video to its own standalone section outside the Gallery block

### Custom Filter: No Results
- Triggered by: submitting filter form with any custom field value selected (water_type, swimmer_level etc)
- Behavior: BD archive returned "Sorry, we did not find any results for your search"
- Root cause: BD's native query engine does not read custom field GET parameters. The Custom Search Engine Widget is display-only.

### Find Swims Widget: ORDER BY mlt alias undefined
- Triggered by: loading /find-swims with no date filter applied
- Expected behavior: SQL error because mlt alias is only joined when date filter is active but ORDER BY always references it
- Status: not yet tested in production - fix required before deployment

---

## Workarounds Tested

### Custom Field Filtering via Custom Search Engine Widget
- Reasoning: BD support enabled the setting specifically to allow custom search query widgets. Assumed it would allow WHERE clause injection.
- Outcome: setting only replaces the form display. No query hook available. Abandoned.

### Client-Side JS Filtering on Native Archive
- Reasoning: simpler short-term solution - hide/show cards based on filter parameters without touching the query
- Outcome: rejected because it only filters the current page of results, not the full dataset. Not viable for a directory with hundreds of listings.

### Video Embed via Custom Widget
- Reasoning: native BD video field was unknown at the time, assumed custom widget was needed for YouTube/Vimeo parsing
- Outcome: abandoned after discovering BD's native post_video field stores a pre-rendered embed string and handles all parsing internally

---

## Final Decision

### Maps
Google Maps is fully working. API key active with correct referrer entries: `directory.openwaterswimming.com/*`, `*.directory.openwaterswimming.com/*`, `*.managemydirectory.com/*`, `managemydirectory.com`.

### Video
Switched to BD's native `post_video` field. WOWSA - Video Embed custom widget is no longer used for the Race form or the Swims form. race_template.php updated to read from `$post['post_video']` with inline style stripping and responsive CSS wrapper.

### Custom Field Filtering
BD's native search pipeline cannot filter by custom post meta fields without a third-party marketplace plugin. Decision: build a fully standalone `/find-swims` page with custom PHP SQL query directly against `data_posts` and `users_meta`. Native `/swims` archive remains intact and unmodified. The `/find-swims` widget handles all filtered search. Homepage search bar points to `/find-swims`.

See: directory/knowledge-base/brilliant-directories.md > Search > Custom Field Filtering Not Supported Natively

### Post Type Architecture
Three separate post types (Race, Marathon Route, Swim Trip) consolidated into a single Swims post type (data_id 84) with category dropdown field `wowsa_swim_listing_type`. Existing post types unpublished and retained for potential future reference. All streaming widgets and the Find Swims widget reference data_id 84. Homepage streaming widgets (Recent Races, Recent Marathon Routes, Recent Swim Trips) need rebuilding as a single WOWSA - Streaming - Recent Swims widget filtered by category, or kept as three separate widgets with data_id updated to 84 and category filter added to each SQL query.
