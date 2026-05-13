# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**VOXORA** — A Laravel web application that remediates STEM documents (PDF/DOCX) to make them accessible for visually impaired users via screen readers and EduBraille display devices. User-facing text is in Indonesian (Bahasa Indonesia).

Core flow: Upload document → AI-powered text remediation (math/LaTeX → natural Indonesian) → Browse library → Ask questions via Q&A bot → Send chunks to EduBraille device.

## Commands

### Setup
```bash
composer setup       # Full setup: install deps, generate key, migrate, build frontend
```

### Development
```bash
composer dev         # Runs Laravel server + queue worker + log watcher + Vite concurrently
php artisan serve    # Laravel dev server only
npm run dev          # Vite frontend only
```

### Build & Quality
```bash
npm run build        # Production frontend build
composer test        # Run PHPUnit test suite
./vendor/bin/pint    # Lint/format PHP (Laravel Pint)
```

### Database
```bash
php artisan migrate          # Run migrations
php artisan migrate:fresh    # Drop and re-run all migrations
```

### Single test
```bash
php artisan test --filter TestClassName
./vendor/bin/phpunit tests/Feature/ExampleTest.php
```

## Architecture

### Stack
- **Backend:** Laravel 13, PHP 8.4
- **Database:** SQLite (default); MySQL supported via `DB_CONNECTION` in `.env`
- **Frontend:** Blade templates + TailwindCSS 4.0 via Vite (no JS framework)
- **AI:** OpenAI API (`gpt-5.4` vision, `gpt-5.4-mini` text) with automatic fallback simulation mode
- **Sessions/Cache/Queue:** All use database driver — no external services required

### Controllers & Responsibilities

| Controller | Route prefix | Role |
|---|---|---|
| `UploadController` | `/upload` | Document upload, extraction, AI remediation, DOCX export |
| `PustakaController` | `/pustaka` | Per-user document library, search, delete |
| `TanyaController` | `/tanya` | Context-aware Q&A using remediated text as AI context |
| `BrailleController` | `/braille` | Unicode Braille conversion, chunking, EduBraille delivery |
| `AdminController` | `/admin` | User/document management, statistics |
| `ProfileController` | `/profile` | User info and password updates |

### Models & Key Relationships
- `User` → hasMany `Document`, `DocumentQuestion`, `BrailleDelivery`
- `Document` → hasMany `DocumentQuestion`, `BrailleDelivery`
- `Document` stores both `raw_text` (extracted) and `remediated_text` (AI-processed)

### AI Integration Pattern
`TanyaController` (Q&A): single call to `gpt-5.4-mini`, falls back to simulated answer on failure.

`UploadController` (remediation) uses a **two-phase pipeline** per text segment:
1. **Phase 1 — Math Extraction** (`resolveEquations`): `gpt-5.4-mini` (temperature 0.1) translates all math symbols/formulas to Indonesian narration; returns clean text.
2. **Phase 2 — Document Narration** (`narrateSegment`): `gpt-5.4-mini` (temperature 0.2) converts the symbol-free text into a screen-reader-ready narration script.

PDF documents skip phases 1–2 and go directly to `gpt-5.4` vision (page images via Ghostscript).

On any failure or missing `OPENAI_API_KEY`, the system falls back to simulation mode (simple regex substitution). The `is_simulated` flag is stored in DB so the UI can indicate simulated results.

### Braille Delivery
`BrailleController` converts remediated text to Unicode Braille and sends chunks (configurable: 5/10/20/40 chars) to the EduBraille device either via HTTP API or Serial connection. Delivery attempts are logged in `braille_deliveries`.

### Authorization
- All user data queries filter by `Auth::id()` for per-user isolation
- `EnsureUserIsAdmin` middleware gates all `/admin/*` routes
- Standard Laravel `auth` middleware on all non-public routes

### Accessibility
The UI uses a custom color palette defined in `resources/css/app.css` with verified WCAG 2.1 AA+ contrast ratios. Do not change color values without re-verifying contrast.

## Environment Variables

Key variables beyond Laravel defaults (see `.env.example`):
```
OPENAI_API_KEY=          # Optional; app works without it via simulation fallback
OPENAI_MODEL_VISION=     # Default: gpt-5.4  (PDF vision narration)
OPENAI_MODEL_TEXT=       # Default: gpt-5.4-mini  (DOCX text remediation + Q&A)
EDUBRAILLE_ENDPOINT=     # EduBraille device HTTP endpoint
EDUBRAILLE_TOKEN=        # EduBraille auth token
EDUBRAILLE_DEVICE_ID=    # Target device ID (default: DEFAULT)
```

## Key Conventions

- **Language:** All user-facing strings, Blade views, and error messages are in Indonesian.
- **Database:** SQLite file lives at `database/database.sqlite`. Do not commit it.
- **File storage:** Uploaded documents are stored in `storage/app/private/documents/` keyed by user ID.
- **No external queues/cache needed:** Everything uses the `database` driver.
