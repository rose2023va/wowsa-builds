<?php
/**
 * WOWSA - Homepage Search Bar
 * BD Widget Builder widget — inline PHP only, no named function definitions.
 * Submits to /find-swims with keyword, location, and month filters.
 *
 * Month dropdown groups:
 *   - Current year: remaining months from today onward (labelled "Month YYYY")
 *   - Next year: all 12 months labelled "Month YYYY"
 *   - Plus a "Next year – any month" option at the top of the next-year group
 *
 * No daterangepicker dependency. No BD native search integration required.
 * Target page: /find-swims (custom PHP SQL widget, not BD native search).
 */

$currentYear  = intval(date('Y'));
$currentMonth = intval(date('n'));
$nextYear     = $currentYear + 1;

$monthLabels = [
    1=>'January',2=>'February',3=>'March',4=>'April',
    5=>'May',6=>'June',7=>'July',8=>'August',
    9=>'September',10=>'October',11=>'November',12=>'December',
];
$monthSlugs = [
    1=>'jan',2=>'feb',3=>'mar',4=>'apr',5=>'may',6=>'jun',
    7=>'jul',8=>'aug',9=>'sep',10=>'oct',11=>'nov',12=>'dec',
];
?>

<div class="wowsa-hero-search">
  <form method="GET" action="/find-swims" class="wowsa-hero-search-form">

    <div class="wowsa-hero-search-fields">

      <div class="wowsa-hero-search-field">
        <label for="whs-keyword" class="wowsa-hero-search-label">What</label>
        <input type="text" id="whs-keyword" name="q"
               placeholder="Race name, route, or swim trip"
               class="wowsa-hero-search-input">
      </div>

      <div class="wowsa-hero-search-divider"></div>

      <div class="wowsa-hero-search-field">
        <label for="whs-location" class="wowsa-hero-search-label">Where</label>
        <input type="text" id="whs-location" name="location"
               placeholder="Country or state"
               class="wowsa-hero-search-input">
      </div>

      <div class="wowsa-hero-search-divider"></div>

      <div class="wowsa-hero-search-field">
        <label for="whs-month" class="wowsa-hero-search-label">When</label>
        <select id="whs-month" name="month" class="wowsa-hero-search-select">
          <option value="">Any time</option>

          <optgroup label="<?= $currentYear ?>">
            <?php for ($m = $currentMonth; $m <= 12; $m++): ?>
              <option value="<?= $monthSlugs[$m] ?>">
                <?= $monthLabels[$m] ?> <?= $currentYear ?>
              </option>
            <?php endfor; ?>
          </optgroup>

          <optgroup label="<?= $nextYear ?>">
            <option value="next-year-any">Next year &ndash; any month</option>
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $monthSlugs[$m] ?>">
                <?= $monthLabels[$m] ?> <?= $nextYear ?>
              </option>
            <?php endfor; ?>
          </optgroup>

        </select>
      </div>

    </div><!-- .wowsa-hero-search-fields -->

    <button type="submit" class="wowsa-hero-search-submit">Search Now</button>

  </form>
</div>

<style>
/* ── Homepage hero search bar ────────────────────────────────── */
.wowsa-hero-search{width:100%;max-width:900px;margin:0 auto;padding:0 16px}
.wowsa-hero-search-form{display:flex;align-items:stretch;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.15);overflow:hidden}

.wowsa-hero-search-fields{display:flex;flex:1;align-items:stretch}
.wowsa-hero-search-field{flex:1;padding:14px 20px;display:flex;flex-direction:column;justify-content:center;min-width:0}
.wowsa-hero-search-divider{width:1px;background:#e4e7ec;margin:12px 0;flex-shrink:0}

.wowsa-hero-search-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#888;margin-bottom:4px;display:block}
.wowsa-hero-search-input{border:none;outline:none;font-size:15px;color:#1a1a2e;background:transparent;width:100%;padding:0}
.wowsa-hero-search-input::placeholder{color:#aaa}
.wowsa-hero-search-select{border:none;outline:none;font-size:15px;color:#1a1a2e;background:transparent;width:100%;padding:0;-webkit-appearance:none;appearance:none;cursor:pointer}

.wowsa-hero-search-submit{padding:0 32px;background:#0077b6;color:#fff;border:none;font-size:16px;font-weight:700;cursor:pointer;white-space:nowrap;flex-shrink:0;letter-spacing:.3px}
.wowsa-hero-search-submit:hover{background:#005f8e}

/* Mobile: stack fields vertically */
@media(max-width:680px){
  .wowsa-hero-search-form{flex-direction:column;border-radius:10px}
  .wowsa-hero-search-fields{flex-direction:column}
  .wowsa-hero-search-divider{width:100%;height:1px;margin:0 12px;width:auto}
  .wowsa-hero-search-submit{height:66px !important;line-height:66px !important;font-size:16px !important;display:block !important;width:100% !important;padding:0}
}
</style>
