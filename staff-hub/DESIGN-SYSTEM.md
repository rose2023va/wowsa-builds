# Design System - matched to Quinn's Lovable landing pages

Source: https://freestyle-biomechanics.openwaterswimming.com/ (Quinn confirmed this style is consistent across all his Lovable landing pages). Pulled directly from the site's compiled CSS on 2026-08-03, so these are the real production values, not a guess.

Stack fingerprint: built with Tailwind CSS + shadcn/ui conventions (the `--primary` / `--secondary` / `--muted` / `--accent` / `--destructive` variable naming is shadcn's). Our hub site doesn't need the full framework — just matching these tokens in plain CSS gets the same look.

## Colors (OKLCH - copy these directly into CSS, all modern browsers support `oklch()`)

Light mode (default):

| Token | Value | Role |
|---|---|---|
| `--background` / `--paper` | `oklch(97.2% .006 95)` | Page background - warm cream, not pure white |
| `--foreground` / `--ink` | `oklch(19% .012 250)` | Body text - near-black, cool undertone |
| `--primary` | `oklch(19% .012 250)` | Primary buttons/elements (dark) |
| `--primary-foreground` | `oklch(97.2% .006 95)` | Text on primary elements (cream) |
| `--secondary` / `--muted` | `oklch(93% .008 95)` | Secondary surfaces, subtle backgrounds |
| `--muted-foreground` | `oklch(48% .012 250)` | Secondary/de-emphasized text |
| `--accent` | `oklch(48% .062 218)` | Ocean blue - CTAs, links, highlights |
| `--accent-foreground` | `oklch(97.2% .006 95)` | Text on accent elements |
| `--destructive` | `oklch(55% .19 27)` | Errors/warnings (red-orange) |
| `--border` / `--rule` / `--input` | `oklch(86% .008 95)` | Borders, dividers, input outlines |
| `--ring` | `oklch(48% .062 218)` | Focus ring - same as accent |

Dark mode exists on the source site (`--background: oklch(15.5% .014 250)`, etc.) but isn't needed for the hub unless we want a dark mode toggle later.

**Approximate hex** (for quick reference only - e.g. dropping into Figma or a slide; use the real `oklch()` values above in actual code, don't rely on these for pixel-perfect match):

- Cream background: ~`#F7F5EF`
- Near-black ink: ~`#1C2029`
- Light warm gray (secondary): ~`#EAE7E0`
- Ocean blue (accent): ~`#3E7191`
- Border gray: ~`#D9D6CE`

## Typography

- **Display/headline font:** `Newsreader` (Google Font, serif, variable weight 200-600, has italics) - used for the editorial/scientific feel in headings.
- **Body font:** `IBM Plex Sans` (Google Font, weights 300/400/500, italic 400) - this is the site's actual default font (`--default-font-family`).
- **Mono:** system stack (`ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace`) - utility only, not visually prominent.

Google Fonts import used on the source site:
```
https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,200..600;1,6..72,200..500&family=IBM+Plex+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap
```

Type scale (rem, Tailwind default scale): xs .75 / sm .875 / base 1 / lg 1.125 / xl 1.25 / 2xl 1.5 / 3xl 1.875 / 4xl 2.25 / 5xl 3 / 6xl 3.75 / 7xl 4.5.

## Shape & spacing

- **Border radius:** `--radius: .125rem` (2px) as the base - corners are nearly square, architectural, not the rounded-pill look. Larger elements (cards, big containers) step up to `.75rem` / `1rem`.
- **Overall layout feel** (from the rendered page): generous whitespace between sections, classic long-form landing page structure (hero → credibility/intro → content/curriculum → social proof → FAQ → final CTA), professional photography, restrained color use (mostly cream/ink, blue only for emphasis).

## Overall tone

Editorial and credible rather than flashy - closer to a science journal or a well-designed course page than a typical "AI SaaS" gradient-and-rounded-corners look. Sharp corners, warm neutral palette, blue used sparingly as the one accent color, serif display type paired with a clean sans body.
