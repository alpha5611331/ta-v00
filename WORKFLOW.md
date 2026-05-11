# VOXORA — Alur Eksekusi Sistem

Dokumen ini menjelaskan alur kerja permukaan (surface flow) aplikasi VOXORA dari sisi pengguna maupun teknis, untuk keperluan penulisan tesis.

---

## Gambaran Besar

```
Pengguna Upload Dokumen
        ↓
  Ekstraksi Teks
        ↓
  Sanitasi Teks
        ↓
  Remediasi AI (OpenAI GPT)
        ↓
  Simpan ke Database
        ↓
┌───────────────────────────────────┐
│  Pilihan aksi lanjutan:           │
│  • Lihat di Pustaka               │
│  • Tanya Bot (Q&A)                │
│  • Ekspor ke DOCX                 │
│  • Kirim ke EduBraille            │
└───────────────────────────────────┘
```

---

## Alur Detail Per Fitur

### 1. Autentikasi

| Langkah       | Rute               | Keterangan                                                                        |
| ------------- | ------------------ | --------------------------------------------------------------------------------- |
| Buka aplikasi | `GET /`          | Jika sudah login → redirect ke `/upload`; jika belum → halaman selamat datang |
| Registrasi    | `POST /register` | Buat akun baru, langsung login, redirect ke `/upload`                           |
| Login         | `POST /login`    | Verifikasi kredensial; admin →`/admin`, user biasa → `/upload`              |
| Logout        | `POST /logout`   | Hapus sesi, redirect ke halaman utama                                             |

---

### 2. Upload & Remediasi Dokumen (Fitur Utama)

Rute: `POST /upload` → `UploadController@store`

```
[1] Validasi File
    • Tipe: PDF atau DOCX
    • Ukuran: maks 20 MB

[2] Simpan File Sementara
    • Disimpan di storage/app/private/uploads/{user_id}/

[3] Ekstraksi Teks
    ├── DOCX → Baca word/document.xml dari ZIP
    │          Persamaan OMML ditandai [PERSAMAAN: ...]
    │          Fallback: phpoffice/phpword
    └── PDF  → pdftotext (jika tersedia)
               Fallback: smalot/pdfparser
               Jika PDF mengandung persamaan sebagai gambar → lewati ke langkah Vision

[4] Sanitasi Teks
    • Hapus header/footer/nomor halaman
    • Normalkan whitespace dan baris kosong berulang

[5] Remediasi AI
    ├── DOCX: kirim segmen teks (4000 kar/segmen) ke GPT-4o-mini
    │         System prompt khusus STEM → konversi simbol/rumus ke narasi Indonesia
    │         Contoh: "x²" → "x kuadrat", "∫" → "integral dari"
    └── PDF:  render halaman ke PNG via Ghostscript
              Kirim gambar ke GPT-4o vision (maks 10 halaman)
              Fallback: pesan panduan jika Ghostscript/API tidak tersedia

    Jika tidak ada API key → Simulasi offline (regex sederhana)

[6] Simpan ke Database (tabel: documents)
    • raw_text      = teks asli hasil ekstraksi
    • remediated_text = hasil narasi AI
    • char_count, file_type, user_id, dst.

[7] Tampilkan Hasil
    • Halaman upload menampilkan teks teremediasi
    • Tombol: Ekspor DOCX | Kirim ke EduBraille | Lihat Pustaka
```

---

### 3. Pustaka Dokumen

Rute: `GET /pustaka` → `PustakaController@index`

```
Tampilkan daftar dokumen milik user yang sedang login
    ↓
Klik dokumen → GET /pustaka/{id} → tampilkan detail + teks teremediasi
    ↓
Dari halaman detail, tersedia:
    • Tombol "Tanya Bot" → /tanya/{id}
    • Tombol "Kirim ke Braille" → /braille?doc_id={id}
    • Tombol "Hapus" → DELETE /pustaka/{id}
```

---

### 4. Tanya Bot (Q&A)

Rute: `POST /tanya/ask` → `TanyaController@ask`

```
[1] User mengetik pertanyaan di form

[2] Request dikirim ke server:
    • question    = teks pertanyaan
    • doc_context = teks teremediasi dokumen (opsional, maks 50.000 kar)
    • document_id = ID dokumen (opsional)

[3] Bangun pesan ke AI:
    • System prompt: "Kamu asisten VOXORA untuk tunanetra..."
    • User message: "Konteks dokumen: [teks] \n\n Pertanyaan: [pertanyaan]"

[4] Kirim ke GPT-4o-mini (timeout 30 detik)
    Jika gagal / tidak ada API key → jawaban simulasi statis

[5] Simpan ke tabel document_questions (pertanyaan + jawaban + flag simulated)

[6] Kembalikan jawaban sebagai JSON → ditampilkan di halaman tanpa reload
```

