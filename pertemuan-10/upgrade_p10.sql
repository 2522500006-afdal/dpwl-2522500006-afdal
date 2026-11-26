-- Jalankan satu kali pada database db_dpwl_deni setelah backup database.
-- Periksa struktur kolom sebelum menjalankan.
ALTER TABLE `t_sewa` MODIFY `status_sewa` ENUM('diajukan','disetujui','ditolak','aktif','selesai','batal','keranjang','lunas') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NULL DEFAULT 'keranjang';
ALTER TABLE `t_bayar` MODIFY `jns_byr` ENUM('dp','pelunasan') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL;
