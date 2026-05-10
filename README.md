# VOXORA - Platform Remediasi Dokumen STEM

VOXORA adalah aplikasi Laravel untuk remediasi dokumen STEM agar lebih ramah screen reader dan dapat dikirim ke perangkat EduBraille.

## 📋 Tech Stack

- **Backend**: Laravel 13.7 dengan PHP 8.4
- **Database**: MySQL (dengan migration support)
- **Frontend**: Blade templates dengan TailwindCSS
- **Libraries**: PhpOffice, PDF parser, HTTP client
- **Build Tool**: Vite

## 🚀 Setup & Installation

### Prerequisites
- PHP 8.4+
- MySQL 8.0+ (atau XAMPP dengan MySQL)
- Composer
- Node.js & NPM
- Git

### 1. Clone Repository
```bash
git clone <repository-url>
cd ta-v00
```

### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 3. Environment Setup
```bash
# Copy environment file
copy .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Database Configuration
Edit `.env` file:
```env
# Database settings
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307  # Sesuaikan dengan port MySQL Anda
DB_DATABASE=ta_voxora
DB_USERNAME=root
DB_PASSWORD=

# Application URL
APP_URL=http://127.0.0.1:8000

# Timezone
APP_TIMEZONE=Asia/Jakarta
```

### 5. Create Database
```bash
# Via MySQL CLI
mysql -u root -p -e "CREATE DATABASE ta_voxora;"

# Atau via XAMPP
# Buka phpMyAdmin dan buat database "ta_voxora"
```

### 6. Run Migrations with Seed
```bash
php artisan migrate:fresh --seed
```

### 7. Start Development Server
```bash
# Start Laravel server
php artisan serve --host=127.0.0.1 --port=8000

# Start Vite (separate terminal)
npm run dev
```

### 8. Access Application
Buka browser: `http://127.0.0.1:8000`

**Default Accounts:**
- **Admin**: `admin@voxora.local` / `admin123`
- **User**: `user@voxora.local` / `user123`

---

## 🔧 Configuration

### Database Migration
Jika perlu refresh database:
```bash
php artisan migrate:fresh --seed
```

### Clear Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Storage Permissions
```bash
php artisan storage:link
```

---

## 📁 Project Structure

```
ta-v00/
├── app/
│   ├── Http/Controllers/     # All controllers
│   ├── Models/              # Eloquent models
│   └── Middleware/          # Custom middleware
├── database/
│   ├── migrations/          # Database migrations
│   └── seeders/            # Database seeders
├── resources/
│   ├── views/              # Blade templates
│   └── js/                 # Frontend JavaScript
├── routes/
│   ├── web.php            # Web routes
│   └── api.php            # API routes
├── storage/
│   ├── app/               # Application files
│   └── logs/              # Log files
└── public/                # Public assets
```

---

## 🎯 Panduan Penggunaan

### Akun Default
- **Admin**: `admin@voxora.local` / `admin123`
- **User**: `user@voxora.local` / `user123`

---

## 📋 Menu Utama

### 1. **Upload Dokumen** (`/upload`)
**Fungsi**: Upload dan remediasi dokumen STEM

**Cara Penggunaan**:
1. Klik tombol "Pilih File" atau drag & drop
2. Pilih file PDF atau DOCX (maks. 10MB)
3. Klik "Proses Remediasi"
4. Tunggu proses ekstraksi dan remediasi
5. Hasil akan tampil di bawah form

**Fitur Tersedia**:
- ✅ Ekstrak teks otomatis dari PDF/DOCX
- ✅ Remediasi simbol matematika ke teks natural
- ✅ Konversi nomor soal ke "Soal nomor X."
- ✅ Export ke Word dengan format aksesibel

**Export ke Word**:
- Nama file: `Remediasi Dokumen [judul].docx`
- Judul dokumen: Heading 1 untuk screen reader
- Konten: Normal text dengan line breaks terjaga

### 2. **Pustaka** (`/pustaka`)
**Fungsi**: Kelola semua dokumen yang sudah diupload

**Cara Penggunaan**:
- Lihat daftar dokumen dengan timestamp WIB
- Klik "Lihat Detail" untuk membaca hasil remediasi
- Klik "Export Word" untuk download versi Word
- Klik "Tanya" untuk bertanya tentang dokumen
- Klik "Hapus" untuk menghapus dokumen

**Informasi Dokumen**:
- Nama file asli
- Jumlah karakter
- Status pengiriman Braille
- Waktu upload (WIB)

