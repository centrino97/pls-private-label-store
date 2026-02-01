# WordPress MCP Integration Guide

This guide explains how to use MCP (Model Context Protocol) tools to connect to WordPress files, database, and view HTML/SQL files for perfect verification.

## Available MCP Tools

### 1. Browser Extension MCP (Already Available)

The `cursor-browser-extension` MCP server is already configured and allows you to:
- Navigate WordPress admin and frontend
- Take screenshots of pages
- Inspect HTML/DOM structure
- Interact with forms and elements
- View rendered output

**Use Cases:**
- View WordPress admin pages
- Inspect plugin-generated HTML
- Test frontend displays
- Verify WooCommerce product pages
- Check Elementor-rendered content

### 2. WordPress MCP Adapter (Recommended Setup)

The official WordPress MCP Adapter exposes WordPress as an MCP server, allowing AI to:
- Access WordPress files (PHP, HTML, CSS, JS)
- Query database through WordPress APIs
- Execute WordPress functions
- Read posts, pages, media, options
- Access plugin/theme files

## Setup Options

### Option A: Browser Extension MCP (Quick Start - Already Available)

The browser extension MCP is already configured. You can use it immediately:

**Example Commands:**
- "Navigate to the WordPress admin and show me the PLS plugin dashboard"
- "Take a screenshot of the products page"
- "Inspect the HTML of a product page"
- "View the rendered output of the pack tiers shortcode"

### Option B: WordPress MCP Adapter (Full Integration)

For complete WordPress file and database access, set up the WordPress MCP Adapter:

#### Installation Steps

1. **Install WordPress Abilities API and MCP Adapter:**
   ```bash
   cd /path/to/wordpress/wp-content/plugins
   composer require wordpress/abilities-api wordpress/mcp-adapter
   ```

2. **Activate the MCP Adapter Plugin:**
   - Go to WordPress Admin → Plugins
   - Activate "MCP Adapter"

3. **Configure Cursor to Connect:**

   Add to your Cursor MCP settings (`.cursor/mcp.json` or Cursor settings):
   
   **For Local WordPress (STDIO):**
   ```json
   {
     "mcpServers": {
       "wordpress-local": {
         "command": "wp",
         "args": [
           "--path=/path/to/your/wordpress",
           "mcp-adapter",
           "serve",
           "--server=mcp-adapter-default-server",
           "--user=admin"
         ]
       }
     }
   }
   ```

   **For Remote WordPress (HTTP):**
   ```json
   {
     "mcpServers": {
       "wordpress-remote": {
         "command": "npx",
         "args": [
           "-y",
           "@automattic/mcp-wordpress-remote@latest"
         ],
         "env": {
           "WP_API_URL": "https://your-site.com/wp-json/mcp/mcp-adapter-default-server",
           "WP_API_USERNAME": "your-username",
           "WP_API_PASSWORD": "your-application-password"
         }
       }
     }
   }
   ```

#### What You Can Do With WordPress MCP Adapter

Once configured, you can ask the AI to:
- "Read the wp-config.php file"
- "Show me all PHP files in the PLS plugin"
- "Query the database for all base products"
- "Get the content of a specific WordPress post"
- "List all plugin files and their contents"
- "Read the database schema for pls_base_product table"
- "Show me the HTML output of a specific shortcode"

### Option C: Enhanced Database Scripts (Current Solution)

The database verification scripts we created work independently and can be enhanced:

**Current Capabilities:**
- Direct MySQL connection
- Table structure verification
- Data integrity checks
- WooCommerce sync verification
- Data inspection

**Enhancement Ideas:**
- Add HTML export of verification results
- Generate SQL dumps of plugin data
- Create visual reports
- Export data to JSON/CSV

## Recommended Approach: Hybrid Solution

For "perfection" in verification, use a combination:

### 1. Database Scripts (Backend Verification)
```bash
# Verify database structure and data
php scripts/db-verify.php
php scripts/db-sync-check.php
php scripts/db-inspect.php pls_base_product 10
```

### 2. Browser Extension MCP (Frontend Verification)
- Navigate to WordPress admin
- View plugin screens
- Inspect rendered HTML
- Test user interactions
- Verify WooCommerce integration

### 3. WordPress MCP Adapter (Complete Access)
- Read all PHP files
- Access WordPress APIs
- Query through WordPress functions
- Read configuration files
- Access theme/plugin files

## Practical Workflow

### Step 1: Database Verification
```bash
# Run database checks
php scripts/db-verify.php > verification-report.txt
php scripts/db-sync-check.php >> verification-report.txt
```

### Step 2: Frontend Verification (Using Browser MCP)
Ask AI: "Navigate to the WordPress admin, go to PLS → Products, and show me what's displayed"

### Step 3: File Verification (Using WordPress MCP)
Ask AI: "Read all PHP files in the PLS plugin and check for any issues"

### Step 4: Integration Verification
Ask AI: "Check if the plugin files match the database structure we verified"

## Example MCP Commands

### Using Browser Extension MCP

**View WordPress Admin:**
```
"Navigate to https://your-site.com/wp-admin and show me the PLS dashboard"
```

**Inspect Plugin Output:**
```
"Take a screenshot of the products page and show me the HTML structure"
```

**Test Frontend:**
```
"Navigate to a product page and show me how pack tiers are displayed"
```

### Using WordPress MCP Adapter (After Setup)

**Read Files:**
```
"Read the file includes/data/repo-base-product.php"
"Show me all SQL queries in the plugin"
"List all HTML templates"
```

**Query Database:**
```
"Get all base products from the database"
"Show me the structure of wp_pls_pack_tier table"
"Query for products without pack tiers"
```

**Access WordPress Data:**
```
"Get all WooCommerce products synced with PLS"
"Show me WordPress options related to PLS"
"List all plugin capabilities"
```

## Quick Reference

### Current Tools Available:
- ✅ Browser Extension MCP - Ready to use
- ✅ Database Scripts - Ready to use
- ⚙️ WordPress MCP Adapter - Requires setup

### Best Practices:

1. **Database First**: Always verify database structure first
2. **Frontend Second**: Use browser MCP to verify UI
3. **Files Third**: Use WordPress MCP to verify code
4. **Integration Last**: Verify everything works together

### Security Notes:

- WordPress MCP Adapter requires authentication
- Use application passwords for HTTP transport
- Limit MCP access to development/staging
- Never expose production credentials

## Troubleshooting

### Browser Extension Not Working
- Ensure Cursor browser extension is installed
- Check MCP server is enabled in Cursor settings
- Verify browser extension MCP is in the list

### WordPress MCP Adapter Not Connecting
- Verify WP-CLI is installed and working
- Check WordPress path is correct
- Ensure MCP Adapter plugin is activated
- Verify user has admin permissions

### Database Scripts Not Working
- Check db-config.php credentials
- Verify MySQL connection
- Ensure PHP PDO extension is enabled
- Check table prefix matches WordPress

## Next Steps

1. **Test Browser Extension MCP** (immediate):
   - Ask AI to navigate to your WordPress site
   - View admin pages
   - Inspect plugin output

2. **Set Up WordPress MCP Adapter** (recommended):
   - Install via Composer
   - Configure Cursor MCP settings
   - Test with simple queries

3. **Enhance Database Scripts** (optional):
   - Add HTML export
   - Create visual reports
   - Generate SQL dumps

---

**For immediate use**: Start with Browser Extension MCP and Database Scripts (both ready now!)
**For complete access**: Set up WordPress MCP Adapter for full file/database integration
