# P10 - Pelunasan dan Dokumen Transaksi

## Berkas yang ditambahkan/diubah
- `application/models/Pembayaran_model.php`: method daftar pelunasan, detail transaksi, kode bayar, dan simpan pelunasan. Method P9 dipertahankan.
- `application/controllers/Pembayaran_controller.php`: endpoint daftar/detail/proses pelunasan dan dokumen transaksi. Alur pembayaran penyewa P9 tetap dipertahankan.
- `application/config/routes.php`: route P10.
- `application/views/templates/sidebar.php`: menu Pelunasan Transaksi.
- `application/views/admin/pelunasan.php`: daftar transaksi.
- `application/views/admin/pelunasan_detail.php`: detail, riwayat, formulir pelunasan.
- `application/views/admin/dokumen_transaksi.php`: dokumen cetak/PDF.

## Persiapan database
1. Backup database `db_dpwl_deni`.
2. Periksa struktur `t_sewa.status_sewa` dan `t_bayar.jns_byr`.
3. Jalankan `upgrade_p10.sql` satu kali apabila nilai enum `lunas` dan `pelunasan` belum tersedia.

## URL
- `admin/pembayaran/pelunasan` - daftar transaksi pelunasan.
- `admin/pembayaran/pelunasan/INV~YYYYMMDD~NN` - detail transaksi.
- `admin/pembayaran/dokumen/INV~YYYYMMDD~NN` - dokumen transaksi.

## Aturan P10
- Hanya transaksi berstatus `disetujui` yang dapat dilunasi.
- Nominal pelunasan dihitung otomatis dari total transaksi dikurangi pembayaran valid.
- Metode hanya `tunai` atau `transfer`; transfer wajib mengunggah bukti.
- Satu transaksi hanya boleh memiliki satu record `pelunasan`.
- Pembayaran pelunasan dicatat valid dan status transaksi berubah menjadi `lunas` dalam satu transaksi database.
- Status kendaraan tidak diubah pada P10.

## Pengujian yang harus dilakukan di komputer lokal
- Buka daftar pelunasan sebagai admin.
- Buka detail transaksi yang statusnya `disetujui`.
- Uji metode tunai dan transfer (format gambar JPEG/PNG/WEBP maksimal 5 MB).
- Pastikan nominal pelunasan sama dengan sisa kewajiban.
- Pastikan transaksi tidak dapat dilunasi dua kali.
- Buka dokumen transaksi dan uji cetak / simpan sebagai PDF.
