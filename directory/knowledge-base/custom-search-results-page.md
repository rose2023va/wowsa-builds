# How to Build a Custom Search and Results Page in Brilliant Directories

**File:** directory/bd/knowledge-base/custom-search-results-page.md
**Last updated:** May 2026
**Author:** Rose
**Status:** Proven in production - /find-swims

---

## Background and Why This Exists

Brilliant Directories' native search pipeline cannot filter post type listings by custom meta fields. The native search engine reads only its own parameters (`q`, `location_value`, `daterange`, `category`, `price`) and ignores any other GET parameters. The "Allow Custom Search Query Widgets for Post Types" setting (which BD support must enable per account) only replaces the sidebar search form display - it does not expose a hook for injecting WHERE clauses into BD's native query.

This means any directory that needs to filter by custom fields (water type, swimmer level, distance, temperature, season months, country, etc.) must bypass BD's native search entirely and build a standalone custom page.

This document describes the proven pattern for doing that.

---

## Core Architecture

The pattern has three components:

**1. A custom Widget Builder widget** that handles the full page - search form, SQL query, and results rendering. It lives in Toolbox > Widget Builder and is called via shortcode on a Web Page Builder page.

**2. A Web Page Builder page** at the desired URL slug (e.g. `/find-swims`, `/find-marathon-routes`, `/hawaii`) with the widget shortcode as the page content and no sidebar assigned.

**3. Optional: a homepage search bar widget** that submits to the custom page URL with pre-populated GET parameters.

---

## Database Structure - Critical Knowledge

Before writing any query, understand how BD stores data:

### Native fields on data_posts
These are direct columns on the `data_posts` table and must be referenced as `dp.column_name` in SQL:
- `dp.post_id`
- `dp.post_title`
- `dp.post_content`
- `dp.post_image`
- `dp.post_filename`
- `dp.post_start_date` - stored as `YYYYMMDD` string, no time component, no separators
- `dp.post_expire_date` - stored as `YYYYMMDDHHmmss`
- `dp.post_status` - 1 = published
- `dp.post_author`
- `dp.post_tags`
- `dp.post_category` - the BD native category field for post types
- `dp.country_sn` - 2-letter country code
- `dp.state_sn` - state/province code
- `dp.data_id` - the post type ID (e.g. 84 for Swims)
- `dp.user_id` - links to users_data

### Custom fields in users_meta
Every custom field added via Form Manager is stored in `users_meta` with:
- `database` = `'data_posts'`
- `database_id` = the `post_id`
- `key` = the field's database variable name
- `value` = the stored value

Checkbox/multi-select fields store comma-separated slugs: `beginner,intermediate,advanced`

To filter by a custom field, JOIN `users_meta` with an alias:
```sql
LEFT JOIN `users_meta` AS m1 
    ON m1.database_id = dp.post_id 
    AND m1.`database` = 'data_posts' 
    AND m1.`key` = 'water_type'
```
Then add to WHERE: `AND m1.value = 'lake'`

For multi-value fields (checkboxes), use LIKE:
```sql
AND m1.value LIKE '%beginner%'
```

### users_data columns - critical gotchas
- There is NO `full_name` column. Names are stored as `first_name` and `last_name` separately.
- Always use: `CONCAT(ud.first_name, ' ', ud.last_name) AS full_name`
- Member profile URL slug is in `ud.filename`
- Plan/subscription ID is in `ud.subscription_id`

### Plan IDs (WOWSA-specific)
- Certified: 7
- Featured: 3
- Registered: 2
- Member: 1
- Unclaimed: post_author = 'WOWSA'

---

## PHP Functions Available in Widget Builder Context

These BD internal functions are available inside Widget Builder widgets:

```php
// Run a SQL query against the BD database
$results = mysql($w['database'], $sql);
$row = mysql_fetch_assoc($results);
$num = mysql_num_rows($results);

// Load all users_meta values into a post array
$post = getMetaData("data_posts", $post_id, $post_array, $w);

// Render the BD country dropdown with a pre-selected value
echo countryList($selectedCountryCode, $w);

// Escape a value for use in SQL
$safe = mysql_real_escape_string($value);
```

**Important:** No PHP function definitions are permitted in Widget Builder. Use inline logic only. Anonymous functions are not available.

---

## Query Pattern

### Count query (for pagination)
Run this first to get the total result count:

```php
$countSql = "SELECT COUNT(DISTINCT dp.post_id) as total
    FROM `data_posts` AS dp
    LEFT JOIN `users_data` AS ud ON dp.user_id = ud.user_id
    LEFT JOIN `subscription_types` AS st ON ud.subscription_id = st.subscription_id
    {$metaJoins}
    WHERE {$baseWhere}";

$countResult  = mysql($w['database'], $countSql);
$countRow     = mysql_fetch_assoc($countResult);
$totalResults = (int)$countRow['total'];
```

