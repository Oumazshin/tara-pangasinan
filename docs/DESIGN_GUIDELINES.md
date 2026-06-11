# Tara Pangasinan — Design Guidelines

A complete reference for colors, typography, spacing, components, and layout used across the Tara Pangasinan tourism website.

---

## 1. Color Palette

### Brand Colors
| Token | Hex | Usage |
|---|---|---|
| `--primary` | `#14b8a6` | Teal — general accent, icons, links |
| `--primary-dark` | `#0f766e` | Hover state for `--primary` buttons |
| Brand Green | `#1D9E75` | CTA buttons, section underlines, highlights |
| Brand Green Hover | `#168260` / `#14805e` | Hover for brand green buttons |

### Neutrals
| Token | Hex | Usage |
|---|---|---|
| `--dark` | `#1f2937` | Headings, dark text |
| `--dark-footer` / Navbar bg | `#2C2C2A` | Navbar (default), footer background |
| `--text` | `#374151` | Body text |
| `--text-light` | `#6b7280` | Secondary/muted text, captions |
| `--light` | `#f8fafc` | Page background, card backgrounds |
| `--white` | `#ffffff` | Cards, inputs, pure white surfaces |
| `--border` | `#e5e7eb` | Dividers, card borders, input borders |
| Warm Off-White | `#F1EFE8` | Discover section background |
| Card Placeholder | `#B4B2A9` | Image placeholder background |

### Category Badge Colors
| Category | Hex | CSS Token |
|---|---|---|
| Beach | `#0ea5e9` | `--cat-beach` |
| Nature | `#10b981` | `--cat-nature` |
| Historical | `#78716c` | `--cat-historical` |
| Festival | `#f59e0b` | `--cat-festival` |
| Food | `#ef4444` | `--cat-food` |

### Overlay Colors
| Usage | Value |
|---|---|
| Hero image overlay | `rgba(0, 0, 0, 0.4)` |
| Details hero gradient | `linear-gradient(180deg, rgba(44,44,42,0.80) 0%, rgba(44,44,42,0.90) 100%)` |
| Footer divider | `rgba(255,255,255,0.10)` |
| Social icon hover | `rgba(255,255,255,0.20)` |
| Outline border (light) | `rgba(44,44,42,0.20)` |
| Muted text overlay | `rgba(44,44,42,0.70)` |

---

## 2. Typography

### Font Families
| Family | Role |
|---|---|
| `'Outfit', sans-serif` | Primary body font (default) |
| `'Inter', sans-serif` | UI components — buttons, labels, cards, footer |
| `'Playfair Display', serif` | Display headings — featured titles, section titles |

### Type Scale
| Element | Font | Size | Weight | Line Height | Notes |
|---|---|---|---|---|---|
| Hero H1 | Outfit | `4rem` | `600` | — | Responsive: 2.5rem @ 992px, 2rem @ 768px |
| Featured Title | Playfair Display | `2.5rem` | `600` | `1.2` | Serif display |
| Category Section Title | Playfair Display | `2.8rem` | — | — | Left-aligned |
| Details Hero Title | Inter | `40px` | `500` | `1.2` | Center-aligned |
| Section Heading (`h2`) | Outfit | `1.5rem` | `600` | — | With border-bottom |
| Discover Card Name | Inter | `20px` | `500` | `1.4` | |
| Body text | Outfit | `1.05rem` | `400` | `1.6` | |
| Secondary text | Outfit/Inter | `0.95rem` | `400` | — | |
| Small text / caption | Inter | `14px` / `0.85rem` | `400` | `1.42` | letter-spacing: 0.041–0.055px |
| Tiny label / badge | Inter | `13px` | `400` | `1.5` | letter-spacing: 0.152px |
| Hero subtitle | Outfit | `0.8rem` | `700` | — | Uppercase, letter-spacing: 2px |
| Category label (uppercase) | Inter | `11px` | `500` | — | letter-spacing: 1.1px, uppercase |
| Button text | Inter | `16px` | `500` | `24px` | letter-spacing: ~0.03–0.05px |
| Nav links | Outfit | `0.95rem` | `500` | — | |
| Footer brand | Inter | `20px` | `500` | `1.4` | |

