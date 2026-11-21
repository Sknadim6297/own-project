# YuktiOne Home Surface Contract

Authority: rendered Home Page (`/`, `body.yk-home-bright`) + logo.

## Page atmosphere (required on all public pages)

| Token | HEX | Use |
|-------|-----|-----|
| `--site-bg` | `#f8f3f1` | Primary page cream |
| `--site-bg-deep` | `#f5f1eb` | Deeper cream / soft panels |
| `--site-bg-alt` | `#f7f2ec` | Alternate wash |
| `--site-bg-warm` | `#f2ede4` | Bottom wash |
| `--site-card-bg` | `#ffffff` | Cards |
| `--site-card-border` | `#e8e4de` | Card/borders |

Do **not** use pink/lavender full-page atmospheres (`#fff8fb`, `#fff1f6`, purple glows) for page backgrounds.

## Brand accents

| Token | HEX | Use |
|-------|-----|-----|
| `--site-primary` / `--y1-plum` | `#8a0f4d` | Logo + accents |
| `--site-primary-hover` | `#c3196b` | Hover |
| `--site-cta-grad` | `#8d1f52 → #6f143f → #5c1035` | Primary CTA (Home “Find an Expert”) |

## Implementation

- Tokens live in `public/frontend/assets/css/yuktione-brand.css` (loaded on all public + auth layouts).
- Public body (non-home, non-auth) uses `--site-bg-image`.
- Tool roots should use `background: transparent` so the shared body cream shows through.
- Soft plum chips (`--y1-plum-soft`) are for small badges only, not page fills.