### 3. **Tanya Dokumen** (`/tanya`)
**Fungsi**: Tanya jawab dengan AI tentang konteks dokumen

**Cara Penggunaan**:
1. **Tanya Umum**: Akses langsung dari menu Tanya
2. **Tanya Konteks**: Klik "Tanya" dari detail dokumen di Pustaka

**Fitur Interaksi**:
- 🎤 **Speech-to-Text**: Tekan tombol mikrofon untuk bicara
- 🔊 **Text-to-Speech**: Jawaban otomatis dibacakan
- ⌨️ **Keyboard**: Ketik pertanyaan langsung
- 💬 **Chat Interface**: Riwayat percakapan tersimpan

**Shortcut Keyboard**:
- `Enter`: Kirim pertanyaan
- `Shift+Enter`: Baris baru
- `Tab`: Navigasi antar elemen

### 4. **Braille** (`/braille`)
**Fungsi**: Kirim teks ke perangkat EduBraille

**Cara Penggunaan**:
1. Pilih dari upload: "Kirim ke Braille" setelah remediasi
2. Akses langsung dari menu Braille
3. Pilih ukuran chunk (5, 10, 20, 40 karakter)
4. Pilih perangkat EduBraille
5. Klik "Kirim ke EduBraille"

**Status Pengiriman**:
- ✅ **Sent**: Berhasil terkirim
- ❌ **Failed**: Gagal dengan error message
- ⏳ **Pending**: Sedang diproses

---

## 👤 Profil Pengguna

### Update Profil (`/profile`)
- Ubah nama lengkap
- Ubah email
- Ganti password

### Admin Panel (`/admin`)
**Hanya untuk user dengan `is_admin = true`**:

**Kelola User**:
- Lihat semua user terdaftar
- Hapus user (kecuali admin)
- Status user (aktif/non-aktif)

**Kelola Dokumen**:
- Lihat semua dokumen di sistem
- Monitor upload activity
- Export data dokumen

**Kelola EduBraille**:
- Tambah perangkat EduBraille
- Test koneksi perangkat
- Set device aktif
- Monitor pengiriman

---

## 🎯 Tips Penggunaan

### Untuk Tunanetra (Screen Reader)
1. **Navigasi**: Gunakan `Tab` untuk berpindah antar elemen
2. **Reading**: NVDA akan membaca konten otomatis
3. **Form**: Semua form memiliki label yang jelas
4. **Status**: Pesan sukses/error diumumkan sebagai ARIA live regions

### Upload Dokumen Tips
- Format yang didukung: PDF, DOCX
- Ukuran maksimal: 10MB
- Hasil terbaik untuk dokumen STEM (matematika, sains)
- Simbol matematika akan dikonversi ke teks natural

### Tanya Jawab Tips
- Gunakan bahasa Indonesia
- Pertanyaan spesifik lebih baik
- Context dokumen membantu jawaban lebih akurat
- Speech-to-work best dengan microphone yang jelas

### Braille Tips
- Chunk size kecil (5-10) untuk teks kompleks
- Chunk size besar (20-40) untuk teks sederhana
- Pastikan perangkat EduBraille terhubung
- Monitor status pengiriman di log

---

## 🛠️ Troubleshooting

### Upload Gagal
- Cek format file (PDF/DOCX only)
- Cek ukuran file (< 10MB)
- Refresh halaman dan coba lagi

### Tanya Jawab Tidak Responsif
- Cek koneksi internet
- Refresh halaman
- Coba dengan pertanyaan sederhana

### Braille Gagal Terkirim
- Pastikan perangkat EduBraille online
- Cek device configuration di admin panel
- Test koneksi dengan tombol "Test Connection"

### Login Gagal
- Cek email dan password
- Pastikan akun sudah terdaftar
- Hubungi admin untuk reset password

---

## 📞 Bantuan

### Kontak Admin
- Email: `admin@voxora.local`
- Akses: Menu Admin > Users

### Fitur Aksesibilitas
- **Screen Reader**: NVDA, JAWS, TalkBack compatible
- **Keyboard Navigation**: Full keyboard access
- **ARIA Labels**: Semantic HTML untuk assistive tech
- **Focus Management**: Logical tab order
- **Live Regions**: Real-time status updates

### Browser Support
- Chrome (Recommended)
- Firefox
- Edge
- Safari (limited features)

---

## 🎉 Selamat Menggunakan!

VOXORA dirancang khusus untuk membantu tunanetra mengakses dan memahami dokumen STEM dengan mudah dan nyaman.

**Created with ❤️ for accessibility**