---

## 3. Spacing System

| Token / Usage | Value |
|---|---|
| Section padding (default) | `80px 32px` |
| Section padding (category/footer) | `60px 80px` |
| Section padding (mobile ≤768px) | `40px 16px` |
| Section padding (tablet ≤992px) | `60px 32px` |
| Card inner padding | `20px` (discover) / `16px` (category) / `1.5rem` (sidebar) |
| Grid gap (discover/category) | `24px` |
| Grid gap (featured) | `48px` |
| Component gap (buttons) | `16px` |
| Nav gap | `2rem` |
| Footer gap between sections | `32px` |
| Footer links gap | `16px 24px` |
| Hero buttons margin-top | `32px` |

---

## 4. Layout & Grid

### Container
```css
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 5%;
}
```

### Page Max Width
- Sections: `max-width: 1440px`, centered with `margin: 0 auto`
- Inner content wrapper: `max-width: 1280px` or `1216px`

### Grid Patterns
| Section | Layout |
|---|---|
| Discover cards | CSS Grid, `repeat(2, 1fr)` → 1 col @ 992px |
| Featured section | CSS Grid, `2-col (image + content)` → 1 col @ 992px |
| Category cards | Flexbox wrap, `calc(25% - 18px)` per card (4-up) → 2-up @ 992px → 1-up @ 768px |
| Explore cards | CSS Grid, `repeat(4, 1fr)` implied |
| Details layout | CSS Grid, `2fr 1fr` (content + sidebar) → 1 col @ 992px |
| Gallery | CSS Grid, `repeat(3, 1fr)` with `.large` spanning 2 cols and 2 rows |
| Review cards | CSS Grid, `1fr 1fr` → 1 col @ 992px |
| Activities | CSS Grid, `repeat(auto-fill, minmax(200px, 1fr))` |

---

## 5. Components

### Navbar
```
Height: 68px
Padding: 0 32px
Background (default): #2C2C2A
Background (scrolled): rgba(255,255,255,0.95) + backdrop-filter: blur(10px)
Position: fixed, top 0, full-width
Transition: all 0.3s ease
```
- Logo: white on dark → `--dark` on scroll
- Links: white on dark → `--text` on scroll, hover → `--primary`
- CTA button: white outline on dark → filled `--primary` on scroll

---

### Buttons

#### Primary (Filled Green)
```css
background: #1D9E75;
color: #fff;
border-radius: 99px;          /* pill shape */
padding: 12px 24px;
font: 500 16px/24px 'Inter';
transition: background 0.3s;
/* Hover: background: #168260 or #14805e */
```

#### Outline (White — on dark bg)
```css
border: 1px solid #FFF;
color: #FFF;
border-radius: 99px;
padding: 12px 32px;
font: 500 16px/24px 'Inter';
/* Hover: background: #FFF; color: #2C2C2A */
```

#### Outline (Dark — on light bg)
```css
border: 1px solid rgba(44,44,42,0.20);
color: #2C2C2A;
border-radius: 99px;
padding: 12px 32px;
font: 500 16px/24px 'Inter';
/* Hover: background: rgba(44,44,42,0.05) */
```

#### Sidebar/Generic Button
```css
/* Primary */
background: var(--primary);       /* #14b8a6 */
color: var(--white);
border-radius: 8px;
padding: 0.75rem;

/* Outline */
border: 1px solid var(--border);
border-radius: 8px;
padding: 0.75rem;
```

> **Rule:** Pill buttons (`border-radius: 99px`) are used for hero, section CTAs, and explore filters. Rounded-rect buttons (`border-radius: 8px`) are used inside cards and sidebars.

---

### Cards

#### Discover Card
```
Border-radius: 8px
Background: #FFF
Shadow: 0 1px 2px 0 rgba(0,0,0,0.05)
Hover: translateY(-5px) + shadow-lg
Image height: 160px
Info padding: 20px, gap: 8px
```

