# Brilliant Directories - Platform Knowledge Base

Last updated: May 9, 2026

---

## Database Structure

### Custom post field values
Stored in the `users_meta` table:
- `database` = `'data_posts'`
- `database_id` = `post_id`
- `key` = field variable name (e.g. `wowsa_swim_listing_type`)
- `value` = stored value

Multi-select / checkbox fields: comma-separated string in `value`.

`users_meta` is BD's universal meta table — used for both member profile meta and post type custom field values. Distinguish by the `database` column (`'data_posts'` vs `'user_data'`).

### Reserved words in SQL
`key`, `value`, `database` are reserved words in MariaDB. Always wrap in backticks:
```sql
SELECT database_id, `key`, `value` FROM `users_meta`
WHERE `database` = 'data_posts' AND `key` = 'water_type'
```

### data_posts columns
- `post_id` — primary key
- `data_id` — post type ID (e.g. 84 = Swims)
- `user_id` — FK to `users_data.user_id` (the author/owner account)
- `post_author` — display name of author (string, not FK)
- `post_status` — 1 = published
- `post_start_date` — date field (YYYY-MM-DD), `0000-00-00` when empty

### subscription_types (membership plans)
Plan limits are stored serialized in `users_meta`, not as a direct column.
Hardcode plan limits in widgets rather than parsing serialized JSON.

Confirmed plan IDs:
| ID | Plan              |
|----|-------------------|
| 1  | Member            |
| 2  | Registered        |
| 3  | Featured          |
| 4  | Admin Blog Author |
| 5  | General User      |
| 6  | Unclaimed Listing |
| 7  | Certified         |

---

## Post Type: Swims (data_id 84)

Slug: `/swims`  
Form: `swim_fields`  
Category field: `wowsa_swim_listing_type` — values: `Race`, `Marathon Route`, `Swim Trip`

Previous separate post types (now unpublished, retained for reference):
- Race: data_id 81
- Marathon Route: data_id 82
- Swim Trip: data_id 83

### Field variable names (key)
| Field                      | Variable                       | Notes                                    |
|----------------------------|--------------------------------|------------------------------------------|
| Listing Type               | `wowsa_swim_listing_type`      | Race / Marathon Route / Swim Trip        |
| Country                    | `country_code`                 | ISO code, e.g. AU                        |
| State (short name)         | `state_sn`                     | Full state name                          |
| Country (full name)        | `country_sn`                   | Full country name                        |
| State code                 | `state_code`                   | Abbreviation                             |
| Water Type                 | `water_type`                   |                                          |
| Distance Bucket            | `distance_bucket`              | Range label, e.g. "1-5km"               |
| Swimmer Level              | `swimmer_level`                |                                          |
| Water Temperature          | `typical_water_temperature`    | Labels include F equivalents             |
| Season Months (Swim Trip)  | `season_months`                | Comma-sep slugs: jan,feb,mar...          |
| Route Distance             | `route_distance`               | Marathon Routes only                     |
| Governing Body             | `governing_body`               | Marathon Routes only                     |
| Video                      | `post_video`                   | BD native — stores pre-rendered iframe   |
| Registration URL           | `post_url`                     | BD native                                |

### Water temperature display labels (slugs unchanged)
- Hot (31C+ / 88F+)
- Warm (21.0-30.9C / 70-88F)
- Moderate (16.0-20.9C / 61-69F)
- Cold (5.1-15.9C / 41-60F)
- Ice (0-5C / 32-41F)

---

## Widget Builder

### Constraints
- **No named function definitions** — inline PHP only inside Widget Builder widgets.
- Anonymous closures (`function($x) { ... }`) are allowed.
- Use `$w->db->get_results()` and `$w->db->get_row()` for direct queries.
- Use `$w->db->escape()` for escaping query input.

### Login check inside widgets
**Do NOT use** `$user['active'] == 2` — the `$user` array is not reliably populated in widget context.

**Use instead:**
```php
user::isUserLogged($_COOKIE)
// and to get member data:
$member = getUser($_COOKIE['userid'], $w);
```

### Calling other widgets
```php
echo widget("WOWSA - Claim This Listing");
echo widget("WOWSA - Socials");
```

### Custom Search Engine Widget (post type search)
BD's Custom Search Engine Widget for post types is **display-only**. It replaces the sidebar search form but has no mechanism to inject WHERE clauses into BD's native query engine. BD's query reads only native parameters: `q`, `location_value`, `daterange`, `category`, `price`.

Custom field filtering requires either:
1. A third-party marketplace plugin (Business Labs Custom Search Filters, ~$195), or
2. A fully standalone custom results page with a direct SQL query — **the approach used for `/find-swims`**.

---

## Search — /find-swims Architecture

The `/find-swims` page bypasses BD's native search entirely. It is a Web Page Builder page containing `[widget=WOWSA - Find Swims]` with full-width layout and no sidebar.

The widget:
- Queries `data_posts` directly via `$w->db`
- Joins `users_meta` aliases for each active filter
- Always joins the `mlt` alias (wowsa_swim_listing_type) — required for both the category WHERE clause and the ORDER BY
- Sort order: plan tier DESC (`FIELD(ud.subscription_id, 7, 3, 2, 1)`), then Marathon Routes float above others (`CASE WHEN mlt.value = 'Marathon Route' THEN 0 ELSE 1 END`), then `post_start_date ASC`
- Date filter logic: Race → filter by `MONTH(post_start_date)`, Swim Trip → filter by `season_months` LIKE, Marathon Route → always shown

### SQL bug: mlt alias undefined when no date filter applied
**Symptom:** SQL error on `/find-swims` when loaded with no filters.

