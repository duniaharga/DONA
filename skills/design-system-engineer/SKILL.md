---
name: design-system-engineer
description: Extract Figma design values and build or update a reusable YOOtheme Pro 5 WordPress child-theme design system. Use when starting a website, establishing LESS variables, or changing global styles; not for routine section construction.
---

# Design System Engineer

## Input and boundaries
Use the selected Figma frames, existing WordPress Studio project, current child theme, and verified YOOtheme version. Never assume the original developer's Downloads paths exist. Do not publish, activate, or transfer a theme without explicit approval.

## Workflow
1. Locate the project and check `project-context/design-system.md` and `project-context/yootheme-version.md`. If verified current design-system values already cover the request, reuse them; do not repeat extraction.
2. Inspect relevant Figma frames, child nodes, fills, strokes, effects, text styles, measurements and assets. Extract colors FIRST, with source node/property, resolved color and role. Then typography, widths, gutters, spacing, radii, borders, shadows, buttons and responsive observations. Separate measured values from inferred behavior.
3. Identify the installed YOOtheme Pro version. Verify actual LESS variable names against matching parent-theme source or a version-matched verified index. Never invent LESS variables or copy a commercial parent theme into output.
4. Preserve existing child-theme files. Write `theme-mysite/less/theme.mysite.less` and required WordPress/YOOtheme integration files only after confirming the version's loading mechanism. Use scoped child LESS for styles with no native variable mapping.
5. Verify LESS compilation and style loading if a runnable pipeline exists; otherwise mark these checks pending. Never claim a LESS file is loaded solely because it exists.
6. Write `project-context/design-system.md` with colors first, values, mappings, provenance, unmapped values, assumptions, and version. Record the observed responsive design rules.

## Efficiency
Inspect only the relevant frames and missing definitions. Store concise reusable values in project-context, not a full Figma dump. Update only changed variables on later requests. Never regenerate unchanged assets.

## Output
Child-theme changes, design-system mapping, validation evidence, and unresolved issues. No live-site actions.
