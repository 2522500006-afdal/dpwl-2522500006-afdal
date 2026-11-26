<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$daftar = $daftarPembayaran ?? [];
require __DIR__ . '/../katalog/header.php';
require __DIR__ . '/../katalog/navbar.php';
?>
<main class="container py-5">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h3 text-success mb-1">Riwayat Pembayaran</h1>
      <p class="text-muted mb-0">Daftar pembayaran DP dari transaksi penyewaan kendaraan Anda.</p>
    </div>
    <a class="btn btn-outline-success mt-2 mt-md-0" href="<?= $e(site_url('katalog')) ?>">Kembali ke katalog</a>
  </div>

  <?php if (!empty($flash)): ?>
    <div class="alert alert-<?= $e(($flash['type'] ?? '') === 'success' ? 'success' : 'warning') ?>"><?= $e($flash['message'] ?? '') ?></div>
  <?php endif; ?>

  <?php if (!$daftar): ?>
    <div class="card border-0 shadow-sm">
      <div class="card-body text-center py-5">
        <i class="fa fa-receipt fa-3x text-secondary mb-3"></i>
        <h2 class="h5">Belum ada pembayaran</h2>
        <p class="text-muted mb-3">Pembayaran DP akan muncul di sini setelah Anda menyelesaikan checkout kendaraan.</p>
        <a class="btn btn-success" href="<?= $e(site_url('katalog')) ?>">Pilih Kendaraan</a>
      </div>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle bg-white">
        <thead class="table-light">
          <tr>
            <th>No.</th>
            <th>Kode Pembayaran</th>
            <th>Kode Transaksi</th>
            <th>Nominal DP</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($daftar as $i => $item):
          $status = (string)($item['sttus_byr'] ?? 'pending');
          $label = ['pending' => 'Menunggu verifikasi', 'valid' => 'Diterima', 'invalid' => 'Ditolak'][$status] ?? $status;
          $warna = $status === 'valid' ? 'success' : ($status === 'invalid' ? 'danger' : 'warning');
        ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= $e($item['kode_bayar']) ?></td>
            <td><?= $e($item['kode_sewa']) ?></td>
            <td>Rp <?= number_format((int)($item['nominal'] ?? 0), 0, ',', '.') ?></td>
            <td><span class="badge bg-<?= $warna ?>"><?= $e($label) ?></span></td>
            <td>
              <a class="btn btn-sm btn-success" href="<?= $e(site_url('transaksi/pembayaran?kode_sewa=' . rawurlencode((string)$item['kode_sewa']))) ?>">
                <?= empty($item['ft_byr']) && $status === 'pending' ? 'Bayar / Upload Bukti' : 'Lihat Detail' ?>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/../katalog/footer.php'; ?>
