# DONA

Custom WordPress child theme for the DONA WooCommerce website, built on YOOtheme Pro.

## Included

- Child-theme PHP, JavaScript, CSS and LESS source.
- Custom product galleries, interactive eight-view bag previews, account/cart drawers, reviews, shipping timeline and media organization.
- Theme assets and saved YOOtheme Builder JSON exports.

## Project skills

The `skills/` directory contains all seven project-local skills, including YOOtheme building, deployment, SEO, accessibility, media optimization and browser QA. Private deployment environment details are replaced with a generic setup reference. The private `.agents/` working copies remain excluded.

## Requirements and setup

Install WordPress, WooCommerce, a licensed compatible YOOtheme Pro parent theme, Advanced Custom Fields and Rank Math separately. Currency and social-login integrations require their respective plugins and configuration. Install this child theme in `wp-content/themes/yootheme-my-theme` and activate it with the parent theme present. Compile the YOOtheme theme styles in the destination environment.

Saved JSON exports are editable layout references; the current pages, global YOOtheme configuration and product/media fields live in the WordPress database. Back them up and migrate them separately with WordPress-aware URL replacement. Uploaded product images and rotation sheets also require a separate media transfer. The M/L/XL viewer choices are illustrative visual previews, independent of purchasable variations.

## Deployment

This repository tracks source code, not a complete WordPress installation. Review changes before deploying scoped files to the destination. Use the project-local `cpanel-github-deployer` skill for backups, SSH deployment and selective content migration. Never replace a live database during a routine code update.

WordPress core, bundled plugins/parent theme, generated theme CSS, uploads, credentials, SSH keys, database exports, caches and private project/deployment reports are intentionally excluded. Keep production credentials in the server configuration. No automatic cPanel deployment is configured.