### Main results query
```php
$sql = "SELECT DISTINCT dp.post_id, dp.post_title, dp.post_image, dp.post_filename,
        dp.post_start_date, dp.post_author, dp.post_content, dp.country_sn, dp.state_sn,
        dp.post_category, ud.subscription_id,
        CONCAT(ud.first_name, ' ', ud.last_name) AS full_name,
        ud.filename AS member_filename
    FROM `data_posts` AS dp
    LEFT JOIN `users_data` AS ud ON dp.user_id = ud.user_id
    LEFT JOIN `subscription_types` AS st ON ud.subscription_id = st.subscription_id
    {$metaJoins}
    WHERE {$baseWhere}
    ORDER BY
        FIELD(ud.subscription_id, 7, 3, 2, 1) ASC,
        CASE WHEN dp.post_category = 'Marathon Route' THEN 0 ELSE 1 END ASC,
        dp.post_start_date ASC
    LIMIT {$resultsPerPage} OFFSET {$offset}";
```

### Loading meta fields into results
After fetching rows, call `getMetaData` to load all `users_meta` values:
```php
$postArray = array();
while ($row = mysql_fetch_assoc($results)) {
    $row = getMetaData("data_posts", $row['post_id'], $row, $w);
    $postArray[] = $row;
}
```

After this call, all custom meta fields are available as `$row['field_name']`.

---

## Building Meta Joins Dynamically

Use a join index counter to avoid alias collisions:

```php
$metaJoins  = '';
$metaWheres = '';
$joinIndex  = 1;

// Single value filter (dropdown)
if ($currentWaterType != '') {
    $wt = mysql_real_escape_string($currentWaterType);
    $metaJoins  .= " LEFT JOIN `users_meta` AS m{$joinIndex} ON m{$joinIndex}.database_id = dp.post_id AND m{$joinIndex}.`database` = 'data_posts' AND m{$joinIndex}.`key` = 'water_type'";
    $metaWheres .= " AND m{$joinIndex}.value = '{$wt}'";
    $joinIndex++;
}

// Multi-value filter (checkboxes - stored as comma-separated)
if (!empty($currentSwimmerLevel)) {
    $slConds = array();
    $metaJoins .= " LEFT JOIN `users_meta` AS m{$joinIndex} ON m{$joinIndex}.database_id = dp.post_id AND m{$joinIndex}.`database` = 'data_posts' AND m{$joinIndex}.`key` = 'swimmer_level'";
    foreach ($currentSwimmerLevel as $sl) {
        $sl = mysql_real_escape_string(trim($sl));
        $slConds[] = "m{$joinIndex}.value LIKE '%{$sl}%'";
    }
    $metaWheres .= " AND (" . implode(' OR ', $slConds) . ")";
    $joinIndex++;
}
```

**Always-present joins** (needed for ORDER BY or conditional WHERE regardless of filter state) must be added outside any `if` block:
```php
// Always join - needed for ORDER BY and date filter logic
$metaJoins .= " LEFT JOIN `users_meta` AS msm ON msm.database_id = dp.post_id AND msm.`database` = 'data_posts' AND msm.`key` = 'season_months'";
```

---

## Date Filter Logic for Mixed Post Types

When a directory has multiple listing types with different date semantics (Races have `post_start_date`, Swim Trips have `season_months`, Marathon Routes have no dates), use conditional WHERE logic:

```php
if ($dateMonthSlug != '') {
    $safeMonth = mysql_real_escape_string($dateMonthSlug);
    $dateFilterWhere = "AND (
        dp.post_category = 'Marathon Route'
        OR (dp.post_category = 'Race' AND dp.post_start_date >= '{$dateStart}' AND dp.post_start_date <= '{$dateEnd}')
        OR (dp.post_category = 'Swim Trip' AND msm.value LIKE '%{$safeMonth}%')
        OR (dp.post_category IS NULL AND dp.post_start_date >= '{$dateStart}' AND dp.post_start_date <= '{$dateEnd}')
        OR (dp.post_category = '' AND dp.post_start_date >= '{$dateStart}' AND dp.post_start_date <= '{$dateEnd}')
    )";
}
```

Date range format for `post_start_date` comparisons - use clean `YYYYMMDD` with no suffix:
```php
$dateStart = $yr . $mo . '01';
$dateEnd   = $yr . $mo . str_pad($daysInMonth, 2, '0', STR_PAD_LEFT);
// Do NOT append 99999 or any time suffix - post_start_date is YYYYMMDD only
```