**Root cause:** The `mlt` join (listing type) was only added to `$metaJoins` inside the `if ($dateFilterActive)` block, but the `ORDER BY` clause always references `mlt.value`.

**Fix (applied in widget_find_swims.php):**
Move the `mlt` join permanently outside the date filter block:
```php
// Always present — do NOT move inside the date filter block
$metaJoins .= " LEFT JOIN `users_meta` AS mlt"
    . " ON mlt.database_id = dp.post_id"
    . " AND mlt.`database` = 'data_posts'"
    . " AND mlt.`key` = 'wowsa_swim_listing_type'";
```
Then the `season_months` join (`msm`) is the only join that stays inside `if ($dateFilterActive)`.

---

## Video — post_video field

BD's native `post_video` field stores a **complete pre-rendered iframe embed string**, not a raw URL. YouTube and Vimeo URLs entered in the form are converted by BD automatically.

**Do not** try to parse or rebuild the embed from the stored value.

**Do** strip BD's hardcoded `width`, `height`, and `style` attributes before echoing, so the responsive CSS wrapper controls dimensions:
```php
$videoHtml = $post['post_video'];
$videoHtml = preg_replace('/\s+width="[^"]*"/',  '', $videoHtml);
$videoHtml = preg_replace('/\s+height="[^"]*"/', '', $videoHtml);
$videoHtml = preg_replace('/\s+style="[^"]*"/',  '', $videoHtml);
echo $videoHtml;
```

Wrap in `.wowsa-video-wrap > .embed-responsive.embed-responsive-16by9` for responsive sizing.
Keep video in its **own section** outside any Gallery `if` block — placing it inside a gallery conditional causes a large gap equal to 56.25% of page width when the gallery is empty.

---

## Google Maps API

Maps require:
1. Active billing account (not Free Trial) — Free Trial accounts cause "This page can't load Google Maps correctly" even with a valid key.
2. Correct referrer entries in Google Cloud Console > APIs & Services > Credentials:
   - `directory.openwaterswimming.com/*`
   - `*.directory.openwaterswimming.com/*`
   - `*.managemydirectory.com/*`
   - `managemydirectory.com`

Note: `*directory.openwaterswimming.com*` (no leading dot) is incorrect for a subdomain. The pattern requires the explicit subdomain entry.

Referrer changes take ~5 minutes to propagate.

---

## Dynamic Country-to-State Filtering (Form Fields)

Triggered purely by Outer Element IDs on the form fields — no wrapper divs or CSS classes required.

- Country field Outer Element ID: `country-chained`
- State field Outer Element ID: `state-chained`

Set in Toolbox > Form Manager > [form] > [field] > Outer Element ID.

---

## Claim This Listing Widget

Built in Toolbox > Widget Builder as "WOWSA - Claim This Listing".

Logic:
1. If `claim_status` = `'claimed'` → show CLAIMED label.
2. If visitor not logged in → redirect to `/join` with post context in `sessionStorage`.
3. If logged in → count published posts (`post_status = 1`) authored by member across **data_id 84** (Swims, consolidated). Compare against hardcoded plan limit.
4. If allowance remains → show free inline claim form (pre-fills `claim_this_listing` form fields).
5. If at limit → show Claim Now button (digital product ID 1, $99) + silent pre-payment notification to `contact@openwaterswimming.com`.

Hardcoded plan limits: Member (1) = 1, Registered (2) = 1, Featured (3) = 3, Certified (7) = unlimited.

> **Note:** Earlier version of this widget counted posts across `data_id IN (81, 82, 83)` (old separate post types). After consolidation to data_id 84, the post count query must reference `data_id = 84`. Update this in the widget code when the claim widget is next edited.

Digital product: Claim Listing — product ID 1, data_category_id 73, $99.
Stripe publishable key: `pk_live_vZkKVYWklfIGCFEF3SlovrIF`

---

## Member Category / Specialty

BD native approach (not custom widget on checkout form):
- Top-level: WOWSA Member
- Sub-level: Organizer, Support, Swimmer
- Sub-sub-level: Race Organizer (16), Marathon Organizer (17), Swim Trip Operator (18), Coach (19), Guide (20), Observer (21), Wellness Swimmer (22), Racing Swimmer (23), Marathon Swimmer (24)

Set via Bootstrap Theme - Account - Select Categories widget on the member profile Listing Details form.
Pre-Select Top Category set per plan in Finance > Membership Plans > General.

Custom HTML checkbox widgets **cannot** save to the native `services` field during checkout — only BD's native category selection widget writes correctly to that field.

---

## Digital Products (Claim payment flow)

- Pre-payment notification: use a hidden HTML form submitted via `fetch()` to the BD Save Form API endpoint, routing through the native `claim_this_listing` form. This fires BD's native email notification reliably.
- `onCompleteFunction` after `processPayment()` supports only page reload or URL redirect — no hook for server-side logic.
- Post author reassignment after payment is a **manual admin step**: BD admin > Bulk Actions > Assign New Author. No BD webhook for digital product purchases confirmed.

---

## BD Platform Limitations (confirmed)

| Limitation | Confirmed |
|-----------|-----------|
| Custom Search Engine Widget cannot inject WHERE clauses into native query | ✓ |
| No native PHP variable for member post count or plan post limit | ✓ |
| `$user[]` array not reliably populated inside Widget Builder widgets | ✓ |
| Native Claim add-on operates at member profile level only, not per-post | ✓ |
| One active plan per account at a time | ✓ |
| No post-submit JS callback or DOM event for embedded BD forms | ✓ |
| `onCompleteFunction` after `processPayment()` = reload/redirect only | ✓ |
| No BD webhook for digital product purchases | ✓ (unconfirmed in training data) |
| Custom HTML checkbox widgets cannot save to native `services` field on checkout | ✓ |
