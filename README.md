# ArtacomBillingSystem

<p align="center">
  <img src="public/images/jelantik.jpeg" width="200" alt="Jelantik Logo">
</p>

<p align="center">
  <a href="#"><img src="https://img.shields.io/badge/PHP-8.2.12-blue.svg" alt="PHP Version"></a>
  <a href="#"><img src="https://img.shields.io/badge/Laravel-11.43.2-red.svg" alt="Laravel Version"></a>
  <a href="#"><img src="https://img.shields.io/badge/Filament-3.2.142-purple.svg" alt="Filament Version"></a>
  <a href="#"><img src="https://img.shields.io/badge/Lisensi-MIT-green.svg" alt="Lisensi"></a>
</p>

## Tentang ArtacomBillingSystem

ArtacomBillingSystem adalah solusi manajemen penagihan dan pembayaran komprehensif yang dibangun di atas Laravel 11. Sistem ini terintegrasi dengan Xendit Payment Gateway dan RouterOS API untuk integrasi Mikrotik, menyediakan platform yang kokoh untuk mengelola langganan, pembayaran, dan akun pelanggan.

### Fitur Utama

- **Proses Pembayaran Tanpa Hambatan**: Integrasi dengan Xendit Payment Gateway
- **Integrasi Mikrotik**: Komunikasi langsung dengan RouterOS melalui API
- **Penagihan Otomatis**: Jadwalkan dan otomatisasi pembayaran dan faktur berulang
- **Dashboard Komprehensif**: Pantau pendapatan, data pelanggan, dan status pembayaran
- **Manajemen Pengguna**: Kontrol akses berbasis peran menggunakan Spatie Permissions
- **Impor/Ekspor Excel**: Manajemen data yang mudah dengan Maatwebsite/Excel
- **Panel Admin Modern**: Didukung oleh Filament 3
- **Pencatatan Aktivitas**: Lacak semua aktivitas dan perubahan sistem

## Persyaratan Sistem

### Persyaratan Server

- PHP >= 8.2.12
- Database MySQL
- Composer >= 2.8.4
- Web Server (Nginx atau Apache)
- Minimal RAM 4GB (direkomendasikan 8GB)
- Minimal 2 core CPU
- Minimal 20GB penyimpanan SSD

### Ekstensi PHP

- PDO MySQL
- OpenSSL
- Mbstring
- Tokenizer
- XML
- Ctype
- JSON
- BCMath
- Fileinfo
- ZIP
- GD/Imagick

## Instalasi

```bash
# Clone repositori
git clone https://github.com/username-anda/artacom-billing-system.git

# Pindah ke direktori
cd artacom-billing-system

# Instal dependensi
composer install

# Buat file lingkungan
cp .env.example .env

# Generate kunci aplikasi
php artisan key:generate

# Konfigurasi database Anda di file .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=artacom_billing
# DB_USERNAME=root
# DB_PASSWORD=

# Konfigurasi kunci API Xendit di file .env
# XENDIT_SECRET_KEY=xendit_secret_key_anda
# XENDIT_PUBLIC_KEY=xendit_public_key_anda

# Konfigurasi koneksi RouterOS di file .env
# ROUTEROS_HOST=ip_mikrotik_anda
# ROUTEROS_USER=admin
# ROUTEROS_PASS=password

# Jalankan migrasi dan seeding database
php artisan migrate --seed

# Build asset
npm install && npm run build

# Generate asset Filament
php artisan filament:install

# Cache konfigurasi (untuk produksi)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Konfigurasi

### Pengaturan Antrian

Sistem menggunakan antrian Laravel untuk pemrosesan latar belakang. Konfigurasi worker antrian:

```bash
# Jalankan worker antrian (pengembangan)
php artisan queue:work

# Untuk produksi, konfigurasikan supervisor untuk mengelola worker antrian
```

### Tugas Terjadwal

Atur cron job untuk menjalankan scheduler Laravel:

```bash
* * * * * cd /path-ke-proyek-anda && php artisan schedule:run >> /dev/null 2>&1
```

### Konfigurasi Tambahan

- Atur zona waktu yang benar di file `.env` Anda: `APP_TIMEZONE=Asia/Jakarta`
- Konfigurasikan pengaturan email untuk mengirim faktur dan notifikasi
- Atur izin file yang tepat untuk direktori storage dan bootstrap/cache

## Pengaturan Webhook Xendit

### Instalasi Ngrok untuk Pengujian Webhook

Untuk pengujian webhook Xendit pada lingkungan lokal atau server tanpa IP publik, Anda memerlukan layanan tunnel seperti Ngrok:

```bash
# Unduh Ngrok
cd ~
wget https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v3-stable-linux-amd64.tgz

# Ekstrak file
tar xvzf ngrok-v3-stable-linux-amd64.tgz

# Pindahkan ke direktori global (opsional)
sudo mv ngrok /usr/local/bin

# Daftar dan dapatkan token di ngrok.com, lalu konfigurasi
ngrok config add-authtoken YOUR_AUTH_TOKEN_HERE

