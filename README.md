# Sistem Inventaris Barang

Sistem Inventaris Barang adalah aplikasi web berbasis PHP yang dirancang untuk mengelola inventaris barang secara efisien. Aplikasi ini menggunakan MySQL sebagai database dan Tailwind CSS untuk antarmuka pengguna yang modern dan responsif.

## Fitur Utama

- **Dashboard**: Tampilan ringkasan statistik inventaris, item stok rendah, dan aktivitas terbaru
- **Manajemen Barang**: Tambah, edit, hapus, dan lihat daftar barang
- **Manajemen Supplier**: Kelola data supplier yang menyediakan barang
- **Barang Masuk**: Pencatatan barang yang masuk ke inventaris
- **Barang Keluar**: Pencatatan barang yang keluar dari inventaris
- **Laporan**: Berbagai jenis laporan inventaris dengan filter tanggal
- **Autentikasi**: Sistem login/logout untuk keamanan

## Teknologi yang Digunakan

- **Backend**: PHP 7+
- **Database**: MySQL
- **Frontend**: HTML, CSS (Tailwind CSS), JavaScript
- **Library**: Anime.js untuk animasi, Font Awesome untuk ikon

## Persyaratan Sistem

- Web server (Apache/Nginx)
- PHP 7.0 atau lebih baru
- MySQL 5.7 atau lebih baru
- Browser modern dengan dukungan JavaScript

## Instalasi

1. **Clone atau Download Repository**
   ```
   git clone https://github.com/username/sistem-inventaris.git
   cd sistem-inventaris
   ```

2. **Setup Database**
   - Buat database baru di MySQL dengan nama `sistem_inventaris`
   - Import file SQL yang disediakan (jika ada) atau buat tabel sesuai struktur aplikasi

3. **Konfigurasi**
   - Edit file `config.php` untuk mengatur koneksi database
   - Pastikan path dan kredensial database sesuai dengan environment Anda

4. **Deploy ke Web Server**
   - Salin semua file ke direktori web server (misalnya `htdocs` di XAMPP)
   - Akses aplikasi melalui browser: `http://localhost/sistem-inventaris`

## Struktur Database

Aplikasi ini menggunakan beberapa tabel utama:
- `barang`: Menyimpan data barang
- `supplier`: Menyimpan data supplier
- `barang_masuk`: Transaksi barang masuk
- `barang_keluar`: Transaksi barang keluar
- `detail_barang_masuk`: Detail transaksi masuk
- `detail_barang_keluar`: Detail transaksi keluar
- `users`: Data pengguna untuk autentikasi

## Penjelasan Fungsi File

### File Utama
- `index.php`: Halaman dashboard utama dengan statistik dan aktivitas
- `login.php`: Halaman login untuk autentikasi pengguna
- `logout.php`: Proses logout dan redirect ke login
- `barang.php`: Manajemen data barang (CRUD operations)
- `supplier.php`: Manajemen data supplier (CRUD operations)
- `barang_masuk.php`: Pencatatan dan manajemen transaksi barang masuk
- `barang_keluar.php`: Pencatatan dan manajemen transaksi barang keluar
- `laporan.php`: Halaman laporan dengan berbagai filter dan jenis laporan

### File Pendukung
- `config.php`: Konfigurasi koneksi database
- `check_table.php`: Script untuk memeriksa dan membuat tabel database
- `test_query.php`: Script testing untuk query database

### Folder Includes
- `includes/header.php`: Header HTML, navigasi, dan session check
- `includes/footer.php`: Footer HTML dan script JavaScript umum

## Animasi dengan Anime.js

Aplikasi ini menggunakan Anime.js untuk memberikan animasi yang smooth pada semua halaman. Animasi yang diterapkan meliputi:
- Fade-in untuk elemen utama saat halaman dimuat
- Transisi smooth pada hover dan interaksi
- Animasi loading untuk operasi asynchronous

## Penggunaan

1. **Login**: Masuk menggunakan kredensial admin
2. **Dashboard**: Lihat ringkasan inventaris
3. **Kelola Barang**: Tambah/edit/hapus item inventaris
4. **Kelola Supplier**: Tambah/edit/hapus data supplier
5. **Transaksi**: Catat barang masuk dan keluar
6. **Laporan**: Generate dan export laporan inventaris

## Kontribusi

Untuk berkontribusi pada proyek ini:
1. Fork repository
2. Buat branch fitur baru
3. Commit perubahan
4. Push ke branch
5. Buat Pull Request

## Lisensi

Proyek ini menggunakan lisensi MIT. Lihat file LICENSE untuk detail lebih lanjut.

## Dukungan

Jika Anda mengalami masalah atau memiliki pertanyaan, silakan buat issue di repository GitHub atau hubungi tim development.
