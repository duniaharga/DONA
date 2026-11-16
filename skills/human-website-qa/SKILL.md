---
name: human-website-qa
description: Perform human-like browser QA of WordPress Studio YOOtheme websites and changed pages, including responsive interactions, accessibility smoke tests, visual comparison with Figma and prelaunch verification. Use after implementation or before transferring to production.
---

# Human Website QA

## Safety and environment
Prefer the locally running WordPress Studio site. Determine the local URL and whether browser automation (for example Playwright) is available. If inaccessible, report blocked tests rather than claiming success. Do not submit real purchases, send emails, delete data, publish or modify production without authorization. Use test data and safe form endpoints.

## Workflow
1. Identify changed pages and their expected behavior from Figma and the implementation notes. Check local site availability and WordPress/YOOtheme state.
2. Open pages in a real browser where possible. Exercise header and mobile menus, buttons, links, accordions, forms (with safe test data), keyboard focus and tab order, error states, back navigation and interactive elements.
3. Inspect representative desktop, tablet and mobile viewports. Check horizontal overflow, spacing, typography, card grids, image crops, hover/focus states, RTL if needed, and touch targets. Compare screenshots to Figma; label inferred mobile behavior.
4. Inspect browser console, failed requests, broken assets, mixed content, obvious accessibility issues, missing alt text and heading order. Where available, run automated accessibility and performance checks; distinguish automated findings from manual observation.
5. Report reproducible issues with page URL, viewport, steps, expected/actual, severity, screenshot path and status. Fix only within approved scope and rerun failed tests.
6. Before migration, confirm local readiness. After transfer, run a separate live smoke test for URLs, forms, SSL, redirects, assets, canonical URLs, robots/noindex, sitemap and analytics configuration. Do not treat local success as proof that production works.

## Efficiency
Test changed sections first. Run sitewide regression only for shared components, global style changes, major releases or explicit requests. Do not crawl every page after each minor edit.

## Output
A QA report with tested/untested cases, reproducible failures, evidence and release blockers. Never claim tests passed if no browser was available.
