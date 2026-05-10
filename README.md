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


## PHP Extensions

Ekstensi PHP berikut harus diaktifkan sebelum menjalankan aplikasi. Pada XAMPP, uncomment baris yang sesuai di `php.ini`:

| Ekstensi | Kegunaan | Baris di php.ini |
|---|---|---|
| `zip` | Membaca/menulis file DOCX (format ZIP) | `extension=zip` |
| `gd` | Pemrosesan gambar oleh PHPWord | `extension=gd` |
| `fileinfo` | Deteksi tipe MIME file upload | `extension=fileinfo` |
| `mbstring` | Operasi string multibyte (UTF-8) | `extension=mbstring` |
| `xml` | Parsing XML dalam DOCX dan PDF | `extension=xml` |
| `dom` | DOM parser untuk struktur dokumen | `extension=dom` |

Ekstensi `fileinfo`, `mbstring`, `xml`, dan `dom` biasanya sudah aktif secara default. Pastikan `zip` dan `gd` diaktifkan secara eksplisit.

Setelah mengubah `php.ini`, restart server PHP/XAMPP agar perubahan berlaku.

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