---

## Sorting Pattern

Standard WOWSA sort: plan tier first, then category (Marathon Routes before Races/Swim Trips within each tier), then date ascending:

```sql
ORDER BY
    FIELD(ud.subscription_id, 7, 3, 2, 1) ASC,
    CASE WHEN dp.post_category = 'Marathon Route' THEN 0 ELSE 1 END ASC,
    dp.post_start_date ASC
```

`FIELD()` returns 0 for values not in the list, so unclaimed listings (subscription_id not in 7,3,2,1) naturally sort last.

---

## Pagination Pattern

```php
$resultsPerPage = 12;
$currentPage    = isset($_GET['pg']) ? max(1, (int)$_GET['pg']) : 1;
$offset         = ($currentPage - 1) * $resultsPerPage;
$totalPages     = ceil($totalResults / $resultsPerPage);

// URL builder preserves all existing GET parameters
// Cannot use a named function - use inline or anonymous pattern
$params = $_GET;
$params['pg'] = $targetPage;
$pageUrl = '/find-swims?' . http_build_query($params);
```

For pagination links, show first page, last page, and 2 pages either side of current with dots for gaps:
```php
for ($i = 1; $i <= $totalPages; $i++) {
    if ($i == 1 || $i == $totalPages || abs($i - $currentPage) <= 2) {
        // render link or current span
    } elseif (abs($i - $currentPage) == 3) {
        echo '<span class="dots">...</span>';
    }
}
```

---

## Debugging a Silent Query Failure

BD suppresses SQL errors silently - a bad query returns 0 rows with no error message. When count query finds results but main query returns 0:

1. Add debug output temporarily:
```php
echo "Main query rows: " . $resultNum . "<br>";
echo "SQL: " . $sql . "<br>";
```

2. Copy the printed SQL and run it directly in phpMyAdmin.

3. Common causes of silent failure:
   - Reference to `ud.full_name` (does not exist - use CONCAT)
   - ORDER BY referencing a JOIN alias that was not added for this query state
   - Date range with appended time suffix (`99999`) causing string comparison failure
   - DISTINCT combined with ORDER BY on expressions not in SELECT

---

## Homepage Search Bar Connection

The homepage search bar is a separate widget that submits a simple GET form to the custom results page URL. It does not need to run any query itself.

Key points:
- Use a dropdown for month selection instead of a daterangepicker - no JS dependency
- Submit to `/find-swims` (or whatever the results page slug is)
- Parameter names must match exactly what the results widget reads from `$_GET`
- The `googleSuggest googleLocation` CSS classes on the location input activate BD's native Google Places autocomplete

```html
<form action="/find-swims" method="get">
    <input type="text" name="q" placeholder="Keyword">
    <input type="text" name="location_value" class="googleSuggest googleLocation" autocomplete="off">
    <select name="date_filter">
        <option value="any">Any date</option>
        <!-- month options -->
    </select>
    <button type="submit">Search Now</button>
</form>
```

---

## Building a Regional Page

A regional page (e.g. Hawaii, New South Wales) is the same widget with a pre-filtered URL. No code changes needed to the widget.

Nav link format:
```
/find-swims?country_code=US&state_code=HI
```

Or create a dedicated Web Page Builder page at `/hawaii` with the widget shortcode, and link to it as `/hawaii?swim_category=Race` to pre-filter by category.

The widget reads all filters from `$_GET` so any parameter can be pre-set via the URL.

---

## Deployment Checklist

1. Build widget in Toolbox > Widget Builder - paste full PHP/HTML/CSS code
2. Save widget and note the exact widget name
3. Go to Web Page Builder > create new page
4. Set slug (e.g. `find-swims`)
5. Set page content to `[widget=WOWSA - Find Swims]`
6. Set layout to full width with no sidebar
7. Publish page
8. Test at the URL with no filters - confirm all listings load
9. Test each filter individually
10. Test pagination if more than 12 results
11. Test homepage search bar submission to confirm parameters pass correctly

---

## Known Limitations

- Location filter (`location_value`) is passed to the custom page but not used in the SQL query - BD's geocoding/radius search is not accessible from custom queries. Location is currently display-only in the custom page context.
- The `countryList()` function renders a full country dropdown but the selected value must be stored as a 2-letter ISO code matching `dp.country_sn`.
- Widget Builder does not support PHP function definitions. All helper logic must be inline.
- BD's native lazy load and map view add-ons do not apply to custom result pages - these are BD-only features for the native search pipeline.
