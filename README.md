# VOXORA

VOXORA adalah aplikasi Laravel untuk remediasi dokumen STEM agar lebih ramah screen reader dan dapat dikirim ke perangkat EduBraille. Aplikasi ini menyediakan alur upload dokumen, pustaka hasil remediasi, tanya jawab berbasis konteks dokumen, dan pengiriman Braille.

## Fitur

- Autentikasi pengguna: register, login, logout, dan profil.
- Upload dokumen PDF/DOCX dan penyimpanan metadata ke database.
- Pustaka dokumen per pengguna.
- Detail hasil remediasi dokumen.
- Tanya dokumen dengan riwayat pertanyaan dan jawaban.
- Pengiriman teks ke EduBraille dengan log pengiriman.

## Tech Stack

- Laravel 13
- PHP 8.4
- SQLite
- Vite
- PHPUnit


## Setup Lokal

1. Install dependency PHP:

```bash
composer install
```

2. Siapkan file environment:

```bash
copy .env.example .env
php artisan key:generate
```

3. Pastikan SQLite database tersedia:

```bash
type nul > database\database.sqlite
```

Jika file sudah ada, langkah ini bisa dilewati.

4. Jalankan migration:

```bash
php artisan migrate
```

5. Install dependency frontend:

```bash
npm install
```

6. Jalankan aplikasi:

```bash
php artisan serve
```

Untuk Vite:

```bash
npm run dev
```

## Database

Project menggunakan SQLite:

```env
DB_CONNECTION=sqlite
```

File database:

```text
database/database.sqlite
```
