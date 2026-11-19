# P9 - Transaksi MVC II: Pembayaran Awal dan Verifikasi Pembayaran

P9 melanjutkan aplikasi P8. Fitur P7 (keranjang dan checkout) tetap dipertahankan.

## Fitur yang ditambahkan

1. Setelah checkout, penyewa diarahkan ke halaman Pembayaran Awal.
2. Sistem menampilkan kode pembayaran, kode sewa, nominal DP 30%, metode, dan status pembayaran.
3. Penyewa dapat mengunggah bukti transfer berupa JPEG, PNG, atau WEBP dengan ukuran maksimal 5 MB.
4. File bukti disimpan pada `uploads/bayar/` dengan nama acak. File PHP tidak diizinkan dijalankan dari folder upload.
5. Admin dapat membuka menu **Verifikasi Pembayaran**, melihat bukti, lalu menerima atau menolak pembayaran.
6. Proses verifikasi memperbarui status pembayaran dan status transaksi dalam satu transaksi database.
7. Pembayaran hanya dapat diverifikasi satu kali dan bukti hanya dapat dikirim oleh pemilik transaksi.

## Cara menjalankan di Laragon

1. Buat salinan/backup database yang digunakan pada P8.
2. Salin seluruh isi folder `pertemuan-09` ke `C:\laragon\www\dpwl-2522500006` (atau folder proyek yang sama dengan konfigurasi P8).
3. Pastikan `application/config/database.php` sesuai dengan nama database MySQL lokal. Konfigurasi bawaan P8 menggunakan database `db_dpwl_deni`, user `root`, password kosong, port `3306`.
4. Pastikan tabel P8 (`t_bayar`, `t_sewa`, `t_penyewa`) tetap tersedia. P9 menggunakan kolom pembayaran yang sudah dibuat oleh proses checkout P8: `kode_bayar`, `tgl_wkt_byr`, `ft_byr`, `mtode_byr`, `jns_byr`, `nominal`, `sttus_byr`, `tgl_wkt_ver`, dan `kode_sewa`.
5. Pastikan PHP mengaktifkan ekstensi `mysqli` dan `fileinfo`, serta `file_uploads` aktif. Batas unggahan aplikasi adalah 5 MB.
6. Buka `http://localhost/dpwl-2522500006/`.
7. Login sebagai penyewa, pilih kendaraan, lakukan checkout, lalu unggah bukti DP.
8. Login sebagai admin aktif dan buka menu **Verifikasi Pembayaran**.

## Status yang digunakan

- `t_bayar.sttus_byr`: `pending`, `valid`, `invalid` (sesuai ketentuan P9).
- `t_sewa.status_sewa`: setelah checkout `diajukan`; jika DP valid menjadi `disetujui`; jika invalid menjadi `ditolak`.

Berkas ZIP sumber tidak menyertakan dump skema database. Pastikan kolom status pada database mengizinkan nilai-nilai tersebut.

## Berkas P9 yang ditambahkan/diubah

- Baru: `application/models/Pembayaran_model.php`
- Baru: `application/controllers/Pembayaran_controller.php`
- Baru: `application/controllers/Admin_pembayaran_controller.php`
- Baru: `application/views/transaksi/pembayaran.php`
- Baru: `application/views/admin/pembayaran_view.php`
- Baru: `uploads/bayar/.htaccess`
- Diubah: `application/config/routes.php`
- Diubah: `application/controllers/Transaksi_controller.php` (redirect checkout menuju pembayaran)
- Diubah: `application/views/templates/sidebar.php` (menu verifikasi admin)

## Catatan pengujian

Pemeriksaan sintaks PHP dilakukan pada file aplikasi. Pengujian alur transaksi secara langsung tetap perlu dilakukan di Laragon menggunakan database P8 milik pengguna.


## Checklist pengujian manual

- [ ] Penyewa yang belum login diarahkan ke halaman login.
- [ ] Penyewa hanya dapat melihat pembayaran milik transaksinya sendiri.
- [ ] File bukan gambar, ukuran lebih dari 5 MB, atau file tanpa bukti ditolak.
- [ ] Unggah bukti mengubah `ft_byr` dan `tgl_wkt_byr`, sementara `sttus_byr` tetap `pending`.
- [ ] Admin melihat pembayaran pending beserta tautan bukti.
- [ ] Admin menerima pembayaran: `sttus_byr=valid`, `status_sewa=disetujui`, dan `tgl_wkt_ver` terisi.
- [ ] Admin menolak pembayaran: `sttus_byr=invalid`, `status_sewa=ditolak`, dan `tgl_wkt_ver` terisi.
- [ ] Verifikasi kedua kali ditolak; transaksi tidak diproses ulang.

Ambil tangkapan layar dari halaman pembayaran penyewa, daftar verifikasi admin, dan hasil verifikasi setelah menjalankan aplikasi dengan database lokal. Hasil uji runtime dan tangkapan layar belum dapat dibuat di sini karena ZIP tidak menyertakan database MySQL pengguna.