#### Category Card
```
Border-radius: 8px
Background: var(--light) #f8fafc
Image height: 140px
Info padding: 16px, gap: 3px
Hover: translateY(-5px) + shadow-lg
```

#### Sidebar Card
```
Border-radius: 12px
Background: var(--white)
Border: 1px solid var(--border)
Padding: 1.5rem
Shadow: shadow-sm
```

#### Review Card / Activity Card
```
Border-radius: 12px
Background: var(--white)
Border: 1px solid var(--border)
Padding: 1.5rem
```

---

### Section Title Underline
A consistent decorative underline used under section headings:
```css
position: absolute;
bottom: -8px;
width: 48px;        /* centered headings */
/* OR */
width: 60px;        /* left-aligned headings */
height: 4px–6px;
border-radius: 9999px;
background: #1D9E75;
```

---

### Category / Badge Pills
```css
color: white;
padding: 4px 12px;
border-radius: 50px;
font-size: 0.8rem;
font-weight: 500;
/* Background: category color from palette above */
```

---

### Category Filter Tabs
```css
/* Active state */
background: #1D9E75;
color: #FFF;
border-radius: 99px;

/* Inactive state */
border: 1px solid var(--border);
border-radius: 99px;
color: var(--text);
```

---

## 6. Elevation / Shadows

| Token | Value | Usage |
|---|---|---|
| `--shadow-sm` | `0 1px 2px 0 rgba(0,0,0,0.05)` | Cards at rest, sidebar cards |
| `--shadow` | `0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06)` | Navbar on scroll |
| `--shadow-lg` | `0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05)` | Card hover state |

---

## 7. Border Radius

| Value | Usage |
|---|---|
| `4px` | Small UI elements |
| `8px` | Discover cards, category cards, activity icon, gallery items |
| `12px` | Gallery large items, sidebar cards, review cards, activity cards, nearby images |
| `50px` | Badge pills, category filter tabs |
| `99px` | Hero/CTA pill buttons |
| `9999px` | Section title underline |
| `50%` | Avatars |

---

## 8. Animations & Transitions

### Keyframes
```css
/* Hero content entrance */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}
.hero-content { animation: fadeIn 0.8s ease-out; }

/* Scroll-down arrow */
@keyframes bounce {
  0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
  40%  { transform: translateY(-10px); }
  60%  { transform: translateY(-5px); }
}
```

### Transition Defaults
| Element | Transition |
|---|---|
| Buttons (bg/color) | `0.3s` ease |
| Card hover (lift) | `transform 0.3s, box-shadow 0.3s` |
| Navbar | `all 0.3s ease` |
| "Learn More" link gap | `gap 0.3s` |
| Mobile nav slide | `0.3s` |
| Social icon hover | `background 0.3s` |

---

## 9. Footer

```
Background: #2C2C2A
Padding: 60px 80px → 60px 32px @ 992px
Font family: Inter
```
| Element | Color |
|---|---|
| Brand name (green part) | `#1D9E75` |
| Brand name (white part) | `#FFFFFF` |
| Description | `#999` |
| Nav links | `#D1D5DB`, hover → `#FFF` |
| Copyright | `#9CA3AF` |
| Divider | `rgba(255,255,255,0.10)` |

---

## 10. Responsive Breakpoints

| Breakpoint | Width | Changes |
|---|---|---|
| Tablet | `≤ 992px` | Single-column grids, reduced padding (60px 32px), stacked layouts |
| Mobile | `≤ 768px` | Compact padding (40px 16px), hamburger menu, full-width cards/buttons |

---

## 11. Icon & Image Guidelines

- **Logo icon:** SVG leaf/plant motif, `24×24px` in footer, `~28px` in navbar
- **Location pin icon:** `#1D9E75` teal green
- **Star rating:** Yellow — `#f59e0b` (matches `--cat-festival`)
- **Arrow icons:** White on dark bg, dark on light bg
- **Social icons:** `32×32px` containers, white SVG icons in footer
- **Image placeholders:** `#B4B2A9` warm gray until images load
- **Hero bg:** Full-cover photo with `rgba(0,0,0,0.4)` overlay for text contrast
