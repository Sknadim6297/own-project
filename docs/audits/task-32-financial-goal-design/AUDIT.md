# Task 32 — Financial Goal design audit

## Architecture

| Surface | Stylesheet / template | Notes |
|---|---|---|
| Form | `goal-form.blade.php` + `yuktione-pfm-goal.css` | Dedicated tool chrome (`.yk-goal`) |
| Results | `goal-results.blade.php` | Same stylesheet (`.yk-goalr`) |
| History / show | Shared `history.blade.php` / `show.blade.php` + `yuktione-pfm.css` | Already plum/PFM |
| Shared PFM tools | `yuktione-pfm.css` tokens `--yk-teal: #8a0f4d` | Approved reference |
| Brand | `yuktione-brand.css` | `--y1-plum`, `--site-bg` |

Task 31 compact header is present (`yk-goal--compact-header`, `goal-compact-header`).

## Confirmed inconsistencies (vs homepage / PFM)

1. **Page background** — paper `#f6f3ec` + green dot grid vs pearl `#FFF8FB` / site cream.
2. **Brand palette** — emerald `#14705f` / `#0f4f45` as primary vs plum `#8A0F4D`.
3. **Soft fills** — mint green `#e4f2ec` vs soft plum `#F8EAF1`.
4. **Primary buttons** — green→plum gradient / gold submit vs solid plum PFM buttons.
5. **Sidebar** — dark emerald/gold decorative panel vs white/soft-plum PFM cards.
6. **Shadows / focus** — green-tinted rings vs plum focus rings.
7. **Chart strokes** — emerald savings line vs plum brand.
8. **Inline SVGs** — many custom strokes; should prefer `x-yk-icon` for actions/info.
9. **Step track / actions footers** — warm paper `#fbfaf6` vs pearl/white.
10. **Body class** — `yk-goal-body` has no page-level pearl continuity rule.

## Preserve

- Compact header (Task 31)
- Wizard steps / fields / defaults / formulas
- No years slider (Task 33)
- No result restructure (Task 36)