# Jalankan Ngrok (port sesuai dengan server web Anda)
ngrok http 80
```

### Konfigurasi Webhook di Dashboard Xendit

1. Dapatkan URL publik dari output Ngrok (misal: `https://a1b2c3d4.ngrok.io`)
2. Login ke [dashboard.xendit.co](https://dashboard.xendit.co) dan navigasi ke Settings > Developers > Webhooks
3. Tambahkan endpoint webhook: `https://a1b2c3d4.ngrok.io/api/webhook/xendit`
4. Pilih event yang dibutuhkan: `invoice.paid`, `invoice.expired`, dll.
5. Simpan token callback yang diberikan dalam `.env` Anda:
   ```
   XENDIT_WEBHOOK_TOKEN=token_dari_xendit
   ```

### Pengaturan Ngrok sebagai Service (Opsional)

Untuk menjalankan Ngrok sebagai service yang otomatis berjalan saat startup:

```bash
# Buat file service systemd
sudo nano /etc/systemd/system/ngrok.service

# Isi dengan konfigurasi berikut:
# [Unit]
# Description=ngrok
# After=network.target
#
# [Service]
# ExecStart=/usr/local/bin/ngrok http 80
# Restart=always
# User=YOUR_USERNAME
#
# [Install]
# WantedBy=multi-user.target

# Aktifkan dan jalankan service
sudo systemctl enable ngrok.service
sudo systemctl start ngrok.service

# Periksa URL tunnel yang aktif
curl http://localhost:4040/api/tunnels
```

> **Catatan Penting**: Untuk lingkungan produksi, gunakan domain dengan IP publik dan SSL yang tepat, bukan Ngrok. Ngrok hanya direkomendasikan untuk pengujian dan pengembangan.

## Penggunaan

### Panel Admin

Akses panel admin di `/admin`. Kredensial default:

- **Email**: admin@example.com
- **Password**: password

### Endpoint API

Sistem menyediakan endpoint API untuk integrasi dengan sistem lain:

- `/api/invoices` - Mengelola faktur
- `/api/payments` - Memproses pembayaran
- `/api/customers` - Mengelola data pelanggan
- `/api/webhook/xendit` - Webhook untuk notifikasi pembayaran Xendit

## Alur Kerja Integrasi Mikrotik

ArtacomBillingSystem terintegrasi dengan RouterOS untuk otomatisasi penanganan pelanggan:

1. **Penagihan Otomatis**
   - Sistem membuat invoice 5 hari sebelum jatuh tempo
   - Notifikasi dikirim ke pelanggan via email/SMS

2. **Pemantauan Pembayaran**
   - Xendit mengirim webhook saat pembayaran diterima
   - Sistem memperbarui status pelanggan

3. **Penanganan Status Koneksi**
   - Pelanggan dengan status overdue (melewati jatuh tempo) otomatis di-suspend di Mikrotik
   - Setelah pembayaran, status diaktifkan kembali otomatis

4. **Monitoring & Notifikasi**
   - Dashboard menampilkan status pelanggan secara real-time
   - Notifikasi otomatis untuk admin dan pelanggan

## Tinjauan Paket

ArtacomBillingSystem menggunakan beberapa paket utama:

- **filament/filament**: Framework panel admin
- **xendit/xendit-php**: Integrasi gateway pembayaran Xendit
- **evilfreelancer/routeros-api-php**: Klien API Mikrotik RouterOS
- **spatie/laravel-permission**: Manajemen peran dan izin
- **spatie/laravel-activitylog**: Pencatatan aktivitas
- **maatwebsite/excel**: Fungsionalitas impor/ekspor Excel
- **livewire/livewire**: Komponen UI interaktif

## Panduan Deployment

### Checklist Produksi

- Konfigurasikan lingkungan ke `production`
- Nonaktifkan mode debug
- Cache konfigurasi, rute, dan tampilan
- Siapkan sertifikat SSL yang tepat
- Konfigurasikan worker antrian dengan Supervisor
- Siapkan backup database
- Konfigurasikan pemantauan dan pencatatan log

### Pertimbangan Keamanan

- Perbarui semua paket secara berkala
- Gunakan kredensial yang kuat untuk akses database dan admin
- Amankan kunci API Xendit Anda
- Terapkan pembatasan laju pada endpoint API
- Ikuti praktik keamanan terbaik Laravel

## Troubleshooting

### Masalah Umum dan Solusinya

#### Webhook Xendit Tidak Berfungsi
- Verifikasi URL webhook sudah benar (tanpa trailing slash)
- Pastikan token webhook telah dikonfigurasi dengan benar
- Periksa log Laravel untuk error

#### Koneksi Mikrotik Gagal
- Verifikasi kredensial RouterOS di file `.env`
- Pastikan API RouterOS diaktifkan di Mikrotik
- Periksa firewall untuk akses IP server ke Mikrotik

#### Masalah Pengaturan Jadwal
- Pastikan cron job dikonfigurasi dengan benar
- Verifikasi perintah artisan schedule:run berjalan
- Periksa izin pengguna untuk cron job

## Roadmap

Berikut adalah fitur yang direncanakan untuk pengembangan masa depan:

- Integrasi gateway pembayaran tambahan
- Aplikasi mobile untuk pelanggan (Android & iOS)
- Dashboard pelanggan yang ditingkatkan
- Fitur tiket dukungan terintegrasi
- Laporan analitik yang lebih canggih
- Integrasi dengan sistem akuntansi

## Kontribusi

Terima kasih telah mempertimbangkan untuk berkontribusi pada ArtacomBillingSystem! Silakan ikuti langkah-langkah berikut:

1. Fork repositori
2. Buat branch fitur (`git checkout -b fitur/fitur-luar-biasa`)
3. Commit perubahan Anda (`git commit -m 'Menambahkan fitur luar biasa'`)
4. Push ke branch (`git push origin fitur/fitur-luar-biasa`)
5. Buka Pull Request

## Lisensi

ArtacomBillingSystem adalah perangkat lunak open-source yang dilisensikan di bawah [lisensi MIT](https://opensource.org/licenses/MIT).

## Ucapan Terima Kasih

- [Laravel](https://laravel.com)
- [Filament](https://filamentphp.com)
- [Xendit](https://xendit.co) 
- [Mikrotik RouterOS](https://mikrotik.com)
- Semua paket dan kontributor lainnya
