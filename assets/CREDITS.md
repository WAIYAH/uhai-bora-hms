# Media credits and licences

Every image, illustration, font and logo shipped with Uhai Bora is listed here.
When you add or replace media, add a row **before** publishing it: source page
(not a CDN link), creator, licence, date retrieved, model-release status, alt
text and where it is used.

Rules for this project:

- Store media locally; never hotlink. The Content-Security-Policy only allows images from this site.
- Stock photos must not be presented as Uhai Bora staff, patients or premises. Caption them “Illustrative photo”.
- Do not use identifiable patients without documented, informed consent.
- Prefer the original illustrations for most pages; use photos sparingly.

## Photographs

| Files | Source | Creator | Licence | Added to project | Model release | Alt text | Used on |
|---|---|---|---|---|---|---|---|
| `photos/blood-pressure-check-{600,900,1200}.webp`, `-900.jpg` | [Pexels photo 7659569](https://www.pexels.com/photo/7659569/) — “A Woman Checking Blood Pressure of her Patient” | Thirdman | [Pexels License](https://www.pexels.com/license/) (free use, attribution optional; no implied endorsement) | 2026-10-23 | Not provided by Pexels — captioned as illustrative | “A health worker checking a patient’s blood pressure at a desk.” | About |
| `photos/hands-reassurance-{640,960,1400}.webp`, `-960.jpg` | Unsplash, image ID `photo-1584515933487-779824d29309` (originally recorded as `https://images.unsplash.com/photo-1584515933487-779824d29309`; photographer not recorded) | Not recorded | [Unsplash License](https://unsplash.com/license) | 2026-10-23 | No identifiable faces | “Two people holding hands in reassurance.” | Login |

Resized and converted to WebP/JPEG with ffmpeg for responsive delivery.

**Before launch:** confirm the Unsplash photographer from the image ID and add the
credit, or replace the photo. If the operator has its own photography with signed
releases, prefer it.

Removed in the October 2026 upgrade: `care-team.jpg` (Pexels 5327585) and
`clinic-care.jpg` (Pexels 5452201) — their alt text described scenes the photos do not show,
and the masked studio portraits did not suit the service. The two decorative MP4 loops were
also removed (no information, extra data use, and they played without a pause control).

## Illustrations and logos

| Files | Source | Licence |
|---|---|---|
| `illustrations/*.svg` | Original artwork made for Uhai Bora | Project-owned |
| `brand/uhai-bora.svg`, `brand/uhai-bora-light.svg`, `brand/uhai-bora-mark.svg` | Original Uhai Bora logo | Project-owned |

Illustrations are decorative in context and are used with empty `alt` text.

## Fonts

| Files | Source | Licence |
|---|---|---|
| `../css/fonts/inter/inter-latin-{400,500,600,700}.woff2` | [Inter 3.3](https://github.com/rsms/inter), subset to Latin characters with fontTools | SIL Open Font License 1.1 — see `css/fonts/inter/OFL.txt` |

Italic display words use the device’s Georgia (or another serif) font; no extra font file is downloaded.
