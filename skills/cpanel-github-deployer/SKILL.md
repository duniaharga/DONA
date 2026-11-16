---
name: cpanel-github-deployer
description: Connect a WordPress project to GitHub and deploy approved changes to cPanel over SSH, with private backups, scoped file uploads, selective database migration and live verification. Use for first-time cPanel WordPress migration, SSH/database setup, repository setup, pushing changes or updating a live site; invocation alone does not authorize publishing unrelated work.
---

# GitHub and cPanel deployment

Use GitHub to version source and SSH to deliver a reviewed release. Distinguish connecting, pushing to GitHub, deploying files and migrating content. A request for one does not automatically authorize the others. A request to deploy identified changes authorizes the necessary backup, transfer, cache refresh and verification; do not repeatedly ask for the same authorization. Do not enable automatic deployment after every edit unless explicitly requested.

## Discover and ask only what is missing

Read available AGENTS.md, STUDIO.md, PROJECT-HANDOFF.md, existing deployment configuration, Git status/remotes and migration notes before changing anything. Preserve finished work and current editor content. For DONA, read [known environment](references/dona-environment.md); revalidate these defaults against the actual connection.

Use this ordered setup for a new destination or when the user explicitly asks to reconnect. Reuse validated details for an existing destination; do not repeatedly collect secrets. A request to edit this skill does not start a migration.

1. **SSH access first.** Ask for SSH hostname (not the cPanel URL), port, username, and local paths to the private key and matching public `.pub` key. Ask whether the public key is already authorized in cPanel → SSH Access. The private key stays on the user's computer; never ask for its contents or upload it. If the public key file is absent, derive the public key locally without printing private material. A key fingerprint is not a key file. Use an SSH agent for a passphrase; hand off passphrase entry. Verify the server host key against trusted cPanel/host evidence rather than disabling host-key checks. Confirm a read-only SSH connection before proceeding.
2. **Database second.** Ask for the database name, database username, database host (often localhost), and confirmation that the user has All Privileges on that database. Ask the user to enter the database password securely into the destination wp-config.php or a protected configuration file, or use an available secret-input facility. Do not request the password in chat, echo it, place it in command-line arguments/history, or store it in skill files/reports. If remote wp-config.php already holds valid credentials, use it in place without exposing its contents. Verify database connectivity and inspect whether tables already exist.
3. **Domain and destination third.** Ask for the full HTTPS URL, whether WordPress lives in the domain root or a subdirectory, and the full filesystem document root. Example: https://example.com/DONA/ maps to a verified server directory, not an assumed public_html path. Confirm the local project/source URL and whether the user wants an initial transfer of wp-content plus database or a scoped update. A cPanel domain, SSH host, database host and filesystem path may all differ.
4. **Prepare and execute the requested transfer.** For first migration, follow [WordPress initial migration](references/initial-migration.md). Build the file manifest, database scope, URL replacement dry-run and rollback first. If the user already requested transfer to this identified empty destination, proceed within that scope without asking again. If a populated destination would be replaced, finish the reviewable plan and ask specifically about replacing its content; preserve live transactions. For later updates, use the scoped file/content procedures below.

Suggested question sequence: “What are the SSH host, port, username, private-key file path and public-key file path? Is the public key authorized?” → after connection succeeds, “What are the database name, user and host? Enter its password securely in the server configuration and confirm All Privileges.” → after connectivity succeeds, “What is the full HTTPS domain, any subdirectory, and the full server path? Should I migrate this project's wp-content and database?” Ask only missing items; do not bundle all three stages when the user requested staged setup.

GitHub is optional for a direct cPanel migration. Discover relevant GitHub connector tools only if repository work is requested; use authenticated gh/git when suitable. Missing GitHub setup must not block an authorized direct SSH transfer. Never create keys, expand access, or publish unrelated work implicitly. Stop dependent work when authentication fails and report the specific prerequisite instead of repeating unchanged credentials.

## GitHub source setup

