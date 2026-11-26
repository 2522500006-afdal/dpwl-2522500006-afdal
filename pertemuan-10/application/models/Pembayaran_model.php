<?php
class Pembayaran_model
{
    private mysqli $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }


    public function getDaftarPembayaranPenyewa(string $userPlg): array
    {
        $sql = "SELECT b.kode_bayar, b.tgl_wkt_byr, b.ft_byr, b.mtode_byr,
                       b.jns_byr, b.nominal, b.sttus_byr, b.tgl_wkt_ver,
                       b.kode_sewa, s.status_sewa
                FROM t_bayar b
                INNER JOIN t_sewa s ON s.kode_sewa = b.kode_sewa
                WHERE s.user_plg = ? AND b.jns_byr = 'dp'
                ORDER BY b.tgl_wkt_byr DESC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new RuntimeException('Daftar pembayaran gagal disiapkan.');
        $stmt->bind_param('s', $userPlg);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Daftar pembayaran gagal dibaca.');
        }
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function getPembayaranPenyewa(string $kodeSewa, string $userPlg): ?array
    {
        $sql = "SELECT b.kode_bayar, b.tgl_wkt_byr, b.ft_byr, b.mtode_byr,
                       b.jns_byr, b.nominal, b.sttus_byr, b.tgl_wkt_ver,
                       b.kode_sewa, s.status_sewa
                FROM t_bayar b
                INNER JOIN t_sewa s ON s.kode_sewa = b.kode_sewa
                WHERE b.kode_sewa = ? AND s.user_plg = ? AND b.jns_byr = 'dp'
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new RuntimeException('Data pembayaran gagal disiapkan.');
        $stmt->bind_param('ss', $kodeSewa, $userPlg);
        if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Data pembayaran gagal dibaca.'); }
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function unggahBukti(string $kodeBayar, string $userPlg, string $namaFile): bool
    {
        $sql = "UPDATE t_bayar b
                INNER JOIN t_sewa s ON s.kode_sewa = b.kode_sewa
                SET b.ft_byr = ?, b.tgl_wkt_byr = NOW(), b.sttus_byr = 'pending', b.tgl_wkt_ver = NULL
                WHERE b.kode_bayar = ? AND s.user_plg = ?
                  AND b.jns_byr = 'dp' AND b.sttus_byr = 'pending'";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new RuntimeException('Penyimpanan bukti pembayaran gagal disiapkan.');
        $stmt->bind_param('sss', $namaFile, $kodeBayar, $userPlg);
        $ok = $stmt->execute();
        $changed = $stmt->affected_rows;
        $stmt->close();
        if (!$ok) throw new RuntimeException('Bukti pembayaran gagal disimpan.');
        return $changed === 1;
    }

    public function getSemuaPembayaran(): array
    {
        $sql = "SELECT b.kode_bayar, b.tgl_wkt_byr, b.ft_byr, b.mtode_byr,
                       b.jns_byr, b.nominal, b.sttus_byr, b.tgl_wkt_ver,
                       b.kode_sewa, s.user_plg, s.status_sewa, p.nm_lngkp
                FROM t_bayar b
                INNER JOIN t_sewa s ON s.kode_sewa = b.kode_sewa
                LEFT JOIN t_penyewa p ON p.user_plg = s.user_plg
                WHERE b.jns_byr = 'dp'
                ORDER BY CASE WHEN b.sttus_byr = 'pending' THEN 0 ELSE 1 END,
                         b.tgl_wkt_byr DESC";
        $result = $this->db->query($sql);
        if (!$result) throw new RuntimeException('Daftar pembayaran gagal dibaca.');
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        return $rows;
    }

    public function verifikasi(string $kodeBayar, string $keputusan): bool
    {
        if (!in_array($keputusan, ['valid', 'invalid'], true)) {
            throw new InvalidArgumentException('Keputusan verifikasi tidak valid.');
        }
        $this->db->begin_transaction();
        try {
            $sql = "SELECT b.kode_sewa, b.ft_byr, b.sttus_byr
                    FROM t_bayar b
                    WHERE b.kode_bayar = ? AND b.jns_byr = 'dp'
                    LIMIT 1 FOR UPDATE";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) throw new RuntimeException('Pemeriksaan pembayaran gagal disiapkan.');
            $stmt->bind_param('s', $kodeBayar);
            if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Pemeriksaan pembayaran gagal dijalankan.'); }
            $bayar = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$bayar || empty($bayar['ft_byr']) || $bayar['sttus_byr'] !== 'pending') {
                throw new RuntimeException('Pembayaran belum memiliki bukti atau sudah diverifikasi.');
            }

            $stmt = $this->db->prepare("UPDATE t_bayar SET sttus_byr = ?, tgl_wkt_ver = NOW() WHERE kode_bayar = ? AND sttus_byr = 'pending'");
            if (!$stmt) throw new RuntimeException('Pembaruan status pembayaran gagal disiapkan.');
            $stmt->bind_param('ss', $keputusan, $kodeBayar);
            if (!$stmt->execute() || $stmt->affected_rows !== 1) { $stmt->close(); throw new RuntimeException('Status pembayaran tidak berhasil diperbarui.'); }
            $stmt->close();

            // Status transaksi mengikuti hasil pemeriksaan DP.
            $statusSewa = $keputusan === 'valid' ? 'disetujui' : 'ditolak';
            $stmt = $this->db->prepare("UPDATE t_sewa SET status_sewa = ? WHERE kode_sewa = ? AND status_sewa = 'diajukan'");
            if (!$stmt) throw new RuntimeException('Pembaruan status transaksi gagal disiapkan.');
            $stmt->bind_param('ss', $statusSewa, $bayar['kode_sewa']);
            if (!$stmt->execute() || $stmt->affected_rows !== 1) { $stmt->close(); throw new RuntimeException('Status transaksi tidak dapat diperbarui. Pastikan transaksi masih diajukan.'); }
            $stmt->close();
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /** P10: daftar transaksi yang masih memiliki kewajiban atau sudah lunas. */
    public function getDaftarPelunasan(): array
    {
        $sql = "SELECT s.kode_sewa, s.tgl_mulai, s.tgl_selesai, s.status_sewa,
                       p.user_plg, p.nm_lngkp, p.no_hp,
                       COALESCE((SELECT SUM(tp.hrg_sewa * tp.n_hari)
                                 FROM t_pilih tp WHERE tp.kode_sewa = s.kode_sewa), 0) AS total_transaksi,
                       COALESCE((SELECT SUM(b.nominal) FROM t_bayar b
                                 WHERE b.kode_sewa = s.kode_sewa AND b.sttus_byr = 'valid'), 0) AS total_terbayar
                FROM t_sewa s
                INNER JOIN t_penyewa p ON p.user_plg = s.user_plg
                WHERE s.status_sewa IN ('disetujui', 'lunas')
                  AND EXISTS (SELECT 1 FROM t_pilih tp WHERE tp.kode_sewa = s.kode_sewa)
                ORDER BY s.tgl_mulai DESC, s.kode_sewa DESC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new RuntimeException('Query daftar pelunasan gagal disiapkan.');
        if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Daftar pelunasan gagal diambil.'); }
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($rows as &$row) {
            $row['total_transaksi'] = (int) $row['total_transaksi'];
            $row['total_terbayar'] = (int) $row['total_terbayar'];
            $row['sisa_kewajiban'] = max(0, $row['total_transaksi'] - $row['total_terbayar']);
        }
        unset($row);

        return array_values(array_filter($rows, static fn(array $row): bool =>
            $row['sisa_kewajiban'] > 0 || $row['status_sewa'] === 'lunas'
        ));
    }

    /** P10: detail transaksi, riwayat pembayaran, dan kendaraan. */
    public function getDetailPelunasan(string $kodeSewa): ?array
    {
        $kodeSewa = trim($kodeSewa);
        if ($kodeSewa === '') throw new InvalidArgumentException('Kode transaksi sewa wajib diisi.');

        $sql = "SELECT s.kode_sewa, s.tgl_mulai, s.tgl_selesai, s.wkt_ambil, s.status_sewa,
                       p.user_plg, p.nm_lngkp, p.no_hp, p.nik, p.almt_lngkp,
                       COALESCE((SELECT SUM(tp.hrg_sewa * tp.n_hari) FROM t_pilih tp
                                 WHERE tp.kode_sewa = s.kode_sewa), 0) AS total_transaksi,
                       COALESCE((SELECT SUM(b.nominal) FROM t_bayar b
                                 WHERE b.kode_sewa = s.kode_sewa AND b.sttus_byr = 'valid'), 0) AS total_terbayar
                FROM t_sewa s INNER JOIN t_penyewa p ON p.user_plg = s.user_plg
                WHERE s.kode_sewa = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new RuntimeException('Query detail pelunasan gagal disiapkan.');
        $stmt->bind_param('s', $kodeSewa);
        if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Detail transaksi gagal diambil.'); }
        $detail = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$detail) return null;

        $detail['total_transaksi'] = (int) $detail['total_transaksi'];
        $detail['total_terbayar'] = (int) $detail['total_terbayar'];
        $detail['sisa_kewajiban'] = max(0, $detail['total_transaksi'] - $detail['total_terbayar']);

        $stmt = $this->db->prepare("SELECT kode_bayar, tgl_wkt_byr, mtode_byr, jns_byr,
                                           nominal, sttus_byr, ft_byr, tgl_wkt_ver
                                    FROM t_bayar WHERE kode_sewa = ?
                                    ORDER BY tgl_wkt_byr ASC, kode_bayar ASC");
        if (!$stmt) throw new RuntimeException('Query riwayat pembayaran gagal disiapkan.');
        $stmt->bind_param('s', $kodeSewa);
        if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Riwayat pembayaran gagal diambil.'); }
        $detail['riwayat_pembayaran'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stmt = $this->db->prepare("SELECT m.no_plat, m.nm_mobil, tp.hrg_sewa, tp.n_hari,
                                           (tp.hrg_sewa * tp.n_hari) AS subtotal
                                    FROM t_pilih tp INNER JOIN t_mobil m ON m.no_plat = tp.no_plat
                                    WHERE tp.kode_sewa = ? ORDER BY m.nm_mobil ASC, m.no_plat ASC");
        if (!$stmt) throw new RuntimeException('Query kendaraan pelunasan gagal disiapkan.');
        $stmt->bind_param('s', $kodeSewa);
        if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Detail kendaraan gagal diambil.'); }
        $detail['daftar_kendaraan'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $detail;
    }

    /** P10: membentuk kode pembayaran harian PAY/YYYYMMDD/NN. */
    private function buatKodeBayarPelunasan(string $tanggalKode): string
    {
        $pola = 'PAY/' . $tanggalKode . '/%';
        $stmt = $this->db->prepare("SELECT kode_bayar FROM t_bayar WHERE kode_bayar LIKE ? ORDER BY kode_bayar DESC LIMIT 1");
        if (!$stmt) throw new RuntimeException('Pembuatan kode pembayaran gagal disiapkan.');
        $stmt->bind_param('s', $pola);
        if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Pembuatan kode pembayaran gagal dijalankan.'); }
        $last = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $next = 1;
        if ($last) { $parts = explode('/', (string) $last['kode_bayar']); $next = ((int) end($parts)) + 1; }
        if ($next > 99) throw new RuntimeException('Nomor pembayaran harian telah mencapai batas 99.');
        return sprintf('PAY/%s/%02d', $tanggalKode, $next);
    }

    /** P10: mencatat satu kali pelunasan penuh secara atomik. */
    public function simpanPelunasan(string $kodeSewa, string $metodeBayar, ?string $namaFileBukti = null): string
    {
        $kodeSewa = trim($kodeSewa);
        $metodeBayar = trim($metodeBayar);
        if ($kodeSewa === '') throw new InvalidArgumentException('Kode transaksi sewa wajib diisi.');
        if (!in_array($metodeBayar, ['tunai', 'transfer'], true)) throw new InvalidArgumentException('Metode pembayaran tidak valid.');
        if ($metodeBayar === 'transfer') {
            $namaFileBukti = trim((string) $namaFileBukti);
            if ($namaFileBukti === '') throw new InvalidArgumentException('Bukti transfer wajib tersedia.');
        } else {
            $namaFileBukti = null;
        }

        // Kunci ini sama dengan penomoran pembayaran DP agar nomor PAY harian tidak bertabrakan.
        $lockName = 'dpwl_payment_code_sequence';
        $lockAcquired = false;
        $transactionStarted = false;
        try {
            $stmt = $this->db->prepare('SELECT GET_LOCK(?, 10) AS lock_status');
            if (!$stmt) throw new RuntimeException('Kunci pembuatan kode pembayaran gagal disiapkan.');
            $stmt->bind_param('s', $lockName);
            if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Kunci pembayaran gagal dijalankan.'); }
            $lock = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ((int) ($lock['lock_status'] ?? 0) !== 1) throw new RuntimeException('Proses pembayaran sedang digunakan. Silakan coba kembali.');
            $lockAcquired = true;

            $this->db->begin_transaction();
            $transactionStarted = true;
            $sql = "SELECT s.kode_sewa, s.status_sewa,
                           COALESCE((SELECT SUM(tp.hrg_sewa * tp.n_hari) FROM t_pilih tp WHERE tp.kode_sewa=s.kode_sewa),0) AS total_transaksi,
                           COALESCE((SELECT SUM(b.nominal) FROM t_bayar b WHERE b.kode_sewa=s.kode_sewa AND b.sttus_byr='valid'),0) AS total_terbayar
                    FROM t_sewa s WHERE s.kode_sewa=? LIMIT 1 FOR UPDATE";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) throw new RuntimeException('Pemeriksaan transaksi gagal disiapkan.');
            $stmt->bind_param('s', $kodeSewa);
            if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Pemeriksaan transaksi gagal dijalankan.'); }
            $transaksi = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$transaksi) throw new RuntimeException('Transaksi sewa tidak ditemukan.');
            if ($transaksi['status_sewa'] !== 'disetujui') throw new RuntimeException('Status transaksi belum memenuhi syarat pelunasan.');

            $total = (int) $transaksi['total_transaksi'];
            $paid = (int) $transaksi['total_terbayar'];
            $remaining = $total - $paid;
            if ($total <= 0 || $remaining <= 0) throw new RuntimeException('Transaksi tidak memiliki sisa kewajiban untuk dilunasi.');
            if ($remaining > 8388607) throw new RuntimeException('Sisa kewajiban melebihi kapasitas kolom nominal pada database.');

            $stmt = $this->db->prepare("SELECT kode_bayar FROM t_bayar WHERE kode_sewa=? AND jns_byr='pelunasan' LIMIT 1 FOR UPDATE");
            if (!$stmt) throw new RuntimeException('Pemeriksaan pelunasan sebelumnya gagal disiapkan.');
            $stmt->bind_param('s', $kodeSewa);
            if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Pemeriksaan pelunasan sebelumnya gagal dijalankan.'); }
            $exists = $stmt->get_result()->fetch_assoc() !== null;
            $stmt->close();
            if ($exists) throw new RuntimeException('Transaksi ini sudah memiliki record pelunasan.');

            $timeResult = $this->db->query("SELECT DATE_FORMAT(NOW(), '%Y%m%d') AS tanggal_kode, NOW() AS waktu_pembayaran");
            if (!$timeResult) throw new RuntimeException('Waktu pembayaran gagal dibaca.');
            $time = $timeResult->fetch_assoc();
            $timeResult->free();
            $dateCode = (string) ($time['tanggal_kode'] ?? '');
            $paymentTime = (string) ($time['waktu_pembayaran'] ?? '');
            if (!preg_match('/^\d{8}$/', $dateCode) || $paymentTime === '') throw new RuntimeException('Waktu pembayaran tidak valid.');

            $kodeBayar = $this->buatKodeBayarPelunasan($dateCode);
            $stmt = $this->db->prepare("INSERT INTO t_bayar (kode_bayar,tgl_wkt_byr,ft_byr,mtode_byr,jns_byr,nominal,sttus_byr,tgl_wkt_ver,kode_sewa)
                                        VALUES (?,?,?,?, 'pelunasan', ?, 'valid', NOW(), ?)");
            if (!$stmt) throw new RuntimeException('Pencatatan pelunasan gagal disiapkan.');
            $stmt->bind_param('ssssis', $kodeBayar, $paymentTime, $namaFileBukti, $metodeBayar, $remaining, $kodeSewa);
            if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Pelunasan gagal disimpan.'); }
            $stmt->close();

            $stmt = $this->db->prepare("UPDATE t_sewa SET status_sewa='lunas' WHERE kode_sewa=? AND status_sewa='disetujui'");
            if (!$stmt) throw new RuntimeException('Pembaruan status transaksi gagal disiapkan.');
            $stmt->bind_param('s', $kodeSewa);
            if (!$stmt->execute() || $stmt->affected_rows !== 1) { $stmt->close(); throw new RuntimeException('Status transaksi gagal diperbarui menjadi lunas.'); }
            $stmt->close();
            $this->db->commit();
            $transactionStarted = false;
            return $kodeBayar;
        } catch (Throwable $e) {
            if ($transactionStarted) $this->db->rollback();
            throw $e;
        } finally {
            if ($lockAcquired) {
                $stmt = $this->db->prepare('SELECT RELEASE_LOCK(?)');
                if ($stmt) { $stmt->bind_param('s', $lockName); $stmt->execute(); $stmt->close(); }
            }
        }
    }

}
