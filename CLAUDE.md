# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

A single-page static marketing site for **Runger (Runners Gerung)**, a running community in Gerung, Lombok Barat, Indonesia. Content is in Indonesian (`lang="id"`).

Despite living under `~/Web/Laravel/`, **this is not a Laravel project**. There is no build system, no `package.json`, no server. It's plain HTML + CSS + JS, opened directly in a browser or served as static files.

## Running locally

No build step. Either open `index.html` directly, or serve the directory:

```bash
python3 -m http.server 8000   # then visit http://localhost:8000
```

A local server is preferred when testing the Leaflet map / gallery, because the GPX files and image paths are fetched relative to the page.

## File layout

- `index.html` — the live site (current version). All CSS and JS are inline in this single file.
- `Runger v2.html`, `Runger v3.html` — older design iterations kept for reference. Don't edit unless asked; changes belong in `index.html`.
- `assets/` — production assets referenced by `index.html` (logo, hero photos, `route-data*.json`).
- `gallery/`, `uploads/` — image pools. `gallery/` is the latest WhatsApp-export batch; `uploads/` holds earlier photos and the source `.gpx` files.
- `Evening_Run.gpx`, `Night_Run.gpx` — original GPS recordings of the two weekly routes. The route polylines rendered on the map are **simplified versions of these tracks, hardcoded as JS arrays** inside `index.html` (see "Route map" below). The `.gpx` files are the source of truth if routes need to be re-derived.
- `_check-*.jpg` — design review screenshots, not used by the site.

## Architecture notes (things that span multiple parts of one file)

`index.html` is one ~1200-line file with several self-contained subsystems. When changing one, you usually don't need to touch the others.

### Page sections (in order)
Ticker → Nav → Hero (with countdown) → Night Run (route map) → About → Pengurus (committee) → Schedule → Gallery → Join → Footer → Lightbox modal.

Each section is anchored (`#night`, `#tentang`, `#pengurus`, `#jadwal`, `#galeri`, `#gabung`) and the nav links point to those IDs. Adding/renaming a section means updating the nav links in **two** places (top nav + footer nav) and the eyebrow numbering (`001`, `002`, …).

### Design system (CSS custom properties, top of `<style>`)
Two palettes coexist: the brand blue (`--runger-blue`, `--ink`, `--bone`, `--paper`) and a single accent (`--volt`, defined in `oklch`). Typography is fixed: Bebas Neue (display), Inter (body), JetBrains Mono (eyebrows/labels). Stick to these tokens — don't introduce new colors or fonts ad hoc.

### Countdown (hero card)
`nextFriday2000()` computes the next Friday 20:00 **WITA (UTC+8)** by shifting `Date` into WITA-space, then converting back to UTC. If you change the start time/day, edit that function — the displayed labels (`hero-next-date`, the `cd-*` cells) are driven from it.

### Route map (Night Run section)
- Built with **Leaflet 1.9.4 from CDN** (no npm).
- Two routes (`evening` = Minggu Ganjil, `night` = Minggu Genap) are stored as inline `[lat, lng]` arrays in the IIFE near the bottom of the file. These were derived from `Evening_Run.gpx` / `Night_Run.gpx` via Ramer-Douglas-Peucker simplification — if you need to update a route, re-simplify from the source GPX rather than hand-editing points.
- Tab buttons (`.route-tab[data-rg]`) switch routes; layer buttons (`.map-layer-btn[data-layer]`) switch between four tile providers (CARTO dark, OSM, Esri satellite, OpenTopoMap). Adding a layer = add an entry to `tileLayers` **and** a `<button data-layer="…">`.
- `TIKUM` constant (`-8.680761, 116.136849`, Kantor Bupati Lobar) is the start meeting point and also the Google Maps fallback link target.

### Gallery + lightbox
Gallery cells use CSS `background-image` (set in the `.cell.real.gN` rules), not `<img>`. The lightbox JS reads `getComputedStyle(...).backgroundImage` to extract the URL — so **adding a new gallery image requires adding both a CSS rule with the `background-image` and the corresponding `.cell.real.gN` markup**. Tag/caption come from inner `.tag` and `.cap` text.

Lightbox supports keyboard (← → Esc Space), touch swipe, and a 3.5s autoplay slideshow.

### Responsive
Single mobile breakpoint at `max-width: 900px` near the end of `<style>`. Most multi-column grids collapse to one column there. Test mobile when changing any `.…-grid` rule.

## Conventions

- All copy is Indonesian. Match the existing tone (casual, second-person, mixes "kita/bareng/tikum" community vocabulary). "Tikum" = titik kumpul (meeting point); "WITA" = the time zone label, always uppercase.
- Section eyebrows are numbered sequentially (`001`, `002`, …). Renumber if you reorder sections.
- External links to Instagram/Strava/WhatsApp/Google Maps use `target="_blank" rel="noopener"`. Keep that.
- Don't add a build tool, framework, or split this into multiple files unless explicitly asked — the single-file layout is intentional for this project.
