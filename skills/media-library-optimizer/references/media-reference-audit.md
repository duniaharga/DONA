# Media reference audit and safe changes

## Where to look
Inspect WordPress data through APIs or read-only queries, accounting for serialized and escaped values:
- Published, draft, private and scheduled posts/pages; reusable blocks, active widgets and any retained revisions/backups relevant to rollback.
- YOOtheme saved page JSON, template/library layouts, theme-mod configuration, Footer content/config, menu layouts, section/column backgrounds, galleries and image elements.
- Attachment parent relationships, featured images, WooCommerce product/variation images and galleries, all color-specific galleries, video slots, lower mixed-media galleries, poster references and ACF/custom post/term metadata.
- Site options, logos, icons, Open Graph images, menus, native media folder membership, translated content and theme/plugin settings relevant to media.
- Child-theme CSS/LESS/JS/PHP/JSON assets and hardcoded relative/absolute URLs. Check generated thumbnails, WebP/AVIF alternatives, scaled originals, crop variants, and references by attachment ID as well as URL.

Search normalized relative/absolute paths, escaped JSON URLs, URL-encoded names and derivative basenames. Substring hits require inspection; failed text search is not proof that a dynamic reference does not exist. If a plugin or external integration's references cannot be inspected, classify the candidate as uncertain. Treat archived layout/backups as retention evidence unless an authorized retention policy says otherwise.

## Metadata and filenames
- Attachment title: a readable library label, not a guarantee of rendered alt text or a changed file URL.
- Alt: determined by the image's purpose at each occurrence. Inspect native Builder overrides and rendered markup. A global attachment description may be unsuitable for an icon link or decorative duplicate.
- Caption: visible supporting copy only when it adds meaning; never insert it automatically below every photograph.
- Description: factual attachment documentation; avoid unverified materials, origin, dates, people or environmental claims.
- New filename example: `dona-luna-black-side-view.jpg`, if the actual image matches that product/color/view.

For a justified existing-file rename, inventory `_wp_attached_file`, `_wp_attachment_metadata` and any original/backup sizes. Preserve attachment IDs; update files, size records and verified references via serialization-aware WordPress APIs. Do not edit the attachment GUID as a URL-rewrite mechanism. Keep old public URLs working through a verified alias/redirect if applicable and authorized. Do not run a blind SQL replacement, change unrelated text, leave orphan derivatives, or remove old files before proving the replacement works. On an already deployed site, report any required redirect/storage integration that cannot be implemented safely.

## Cleanup classification
| Status | Action |
| --- | --- |
| Referenced by active, translated, dynamic or shared content | Retain |
| Required by retained backup, revision or original-size metadata | Retain or follow an explicitly authorized retention policy |
| No references after a complete audit and use can be ruled out | Eligible for authorized reversible cleanup, with manifest |
| Incomplete audit, unavailable plugin references, or ambiguous duplicate | Retain and report uncertainty |

A duplicate's bytes may match another file while its attachment ID or URL remains in use. Migrate and verify those references before considering the duplicate removable. Do not treat WordPress uploads as a temporary export folder. Remove task-owned temporary exports only when their purpose is finished and a retained source/deliverable exists.
