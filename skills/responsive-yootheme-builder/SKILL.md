---
name: responsive-yootheme-builder
description: Build or revise responsive, editable YOOtheme sections and pages from selected Figma frames in a WordPress Studio project, using a Figma connector or the Figma desktop app. Use for native YOOtheme layout JSON, element construction, mobile/tablet behavior and visual revisions; reuse an existing design system.
---

# Responsive YOOtheme Builder

## Figma connection and inspection
Discover available Figma connectors first; when the user tags the Figma desktop app or no suitable connector is available, use the authorized Computer Use interface to inspect the design in Figma. A missing MCP connector alone is not a reason to stop. Read [Figma-to-native workflow](references/figma-to-native-yootheme.md) for the relevant access route, asset export, and element mapping.

Inspect the requested frame's layers, layout properties, typography, colors, dimensions, and screenshots before building. Distinguish properties read from Figma from visual estimates and inferred responsive behavior. If both access routes are unavailable or permissions block access, report the specific blocker and request the missing access or an exported reference; never fabricate unseen node data.

## Workflow
1. Read `project-context/design-system.md` and `project-context/yootheme-version.md` if present. Inspect only the requested Figma nodes and related assets. If access fails, report the error and do not guess unseen content.
2. Identify only the native YOOtheme elements needed. Read the version-matched catalog and installed element definitions for their parent and child schemas, supported properties, values, responsive controls and rendering. Do not load the full theme archive unless the targeted schema is missing or contradicted.
3. Construct native section, row, column, grid, headline, text, image, icon, button, list or table elements as supported. Use fragments only where verified. Preserve editability; do not flatten complex layouts into HTML. Distinguish tile and card variants.
4. Reuse global LESS and existing styles. Add only necessary scoped CSS to the child theme, after checking rendered markup and selectors. For icon grids use original assets with stable deployment paths. Do not manually rewrite SVG or base64 bytes.
5. Apply measured desktop geometry and observed tablet/mobile settings; explicitly label inferred breakpoints and stacking. Check text wrapping, cropping, spacing, touch sizes and RTL when relevant. Keep global WordPress menus, logos and header in YOOtheme site chrome, not page JSON.
6. Validate JSON with `scripts/validate_json.py` for duplicate keys and structure. Validate element/property/child schema against the matching theme separately; JSON syntax validation alone is insufficient.
7. If WordPress Studio is running, import/render and compare at desktop, tablet and mobile. Otherwise report import/render as pending. Do not publish or alter a live site.
8. When a page is complete, run the project-local `media-library-optimizer`, then `seo-geo-optimizer` for measured Lighthouse and accessibility/SEO checks, then `a11y-compliance-auditor` for manual accessibility coverage, then `human-website-qa`. Reuse their reports and report blockers honestly. A small isolated section/style adjustment does not require a full page-completion audit unless the user requests one.

## Section naming and semantic markup
For every new YOOtheme section, set a clear, descriptive Builder name in `props.name` and explicitly set `props.html_element` to `section` so the rendered wrapper is a semantic `<section>`. Do not leave new sections unnamed or rely on the default wrapper tag. Verify both the saved Builder name and rendered HTML tag when checking the section. Preserve existing sections unless they are part of the requested change.

Set the section’s outer top and bottom spacing with its native YOOtheme section padding controls. Do not substitute row/column padding, spacer elements, or custom CSS for section padding when the native section controls support the required spacing. Keep element-specific internal spacing on the relevant element.

## Efficiency
One requested section = one targeted node inspection and targeted schema read. Reuse verified layout examples. Do not regenerate the child theme, audit sitewide SEO or test unrelated pages. Escalate only on missing schema, version change or failed validation.

## Deliverables
Editable JSON, unchanged original assets as needed, small LESS changes if needed, and a concise verification/unresolved-items note. Unknown links remain explicitly unresolved.
