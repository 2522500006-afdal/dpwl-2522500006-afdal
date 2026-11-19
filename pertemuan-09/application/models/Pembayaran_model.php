<?php
class Pembayaran_model
{
    private mysqli $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
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
}
