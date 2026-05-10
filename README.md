# VOXORA

VOXORA adalah aplikasi Laravel untuk remediasi dokumen STEM agar lebih ramah screen reader dan dapat dikirim ke perangkat EduBraille. Aplikasi ini menyediakan alur upload dokumen, pustaka hasil remediasi, tanya jawab berbasis konteks dokumen, dan pengiriman Braille.

## Fitur

- Autentikasi pengguna: register, login, logout, dan profil.
- Upload dokumen PDF/DOCX dan penyimpanan metadata ke database.
- Pustaka dokumen per pengguna.
- Detail hasil remediasi dokumen.
- Tanya dokumen dengan riwayat pertanyaan dan jawaban.
- Pengiriman teks ke EduBraille dengan log pengiriman.
- Dashboard admin untuk melihat pengguna dan dokumen.
- Proteksi admin menggunakan kolom `users.is_admin`.

## Tech Stack

- Laravel 13
- PHP 8.4
- SQLite
- Vite
- PHPUnit

Project ini dikembangkan dengan Laravel Herd PHP. PHP aktif yang dipakai:

```text
C:\Users\user\.config\herd-lite\bin\php.exe
```

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

Tabel utama aplikasi:

- `users`: akun pengguna, termasuk `is_admin` dan `is_active`.
- `documents`: metadata dokumen, teks mentah, hasil remediasi, dan status Braille.
- `braille_deliveries`: log pengiriman ke EduBraille.
- `document_questions`: riwayat tanya jawab dokumen.

Tabel bawaan Laravel:

- `sessions`
- `cache`
- `jobs`
- `failed_jobs`
- `password_reset_tokens`

## Admin

Halaman admin hanya bisa diakses user dengan:

```text
is_admin = true
```

Route admin:

```text
/admin
/admin/users
/admin/docs
```

Saat migration role/status dijalankan, user dengan email yang diawali `admin@` otomatis ditandai sebagai admin.

## Testing

Jalankan test:

```bash
php artisan test
```

Test yang sudah ada mencakup:

- halaman publik dapat dibuka
- user non-admin tidak bisa membuka admin
- user admin bisa membuka admin

## Status Implementasi

Sudah selesai:

- Pondasi database MVP.
- Relasi model utama.
- Penyimpanan dokumen ke database.
- Log pengiriman Braille.
- Riwayat tanya jawab.
- Proteksi route admin.

Masih dapat dilanjutkan:

- Ekstraksi teks PDF/DOCX asli.
- Integrasi AI API untuk remediasi penuh.
- Halaman histori Braille.
- Halaman histori Tanya.
- Pengelolaan perangkat EduBraille.

## Catatan

Saat ini beberapa bagian remediasi dan pengiriman masih memiliki mode simulasi/fallback agar aplikasi tetap bisa berjalan tanpa API eksternal atau perangkat fisik.