---

### 5. Kirim ke EduBraille

Rute: `POST /braille/send` → `BrailleController@send`

```
[1] User memilih:
    • Teks yang akan dikirim
    • Ukuran chunk: 5 / 10 / 20 / 40 karakter
    • Perangkat EduBraille (dari daftar device aktif)

[2] Teks dibersihkan dan dipotong menjadi chunk

[3] Setiap chunk dikonversi ke Unicode Braille Grade 1
    Contoh: "halo" → ⠓⠁⠇⠕

[4] Kirim payload ke endpoint HTTP perangkat EduBraille
    • Jika berhasil → status "sent"
    • Jika gagal → status "failed" + pesan error

[5] Log pengiriman disimpan ke tabel braille_deliveries

[6] Halaman menampilkan pratinjau chunk braille + status pengiriman
```

---

### 6. Panel Admin

Rute: `/admin/*` — dilindungi middleware `app.admin`

| Halaman               | Fungsi                                                                     |
| --------------------- | -------------------------------------------------------------------------- |
| `/admin`            | Dashboard statistik (jumlah user, dokumen, pertanyaan, pengiriman braille) |
| `/admin/users`      | Daftar semua user, hapus user                                              |
| `/admin/docs`       | Daftar semua dokumen dari semua user                                       |
| `/admin/edubraille` | Kelola perangkat EduBraille (tambah, aktifkan, uji koneksi, kirim)         |

---

## Komponen Teknis Utama

| Komponen            | Teknologi                                   | Keterangan                                          |
| ------------------- | ------------------------------------------- | --------------------------------------------------- |
| Framework           | Laravel 13 (PHP 8.4)                        | Backend MVC                                         |
| Database            | SQLite (default)                            | Semua data: dokumen, pertanyaan, pengiriman braille |
| Frontend            | Blade + TailwindCSS 4.0                     | Server-side rendering, tanpa JS framework           |
| AI Remediasi        | OpenAI GPT-4o (vision) / GPT-4o-mini (teks) | Konversi simbol STEM ke narasi Indonesia            |
| PDF Processing      | Ghostscript (rasterisasi) + pdftotext       | Ekstraksi teks / render halaman                     |
| DOCX Processing     | ZipArchive + DOMXPath + phpoffice/phpword   | Ekstraksi teks & persamaan OMML                     |
| Braille Konversi    | Mapping karakter Unicode Braille Grade 1    | Built-in, tanpa library eksternal                   |
| Queue/Cache/Session | Database driver Laravel                     | Tidak memerlukan Redis/Memcached                    |

---

## Diagram Alur Data Singkat

```
PDF / DOCX
    │
    ▼
[Ekstraksi] ───────────────────────────────────────────────────────────┐
    │ teks mentah + [PERSAMAAN: ...]                                   │
    ▼                                                                  │ PDF dgn gambar
[Sanitasi] ──→ hapus noise (header, footer, baris kosong)              │
    │                                                                  │
    ▼                                                                  ▼
[GPT-4o-mini] ────────────────────────────────────────── [GPT-4o Vision per halaman]
    │ narasi teks STEM dalam Bahasa Indonesia
    ▼
[Database: documents.remediated_text]
    │
    ├──→ Pustaka: baca / hapus
    ├──→ Tanya Bot: jawab pertanyaan berdasarkan konteks dokumen
    ├──→ Ekspor DOCX: download file Word
    └──→ EduBraille: potong → konversi Braille → kirim HTTP ke perangkat
```

---

## Catatan untuk Tesis

- **Mode Simulasi:** Jika `OPENAI_API_KEY` tidak disetel, sistem tetap berjalan menggunakan regex sederhana sebagai fallback. Ini memungkinkan pengujian tanpa biaya API.
- **Isolasi Data:** Setiap query data difilter berdasarkan `user_id` — satu user tidak dapat melihat dokumen user lain.
- **Aksesibilitas:** Seluruh UI menggunakan palet warna yang memenuhi standar kontras WCAG 2.1 AA+.
- **Tanpa Layanan Eksternal:** Queue, cache, dan session semuanya menggunakan driver database Laravel — tidak diperlukan Redis, Memcached, atau layanan pihak ketiga lainnya selain OpenAI API.
