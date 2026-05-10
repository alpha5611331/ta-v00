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
- Ghostscript (wajib untuk dokumen PDF)

## 👻 Ghostscript (Wajib untuk Dokumen PDF)

Ghostscript harus diinstal di sistem agar ekstraksi teks dari file PDF berfungsi. Tanpa Ghostscript, upload dokumen PDF akan gagal.

**Windows:**
1. Unduh installer dari [https://www.ghostscript.com/releases/](https://www.ghostscript.com/releases/) (pilih versi 64-bit).
2. Jalankan installer dan ikuti langkah instalasinya.
3. Pastikan direktori `bin` Ghostscript (misalnya `C:\Program Files\gs\gs10.xx.x\bin`) ditambahkan ke variabel lingkungan `PATH`.
4. Verifikasi instalasi:

```bash
gswin64c --version
```

**Linux/macOS:**
```bash
# Ubuntu/Debian
sudo apt-get install ghostscript

# macOS (Homebrew)
brew install ghostscript
```

## 🔌 PHP Extensions

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

## 🛠️ Instalasi

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
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307  # Sesuaikan dengan port MySQL Anda
DB_DATABASE=ta_voxora
DB_USERNAME=root
DB_PASSWORD=

APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta
```

### 5. Create Database
```bash
# Via MySQL CLI
mysql -u root -p -e "CREATE DATABASE ta_voxora;"

# Atau via XAMPP: buka phpMyAdmin dan buat database "ta_voxora"
```

### 6. Run Migrations with Seed
```bash
php artisan migrate:fresh --seed
```

### 7. Start Development Server
```bash
# Start Laravel server
php artisan serve --host=127.0.0.1 --port=8000

# Start Vite (terminal terpisah)
npm run dev
```

### 8. Access Application
Buka browser: `http://127.0.0.1:8000`

**Akun Default:**
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
