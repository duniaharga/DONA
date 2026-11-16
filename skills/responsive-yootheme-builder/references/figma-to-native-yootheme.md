# Figma to native YOOtheme implementation

## Access and evidence
Prefer an available Figma connector that exposes the selected node's design context and assets. If the user tags Figma desktop, follow that preference. If no suitable connector is available, inspect the design through Figma desktop using the authorized Computer Use tools rather than assuming the file cannot be read.

### Connector route
- Resolve the supplied file and node ID and retrieve design context plus a screenshot. Inspect only the requested frame and required descendants.
- Capture hierarchy, exact text, auto-layout direction, gaps, padding, sizing constraints, alignment, fills, strokes, gradients, typography, effects, radii, and exportable assets. Inspect responsive variants when provided.

### Figma desktop route
- Select Figma with the documented Computer Use entry point and confirm the visible file and selected node match the user's URL. Use the current layer tree to select the requested section or descendant; zoom to selection when useful.
- Read measurements from the Design properties panel: width/height, auto layout, gaps, padding, alignment, text styles, fills, and strokes. Inspect a screenshot for composition and image crop. Selecting a layer may first scroll it into view; check fresh state before retrying a selection.
- Record only properties actually observed. An accessibility layer tree and visible properties can supply useful node evidence, but are not equivalent to a complete MCP node export. Label unexposed values as unknown or estimated rather than presenting them as measured.
- Export required photo/image layers through Figma's Export controls into a writable project folder. Choose an appropriate web format and resolution; avoid exporting an entire composed section in place of editable content. Preserve the exported bytes when copying/importing them. Verify the exported files exist and correspond to the selected image layers. Do not extract private app caches or undocumented internal endpoints.
- If a native export dialog changes the active window, refresh its UI state before continuing. Use a visible folder selector or documented path-navigation controls. Report genuine permission failures; do not bypass them through another mechanism.

For either route, retain compact mapping notes with source file/node IDs and measured values. Label mobile stacking and breakpoints as inferred when no mobile frame is supplied. If only a screenshot is available, identify the result as screenshot-based reconstruction and do not claim exact layer measurements.

## Native implementation
- Identify the actual installed YOOtheme version. Use the user's exported catalog as reference data; installed theme element definitions and renderer are authoritative.
- Inspect parent and item/child schemas, accepted option values, width controls, and template markup for only the element types required.
- Map the design using the following starting points, then verify against the installed schema:

| Figma content | Native YOOtheme structure |
| --- | --- |
| Page or requested frame group | Layout containing named semantic sections |
| Full-width visual section | Section with native background, width, and vertical padding controls |
| Horizontal or alternating image/text group | Row containing separate responsive columns |
| Heading | Headline with the appropriate H1/H2/H3 tag |
| Paragraph | Text element |
| Photograph | Image element with descriptive alt text; use a column/section background only when the reference calls for decorative media |
| CTA | Button with button_item children |
| Repeated linked cards | Grid with grid_item children |
| Navigation/link group | List with list_item children, or an existing native menu |

- Keep each title, paragraph, image, and action separately editable. For alternating editorial sections, use a separate row with image and copy columns for each section; preserve desktop order and specify deliberate mobile stacking. Do not put an entire section into one HTML/text element or one raster image.
- Preserve individual editability. Keep global header/nav/logo in YOOtheme site configuration, not page content JSON.
- Reuse project-context/design-system.md and child-theme LESS. Do not create duplicate global values; flag novel styles for a targeted design-system update.
- Download connector assets or import desktop-exported assets without rewriting their bytes, and use stable paths. Avoid temporary Figma URLs and invented CTA destinations.
- Validate JSON syntax/duplicate keys, then theme-specific element/property/child structure. Use native option values exactly (for example, an empty string may mean Default; do not invent a `default` value). Import and render locally when available. Preserve unrelated saved Builder content; when storing JSON through WordPress APIs, account for their slash handling so CSS newlines and Unicode survive the round trip. Check actual rendered/computed styles, since parent-theme CSS loaded later can override equally specific selectors. Compare screenshots at desktop, tablet and mobile; record observed versus inferred behaviors.

## Handoff
Deliver layout JSON, required original assets, any narrowly scoped LESS changes, mapping notes, and a clear verification status. Never claim successful import without actually importing.
