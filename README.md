# Siskripsi PGSD

Aplikasi web untuk pengajuan judul, seminar proposal, dan sidang skripsi Prodi PGSD Universitas Muhammadiyah Muara Bungo. Aplikasi ini juga mengatur pembagian dosen pembimbing dan penguji.

Dibangun dengan Laravel 12 (PHP 8.2+) dan MySQL. Tampilan memakai Blade dan CSS biasa, tanpa proses build, sehingga cocok untuk shared hosting.

## Yang sudah ada (langkah 2)

- Login dengan NIM, nama pengguna, atau email. Mahasiswa mendaftar akun sendiri, lalu admin mengaktifkannya. NIM harus ada di data mahasiswa prodi.
- Empat peran: mahasiswa, dosen, kaprodi, admin. Admin bisa membuka semua halaman.
- Mahasiswa: tiga tahap (judul, seminar proposal, sidang). Syarat dicek otomatis, berkas berupa link, dan tombol Daftar baru aktif bila syarat wajib terpenuhi. Tahap berikutnya terbuka setelah tahap sebelumnya lulus.
- Kaprodi: menyetujui judul sambil menetapkan pembimbing 1 dan 2. Sistem menyarankan dosen dengan beban paling ringan dibanding kuotanya. Kaprodi juga memverifikasi syarat seminar dan sidang, serta melihat rekap beban dosen.
- Admin: data dosen (NIDN/NUPTK boleh kosong, kuota per peran, impor CSV), data mahasiswa (impor CSV), ruang, syarat tiap tahap, akun pengguna, dan periode.

## Penjadwalan (langkah 3)

- Admin membuat sesi ujian (kode, tanggal, jam, ruang, jumlah mahasiswa per sesi), lalu mengisi slotnya dengan mahasiswa yang sudah terverifikasi.
- Penguji 1 dan 2 disarankan dari dosen yang boleh menguji, bukan pembimbing mahasiswa itu, dengan beban paling ringan dibanding kuotanya. Untuk sidang, penguji seminar proposal dipakai lagi.
- Sistem menolak bentrok: ruang yang sama atau dosen (pembimbing maupun penguji) yang sama di sesi lain pada jam yang beririsan.
- Jadwal baru terlihat oleh mahasiswa dan dosen setelah diterbitkan. Mahasiswa bisa membuka dan mencetak kartu ujian.
- Kaprodi atau admin mencatat hasil (lulus, lulus dengan revisi, mengulang). Lulus seminar proposal membuka tahap sidang.
- Jadwal bisa diekspor ke CSV dengan susunan kolom seperti Sheet lama, lalu dibuka di Excel.
- Rekap beban dosen menampilkan jumlah bimbingan dan tugas menguji dibanding kuotanya.

## Menjalankan di komputer sendiri

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan db:seed --class=ContohSeeder   # data contoh, hanya untuk uji coba
php artisan serve
```

Akun contoh (password `password123`): `kaprodi`, `siti` (dosen), `2021130045` (mahasiswa). Akun admin: `admin` / `gantipassword123`.

Jalankan tes: `php artisan test`.

## Memasang di Hostinger (shared hosting)

1. Di hPanel, buat database MySQL beserta penggunanya.
2. Aktifkan akses SSH (hPanel → Advanced → SSH Access), lalu masuk lewat SSH.
3. Clone repositori ke folder di luar `public_html`, misalnya `~/siskripsi`, lalu jalankan `composer install --no-dev --optimize-autoloader`.
4. Salin `.env.example` ke `.env`, lalu isi: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://domain-anda`, `DB_CONNECTION=mysql`, data database dari langkah 1, serta `ADMIN_EMAIL` dan `ADMIN_PASSWORD` untuk akun admin pertama.
5. Jalankan `php artisan key:generate`, `php artisan migrate --seed --force`, lalu `php artisan config:cache` dan `php artisan route:cache`.
6. Arahkan domain ke folder `public` aplikasi. Caranya: hapus `public_html`, lalu buat symlink `ln -s ~/siskripsi/public ~/domains/domain-anda/public_html`. Sesuaikan path dengan struktur akun Anda.
7. Masuk sebagai admin, ganti password, lalu impor data dosen dan mahasiswa.

Jangan jalankan `ContohSeeder` di server produksi.

## Format CSV impor

- Dosen: `nama, jenis_identitas, nomor_identitas, boleh_p1, boleh_p2, boleh_penguji, kuota_p1, kuota_p2, kuota_u1, kuota_u2`
- Mahasiswa: `nim, nama, angkatan, no_hp`

Pemisah koma atau titik koma (format CSV Excel versi Indonesia) sama-sama diterima.
