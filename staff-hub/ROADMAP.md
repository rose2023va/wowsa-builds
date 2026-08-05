# WOWSA Staff Hub - Roadmap

Living document. Update checkboxes and the "Decisions log" as things change instead of re-deciding from scratch each session. Read this + `DESIGN-SYSTEM.md` at the start of any new session on this project.

## What this project is

A private, staff-only setup covering two things:
1. **AI knowledge access** - Rose and Quinn can ask an AI questions about how WOWSA's systems/builds work, grounded in the GitHub docs, without Rose having to train anyone individually.
2. **A private admin hub site** - one page with links to all WOWSA tools (Jotform, Brilliant Directories, WordPress, WooCommerce, Airtable, Lovable, Kajabi, Brevo, Slack, GitHub, Notion, Google Workspace), a knowledgebase section that mirrors GitHub docs, a live view of the Notion to-do list, and a link out to the AI.

## Decisions log (don't re-litigate these unless something changed)

- **No Anthropic API billing.** Rejected per-token metered cost even with cost controls (Haiku, caching) - wants flat/predictable cost and won't compromise on Opus-level intelligence. Subscription-only.
- **No embedded custom chat widget.** That combination (embedded chat + zero per-token cost + full Opus) doesn't exist - confirmed and accepted this tradeoff.
- **Rose and Quinn keep separate personal Pro/Max accounts** (not Claude for Team). Claude Projects can't be shared across separate personal accounts, so each person creates their own individual Project. "Sharing" happens at the GitHub layer (both connect their own GitHub auth to the same repo) plus a canonical instructions doc Rose maintains and both paste in.
- **Customer service access deferred.** Prove out the setup with Rose + Quinn first. When revisited: a dedicated Pro seat per person, not shared logins (ToS risk).
- **Claude Code plugin: not building one yet.** Pure Q&A doesn't need a plugin - just Claude Code pointed at a well-documented repo does that already. A plugin only becomes worth it if/when specific repeatable *workflows* (not just Q&A) are identified as slash commands.
- **Hosting: Cloudflare Pages**, not GitHub Pages (Rose's preference). Free tier is enough (unlimited bandwidth/sites, 500 builds/month) - cost is $0 for this project's scale.
- **Privacy gate: Cloudflare Access**, free tier likely covers 2 users (verify exact limit at signup - wasn't confirmed live).
- **Design: matched to Quinn's Lovable landing pages.** Tokens captured in `DESIGN-SYSTEM.md` from freestyle-biomechanics.openwaterswimming.com - Newsreader + IBM Plex Sans, cream/ink/ocean-blue palette, near-square corners.
- **Stack: plain HTML/CSS/JS** for the hub site - no framework/build step needed for a links page + doc viewer. Revisit only if the knowledgebase section needs real docs features (search, nav) - see Phase 4.
- **Notion to-do list: displayed live, kept private.** Rejected the simple "Share to web" + iframe embed because that requires making the Notion page publicly (if unlisted) reachable, which breaks the privacy level of the rest of the gated site. Instead: a Cloudflare Pages Function (serverless, same free tier, no separate server) holds a private Notion integration token and fetches the to-do list server-side; the hub page's JS calls that same-origin function, never the Notion API directly. This is the one piece of the site that isn't purely static.

## Phase 0 - Foundations (mostly done)

- [x] Decide no-API / subscription-only architecture
- [x] Decide per-person Claude Projects (not shared/Team)
- [x] Decide hosting (Cloudflare Pages) and privacy gate (Cloudflare Access)
- [x] Capture design system from Quinn's Lovable page
- [ ] Confirm Cloudflare Access free tier user limit at signup

## Phase 1 - Knowledge layer (GitHub as source of truth)

- [ ] Audit docs that already exist across repos (e.g. `wowsa-builds/directory/knowledge-base/*`, `swimmable-distance` briefs) - see what's already there before writing new stuff
- [ ] Write the canonical "WOWSA AI instructions" doc - what the assistant should know, how it should answer, which repos/tools it should reference
- [ ] Decide where this instructions doc lives (this repo vs a dedicated docs repo)

## Phase 2 - Claude Projects setup (per person)

- [ ] Rose: create Claude Project, paste instructions doc, connect GitHub
- [ ] Quinn: same
- [ ] Test both with real staff questions, refine the instructions doc based on gaps
- [ ] Re-sync instructions doc across both Projects whenever it's updated (manual - no shared Project)

## Phase 3 - Hub site build

- [ ] Scaffold plain HTML/CSS/JS using `DESIGN-SYSTEM.md` tokens
- [ ] Tool links section (Jotform, Brilliant Directories, WordPress, WooCommerce, Airtable, Lovable, Kajabi, Brevo, Slack, GitHub, Notion, Google Workspace)
- [ ] Link out to each person's individual Claude Project
- [ ] Knowledgebase section - decide: single flat page rendering docs, or multi-page browser
- [ ] Notion to-do widget:
  - [ ] Create a Notion internal integration, get its private token
  - [ ] Share the specific to-do database/page with that integration (Notion requires this per-database, it's not automatic)
  - [ ] Store the token as a Cloudflare Pages environment variable (secret) - never commit it to the repo
  - [ ] Build a Cloudflare Pages Function (e.g. `functions/api/todos.js`) that calls the Notion API server-side and returns just the fields the page needs
  - [ ] Hub page JS fetches from that same-origin function and renders the list
- [ ] Local preview / review with Rose before deploying

## Phase 4 - Knowledgebase auto-mirror (may fold into Phase 3 or come after)

- [ ] Decide if flat markdown-to-HTML rendering is enough, or if a static site generator (e.g. MkDocs, Eleventy) is worth it for search/navigation
- [ ] If a generator is added: wire up the build step so it still auto-rebuilds from GitHub on push

## Phase 5 - Hosting & privacy

- [ ] Create/confirm Cloudflare account
- [ ] Connect the hub repo to Cloudflare Pages, verify auto-deploy on push
- [ ] Set up Cloudflare Access, restrict to Rose + Quinn's emails
- [ ] Optional: custom domain/subdomain under WOWSA's domain

## Phase 6 - Deferred / future (not started, revisit later)

- [ ] Customer service AI access (dedicated Pro seat when CS is onboarded)
- [ ] Claude Code plugin (only if concrete repeatable workflows get identified)
- [ ] Dark mode for the hub site (design tokens already captured if wanted)

## Maintained by
Rose | Last updated: 2026-08-04
