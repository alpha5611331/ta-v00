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
- **AI:** OpenAI API (`gpt-4o-mini`) with automatic fallback simulation mode
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
Both `UploadController` (remediation) and `TanyaController` (Q&A) follow the same pattern:
1. Attempt real OpenAI API call using `OPENAI_API_KEY`
2. On failure or missing key, fall back to simulation mode (generates plausible dummy output)
3. Store `is_simulated` flag in DB so the UI can indicate simulated results

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
EDUBRAILLE_HOST=         # EduBraille device HTTP endpoint
EDUBRAILLE_PORT=         # EduBraille device port
```

## Key Conventions

- **Language:** All user-facing strings, Blade views, and error messages are in Indonesian.
- **Database:** SQLite file lives at `database/database.sqlite`. Do not commit it.
- **File storage:** Uploaded documents are stored in `storage/app/private/documents/` keyed by user ID.
- **No external queues/cache needed:** Everything uses the `database` driver.