If no repository exists, prepare a source-only repository with explicit allowlisted files and a suitable ignore file; ask for missing owner/repository/visibility before creating it. Default recommendation is private. Do not push before checking staged paths for secrets and private data; do not print matches containing secrets. Preserve existing history and unrelated working changes, avoid force pushes and never stage the entire WordPress directory indiscriminately.

Version the custom child theme, intentional custom plugins and relevant non-sensitive source/config templates. Exclude wp-config.php, .env, SSH keys, database dumps, SQLite files, logs, private backups, sessions, exports, caches, node_modules and license/API secrets. Exclude premium parent/plugin bundles unless repository licensing and user scope permit them. Media deployment is separate from committing uploads. Do not commit personal host details or this project-specific reference to a public repository.

Record the exact commit or file checksums selected for release. Push to the authorized repository/branch. A GitHub push is not proof of a successful server deployment. Never create Actions secrets, webhooks, new SSH access or CI auto-deployment implicitly.

## Prepare a concrete release

Inspect local and remote versions and differences. Treat live content and source changes as potentially newer; do not overwrite conflicting server edits silently. Check server tools/PHP, document root, realpath/symlinks, target URL, current DB engine/prefix and available disk space. Keep credentials on the server. Identify rollback before mutation.

For files, build an explicit relative-path manifest of additions/changes and any intentionally removed files. Exclude local/Studio-only components and development artifacts. Include runtime assets used by changed code, including optimized image/video copies and fonts; check their URLs and sizes. Use a read-only transfer preview or rsync dry-run when supported. Never use broad --delete against a live WordPress tree. Preserve uploads, wp-config.php, server rules and unrelated plugins/files.

Store rollback copies of affected files outside the document root with restrictive permissions. Back up the database before any database change. Use a unique release directory; do not overwrite earlier backups. If authorization is missing for the concrete target/action or a discovered conflict expands scope, present the prepared manifest, impact and rollback, then ask only for that unresolved decision. Preparing a skill or plan does not authorize a live release.

## Deploy files and content appropriately

Upload only the approved manifest over SSH/SFTP/rsync, preferably staging first and atomically replacing individual files or a validated release. Use reversible moves for approved removals. Verify transfer checksums and file ownership/permissions; avoid making all files executable or world-writable. Avoid copying Studio db.php, SQLite integration, Studio loaders, local wp-config.php or local environment flags into MySQL hosting.

Database changes require a separate, explicit selection:
- CSS/PHP/JS changes normally need no database import.
- YOOtheme Builder pages, global layout/template options, taxonomy/media metadata and Woo fields may live in the DB. Export only requested records/settings through WordPress APIs, inspect dependencies/IDs/URLs, back up affected live values and apply selectively.
- Do not overwrite a live database with a local dump during routine updates. Preserve orders, customers, users, payment settings, sessions, stock and live editorial changes. Initial migration/full replacement requires explicit scope, fresh backup and checking for live data.
- Studio SQL exports must be MySQL-compatible. Handle serialized PHP and JSON-escaped URLs with WordPress-aware replacement; plain SQL text replacement can corrupt layouts.
- Confirm destination subdirectory, home/siteurl, table prefix, database loader and rewrite rules. Avoid double-prefixing /DONA/. Regenerate only relevant caches; do not flush unrelated site data or global object caches without need.

## Verify, report and recover

Lint changed code and run appropriate focused checks before transfer. After deployment check HTTPS status, canonical URL, homepage and changed pages, internal links, scripts/styles/media, console errors and mobile layout. Exercise affected cart/account/variation flows without orders or external submissions. Do not treat a homepage HTTP200 as proof that checkout, emails or payments work. Follow human-website-qa for substantial changes; run existing media/SEO skills when page scope warrants it rather than full audits on every CSS edit.

If deployment causes a confirmed regression, restore the affected backed-up files/settings where safe. Do not roll back an entire database over new transactions; stop and request a scoped recovery decision. Stop repeated failed transfer/authentication attempts after identifying a concrete blocker.

Save a non-secret deployment report with target, commit/checksums, manifest, database operations, private backup location, validations, rollback steps and unresolved items. Final reply distinguishes pushed, uploaded, migrated and verified, and supplies the live URL. Never claim full synchronization or launch readiness when only a subset was deployed.
