---
name: analyze
description: >
  Run and read content analysis on a Drupal site with the Analyze
  module's Drush commands: batch analysis with any installed analyzer
  (sentiment, brand voice, content marketing and security audits),
  broken link checks and reports, and PostHog or Search Console data
  for one page or the whole site. Use it when a task asks to analyze,
  score or audit site content, find broken links, or pull analytics or
  search data for URLs.
metadata:
  version: "1.0.0"
---

# Analyze: Content Analysis CLI

Centralized content analysis framework for Drupal. Run any
combination of analyzers across all content via Drush CLI.

## Preamble: Auto-discover Current State

Run these commands first to understand the site's configuration:

```bash
# List all batch-capable analyzers
drush analyze:batch --list

# Check current analyzer settings
drush config:get analyze.settings status
```

## Commands Reference

### Centralized Batch Processing

| Command | Description |
|---------|-------------|
| `analyze:batch` | Run batch analysis (all or selected analyzers) |
| `analyze:batch --list` | List available batch-capable analyzers |
| `analyze:batch --status` | Show analysis coverage and exit |
| `analyze:setup-ai` | Install AI skill files to project root |
| `analyze:setup-ai --check` | Check if skill files are up to date |

### Batch Command Options

| Option | Description |
|--------|-------------|
| `--analyzers=X,Y` | Comma-separated analyzer plugin IDs |
| `--types=node:article` | Filter by entity type:bundle |
| `--limit=100` | Maximum entities to process |
| `--force` | Re-analyze even if results exist |

### Module-Specific Commands (Broken Links)

| Command | Description |
|---------|-------------|
| `analyze:broken-links:check <type>/<id>` | Check single entity |
| `analyze:broken-links:report` | Sitewide broken link report |
| `analyze:broken-links:recheck` | Recheck stale URLs |

The entity is one argument, such as `node/42`. Filter the check with
`--status=broken|redirect|healthy|all`, `--scope=internal|external|both`
and `--format=table|json`.

### Module-Specific Commands (PostHog)

| Command | Description |
|---------|-------------|
| `analyze:posthog:status` | Connection status |
| `analyze:posthog:query <path>` | Query analytics for URL |
| `analyze:posthog:report` | Sitewide analytics report |
| `analyze:posthog:goals` | List conversion goals |
| `analyze:posthog:cache-clear` | Clear cached data |

### Module-Specific Commands (Search Console)

| Command | Description |
|---------|-------------|
| `analyze:search-console:status` | Connection status |
| `analyze:search-console:query <path>` | Query search data |
| `analyze:search-console:report` | Sitewide search report |
| `analyze:search-console:cache-clear` | Clear cached data |

## Workflow Examples

```bash
# Analyze all content with all available analyzers
drush analyze:batch

# Run sentiment and brand voice analysis on articles
drush analyze:batch \
  --analyzers=analyze_ai_sentiments_analyzer,analyze_ai_brand_voice_analyzer \
  --types=node:article

# Force re-analyze first 50 entities
drush analyze:batch --limit=50 --force

# Check a single entity for broken links
drush analyze:broken-links:check node/42

# Show only its broken external links
drush analyze:broken-links:check node/42 --status=broken --scope=external

# View sitewide broken link report
drush analyze:broken-links:report

# Query PostHog analytics for a page
drush analyze:posthog:query /about-us

# Query Search Console data for a page
drush analyze:search-console:query /blog/my-post \
  --days=90 --dimension=country
```

## Key Concepts

- **Analyzer plugin**: A module providing content analysis
  via the Analyze plugin system
- **BatchableAnalyzerInterface**: Opt-in interface for
  centralized batch processing
- **Entity bundle**: Content type (e.g., node:article)
  that analyzers can be enabled for
- **Plugin ID**: Machine name identifying an analyzer
  (e.g., analyze_ai_sentiments_analyzer)

## Notes

- Batch processing uses Drupal's Batch API in GUI and
  sequential processing in CLI
- Each analyzer handles its own result storage
- Analyzers are enabled per content type at
  /admin/config/content/analyze-settings
- The centralized batch form is at
  /admin/config/content/analyze-batch
