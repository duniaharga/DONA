# Page completion audit

## Lighthouse measurement
- Detect an installed Lighthouse CLI, existing project dependency, suitable browser tooling, or Chrome's Lighthouse panel. Use the interface actually available; do not assume a connector or silently install software.
- A local URL must be reachable from the process/browser running the audit. PageSpeed Insights cannot inspect a private localhost URL; do not expose the site publicly just to run an audit.
- Include Performance, Accessibility, Best Practices and SEO. Use the installed version's presets/options and retain raw JSON/HTML reports. Record command/settings and exclusions. Keep baseline and final runs comparable; distinguish authenticated admin-bar overhead from the public storefront.
- Run a baseline for mobile and desktop. Rerun after a relevant fix. If Performance fluctuates materially, obtain up to three comparable runs and report their range/median instead of choosing the best score. A changing server, network, browser or Lighthouse version can change results.
- Report LCP/CLS/TBT and other metrics present in the actual report; distinguish these lab metrics from field Core Web Vitals. A 100 Accessibility score does not replace keyboard, screen-reader-name, form error and focus testing.

## WordPress/YOOtheme implementation checks
- Verify parent-theme version and native schemas. Set document metadata through existing plugin settings and layout semantics through native Builder elements where possible.
- Audit both saved content and runtime variants: mobile/desktop headers, sticky clones, menus, product swatches, drawers, sliders and login dialogs. Ensure IDs remain unique and `aria-labelledby` targets exist.
- Link text should communicate purpose in context. Icon-only controls require a name; decorative icon images inside already named controls normally use empty alt. ARIA does not repair a noninteractive element that should have been a native button.
- Preserve robots rules for staging, accounts, cart and checkout as appropriate. Mark an intentional staging noindex audit result as environment-dependent; do not disable the check or remove noindex to manufacture a perfect SEO score.
- Measure server/TTFB separately from asset weight when useful. Prefer right-sized responsive images, explicit dimensions/aspect ratios, appropriate loading priority, restrained font weights, and removal of confirmed duplicate enqueues. Removing a media file from disk does not improve a page that never requested it.
- Recheck the requested page and affected shared interactions after an optimization. Keep cosmetic design changes separate from necessary audit fixes.

## Report fields
URL and page ID; environment/date; audit tool/version/settings; before/after score matrix; retained report paths; actual failed audit IDs; change/evidence; media rename/cleanup report; manual accessibility and commerce interaction checks; production-only items; unverified items.

## Primary references
Consult current official documentation when audit behavior or rules are uncertain:
- [Lighthouse scoring](https://developer.chrome.com/docs/lighthouse/performance/performance-scoring)
- [Lighthouse documentation](https://developer.chrome.com/docs/lighthouse/overview)
- [W3C image alternatives](https://www.w3.org/WAI/tutorials/images/)
- [W3C accessible names](https://www.w3.org/WAI/ARIA/apg/practices/names-and-descriptions/)
