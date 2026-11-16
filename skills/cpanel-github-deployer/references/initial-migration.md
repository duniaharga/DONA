# Initial WordPress migration to cPanel

Use only for an explicitly requested initial transfer or replacement, after the ordered SSH → database → domain setup in SKILL.md. An initial migration normally transfers wp-content and database; do not substitute a full database import for a later theme update.

## Prepare

- Read project instructions and inspect the existing source implementation. Verify local source URL, DB engine, table prefix, active theme/plugins, upload dependencies and any multisite setup. Multisite needs its own inspected migration plan.
- Verify destination realpath, HTTPS URL including exact subdirectory/case, PHP compatibility, free disk space and WordPress core. wp-content plus a database is not a complete runnable installation without core, wp-config.php and server rewrite configuration. Reuse compatible destination core or prepare official core with WP-CLI. Preserve unrelated files and server settings.
- Inspect destination tables and content before import. Back up affected destination files and database outside public_html in a unique private directory. An empty user-created database can receive the requested import. A populated site's replacement needs concrete authorization; never overwrite new orders/users during routine deployment.
- Prepare an explicit wp-content manifest including needed themes, plugins and uploads (including generated delivery assets). Exclude caches, logs, private exports, secrets, .git, node_modules, local SQLite databases, Studio-only loaders and SQLite db.php. Inspect other drop-ins rather than copying them blindly. Do not delete or replace unrelated live uploads/plugins. Observe premium asset deployment rights.
- Export the local database in a format compatible with destination MySQL/MariaDB. WordPress Studio uses SQLite: use an available verified MySQL-compatible export, not a copy of its SQLite file or a raw SQLite dump. Validate the export and stop if compatibility cannot be established.

## Transfer and configure

- Stage files over authenticated SSH/SFTP. Back up affected existing files, verify checksums and permissions, then promote scoped files. Keep private SSH keys local.
- Import the selected export with destination WordPress/MySQL tooling. Keep credentials in existing remote configuration or a protected temporary credential file; avoid password command-line flags, command echo and public SQL files. Never copy local wp-config.php over the destination configuration.
- Configure destination DB_NAME, DB_USER, DB_HOST and table prefix from the actual imported tables while preserving destination secrets/salts/server settings. Arrange secure password entry if needed. Remove local-only database loaders only by reversible move to private backup. Do not enable debug output on the public site.
- Run WordPress-aware URL replacement with a dry-run first, including serialized values. Replace the local installation base with the full target base, including its subdirectory exactly once. Verify home and siteurl. Inspect JSON-escaped Builder URLs, root-relative links, CSS media references and attachment URLs separately; do not run a blind SQL text substitution or indiscriminately rewrite every slash. Preserve GUIDs unless a documented migration need justifies otherwise.
- Regenerate relevant YOOtheme caches and permalink rules. Verify subdirectory RewriteBase/index.php routes and preserve surrounding server directives. Do not expose executable migration helpers or backups in the public root.

## Verify and hand over

- Verify homepage, shop, product, account, cart, checkout rendering, wp-admin login route, media and assets, canonical URLs and internal links at the target subdirectory. Test interactions without placing orders or sending external messages. Confirm MySQL is used and Studio-only paths are absent. Check representative mobile rendering and console/network failures.
- Report exactly what files/database content migrated, the verified target URL, backup locations, unresolved launch items and rollback approach. Do not claim payment, email, full SEO or Google sign-in readiness without testing those flows.
- Remove temporary credential files and staged public artifacts where applicable, keeping private recovery backups. Save non-secret connection identifiers only with appropriate project scope; never persist passwords or key contents in the skill or handoff.
