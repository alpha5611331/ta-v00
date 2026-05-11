# VOXORA — System Execution Workflow

This document describes the surface-level and technical workflow of the VOXORA application, intended for thesis documentation purposes.

---

## Overview

```
User Uploads Document
        ↓
  Text Extraction
        ↓
  Text Sanitization
        ↓
  AI Remediation (OpenAI GPT)
        ↓
  Save to Database
        ↓
┌───────────────────────────────────┐
│  Available follow-up actions:     │
│  • View in Library                │
│  • Ask the Bot (Q&A)              │
│  • Export to DOCX                 │
│  • Send to EduBraille Device      │
└───────────────────────────────────┘
```

---

## Detailed Flow Per Feature

### 1. Authentication

| Step           | Route              | Description                                                                    |
| -------------- | ------------------ | ------------------------------------------------------------------------------ |
| Open app       | `GET /`          | If logged in → redirect to `/upload`; otherwise → welcome page               |
| Register       | `POST /register` | Create new account, auto-login, redirect to `/upload`                        |
| Login          | `POST /login`    | Verify credentials; admin → `/admin`, regular user → `/upload`             |
| Logout         | `POST /logout`   | Destroy session, redirect to home page                                         |

---

### 2. Document Upload & Remediation (Core Feature)

Route: `POST /upload` → `UploadController@store`

```
[1] File Validation
    • Type: PDF or DOCX only
    • Size: maximum 20 MB

[2] Temporary File Storage
    • Saved to storage/app/private/uploads/{user_id}/

[3] Text Extraction
    ├── DOCX → Read word/document.xml from ZIP archive
    │          OMML equations tagged as [EQUATION: ...]
    │          Fallback: phpoffice/phpword
    └── PDF  → pdftotext (if available)
               Fallback: smalot/pdfparser
               If PDF contains equations as images → skip to Vision step

[4] Text Sanitization
    • Strip headers, footers, and page numbers
    • Normalize whitespace and collapse repeated blank lines

[5] AI Remediation
    ├── DOCX: send text segments (4,000 chars/segment) to GPT-4o-mini
    │         STEM-specific system prompt → convert symbols/formulas to
    │         Indonesian natural language narration
    │         Example: "x²" → "x squared", "∫" → "integral of"
    └── PDF:  render pages to PNG via Ghostscript
              Send images to GPT-4o vision (max 10 pages)
              Fallback: guidance message if Ghostscript/API unavailable

    If no API key is set → Offline simulation mode (simple regex substitution)

[6] Save to Database (table: documents)
    • raw_text        = original extracted text
    • remediated_text = AI-generated narration
    • char_count, file_type, user_id, is_simulated, etc.

[7] Display Result
    • Upload page shows the remediated text
    • Action buttons: Export DOCX | Send to EduBraille | View Library
```

---

### 3. Document Library

Route: `GET /pustaka` → `PustakaController@index`

```
Show list of documents belonging to the logged-in user
    ↓
Click document → GET /pustaka/{id} → show detail view + remediated text
    ↓
From the detail page, available actions:
    • "Ask Bot" button → /tanya/{id}
    • "Send to Braille" button → /braille?doc_id={id}
    • "Delete" button → DELETE /pustaka/{id}
```

---

### 4. Ask the Bot (Q&A)

Route: `POST /tanya/ask` → `TanyaController@ask`

```
[1] User types a question in the form

[2] Request sent to server:
    • question    = question text
    • doc_context = remediated document text (optional, max 50,000 chars)
    • document_id = document ID (optional)

[3] Build AI message:
    • System prompt: "You are the VOXORA assistant for visually impaired users..."
    • User message: "Document context: [text]\n\nQuestion: [question]"

[4] Send to GPT-4o-mini (30-second timeout)
    If failed / no API key → return static simulated answer

[5] Save to table document_questions (question + answer + simulated flag)

[6] Return answer as JSON → rendered on page without full reload
```

---

### 5. Send to EduBraille

Route: `POST /braille/send` → `BrailleController@send`

```
[1] User selects:
    • Text to send
    • Chunk size: 5 / 10 / 20 / 40 characters
    • Target EduBraille device (from active device list)

[2] Text is cleaned and split into chunks

[3] Each chunk is converted to Unicode Braille Grade 1
    Example: "halo" → ⠓⠁⠇⠕

[4] Payload sent to EduBraille device HTTP endpoint
    • Success → status "sent"
    • Failure → status "failed" + error message

[5] Delivery attempt logged to table braille_deliveries

[6] Page displays Braille chunk preview + delivery status
```

---

### 6. Admin Panel

Route: `/admin/*` — protected by `app.admin` middleware

| Page                  | Function                                                                     |
| --------------------- | ---------------------------------------------------------------------------- |
| `/admin`            | Statistics dashboard (user count, documents, questions, Braille deliveries)  |
| `/admin/users`      | List all users, delete user                                                   |
| `/admin/docs`       | List all documents from all users                                             |
| `/admin/edubraille` | Manage EduBraille devices (add, activate, test connection, send)              |

---

## Core Technical Components

| Component           | Technology                                   | Description                                             |
| ------------------- | -------------------------------------------- | ------------------------------------------------------- |
| Framework           | Laravel 13 (PHP 8.4)                         | Backend MVC                                             |
| Database            | SQLite (default)                             | All data: documents, questions, Braille deliveries      |
| Frontend            | Blade + TailwindCSS 4.0                      | Server-side rendering, no JS framework                  |
| AI Remediation      | OpenAI GPT-4o (vision) / GPT-4o-mini (text) | Converts STEM symbols to Indonesian natural language    |
| PDF Processing      | Ghostscript (rasterization) + pdftotext      | Text extraction / page rendering                        |
| DOCX Processing     | ZipArchive + DOMXPath + phpoffice/phpword    | Text & OMML equation extraction                         |
| Braille Conversion  | Unicode Braille Grade 1 character mapping    | Built-in, no external library required                  |
| Queue/Cache/Session | Laravel database driver                      | No Redis, Memcached, or external broker needed          |

---

## Data Flow Diagram

```
PDF / DOCX
    │
    ▼
[Extraction] ──────────────────────────────────────────────────────────┐
    │ raw text + [EQUATION: ...]                                        │
    ▼                                                                   │ PDF with image-equations
[Sanitization] ──→ strip noise (headers, footers, blank lines)          │
    │                                                                   │
    ▼                                                                   ▼
[GPT-4o-mini] ─────────────────────────────────────── [GPT-4o Vision per page]
    │ STEM text narration in Indonesian
    ▼
[Database: documents.remediated_text]
    │
    ├──→ Library: read / delete
    ├──→ Ask Bot: answer questions based on document context
    ├──→ Export DOCX: download as Word file
    └──→ EduBraille: chunk → convert to Braille → HTTP send to device
```

---

## Notes for Thesis

- **Simulation Mode:** When `OPENAI_API_KEY` is not set, the system continues to function using simple regex substitution as a fallback. This allows testing without incurring API costs.
- **Data Isolation:** All data queries are filtered by `user_id` — one user cannot access another user's documents.
- **Accessibility:** The entire UI uses a color palette that meets WCAG 2.1 AA+ contrast standards.
- **No External Services Required:** Queue, cache, and session all use Laravel's database driver — no Redis, Memcached, or third-party services beyond the OpenAI API are needed.
