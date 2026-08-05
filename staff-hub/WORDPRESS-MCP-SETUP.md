# WordPress / WooCommerce MCP Setup

How to connect Claude Code to openwaterswimming.com so it can read and manage WordPress posts/pages and WooCommerce products/orders directly.

## What this gives Claude access to

**WordPress posts/pages:** `get-posts`, `get-post`, `create-post`, `update-post`, `delete-post`, plus categories, tags, comments, media, and SEO tooling (Rank Math, SEOPress, Yoast).

**WooCommerce:** two overlapping tool sets are exposed —
- Legacy set: `wc-get-products`, `wc-get-product`, `wc-update-product`, `wc-get-orders`, `wc-get-order`, `wc-update-order-status`, `wc-get-customers`
- Native set: `orders-query`, `order-add-note`, `order-update-status`, `products-query`, `product-create`, `product-update`, `product-delete`

This covers reading/updating catalog and orders. There is no dedicated "WooCommerce settings" ability — payment gateways, tax, and shipping config are not exposed and must still be managed in wp-admin.

## Requirements

- WordPress **6.9+** (needed for the built-in Abilities API)
- Two plugins (see below) — one from wordpress.org, one from GitHub
- Admin access to the WordPress site to generate an Application Password

## Plugins needed

### 1. Enable Abilities for MCP
Registers which WordPress/WooCommerce capabilities are exposed as abilities, and provides the admin UI to toggle them on/off.

- Source: [wordpress.org/plugins/enable-abilities-for-mcp](https://wordpress.org/plugins/enable-abilities-for-mcp/)
- Install normally via wp-admin → Plugins → Add New → search "Enable Abilities for MCP"

### 2. MCP Adapter
The companion plugin that actually registers the live `/wp-json/mcp/...` REST routes and turns the enabled abilities into a real MCP server. **Not on wordpress.org** — it's distributed via GitHub releases by the WordPress core team.

- Source: [github.com/WordPress/mcp-adapter/releases](https://github.com/WordPress/mcp-adapter/releases)
- Install manually: download the latest release ZIP, then wp-admin → Plugins → Add New → Upload Plugin, upload the ZIP, activate

Both plugins must be active. Without MCP Adapter, the `mcp` REST namespace never registers and every request to the endpoint 404s with `rest_no_route`, even with correct credentials.

## Setup steps

1. **Install and activate both plugins** above, in either order.
2. In wp-admin, go to **Settings → WP Abilities**. Toggle on the abilities you want exposed (posts/pages, WooCommerce products, WooCommerce orders, etc.).
3. Go to the **Connection** tab on the same settings page.
4. Use the in-browser credential generator to create an **Application Password** — this is generated locally in your browser and never sent to the server in plaintext. Copy the Base64 credentials shown.
5. Copy the **MCP Endpoint URL** shown on that page. It follows the pattern:
   ```
   https://www.openwaterswimming.com/wp-json/mcp/mcp-adapter-default-server
   ```
6. The page also generates a ready-to-copy config block for `claude_desktop_config.json` — this is for **Claude Desktop**, not Claude Code. For Claude Code, use the CLI method below instead.

## Connecting to Claude Code

Claude Code supports remote HTTP MCP servers natively — no need for the `npx mcp-remote` wrapper that the Claude Desktop config uses.

```bash
claude mcp add --transport http wowsa-wordpress \
  "https://www.openwaterswimming.com/wp-json/mcp/mcp-adapter-default-server" \
  --header "Authorization: Basic YOUR_BASE64_CREDENTIALS" \
  --scope local
```

**Use `--scope local`.** This stores the server config (including the credential) in `~/.claude.json` under your local user profile, not in a project file that could get committed to git. `--scope project` would write to a shared `.mcp.json` in the repo — don't use that here since it would put the Application Password in git history.

### Verify the connection

```bash
claude mcp list
```

Should show `wowsa-wordpress ... ✔ Connected`. If it shows a 404/`rest_no_route` error, the MCP Adapter plugin isn't active. If it shows 401, the credentials are wrong or the Application Password was revoked.

### New session required

After running `claude mcp add`, the new server's tools (`mcp__wowsa-wordpress__*`) won't appear in the *current* Claude Code session — this is a known behavior with newly added MCP connectors. **Start a new Claude Code session** and the tools will be available.

## Security notes

- The Application Password is a live credential with whatever permissions your WordPress user account has. Treat it like any other secret — don't paste it into shared docs, chats, or commit it to git.
- To revoke access, go to your WordPress user profile → **Application Passwords** and revoke the one generated for this connection.
- The Anthropic Console API key (from platform.claude.com/settings/keys) is unrelated to this setup — that key lets WordPress call *out* to Claude's API for its own AI features (under Settings → Connected Services in some plugin configs). It does not grant Claude access to WordPress data and should not be confused with the Application Password used here.
