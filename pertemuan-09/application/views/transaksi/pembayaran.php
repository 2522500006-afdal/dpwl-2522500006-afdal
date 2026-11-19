<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$p = $pembayaran ?? [];
require __DIR__ . '/../katalog/header.php';
require __DIR__ . '/../katalog/navbar.php';
$status = (string)($p['sttus_byr'] ?? 'pending');
$statusLabel = ['pending' => 'Menunggu verifikasi', 'valid' => 'Diterima', 'invalid' => 'Ditolak'][$status] ?? $status;
?>
<main class="container py-5">
  <div class="row justify-content-center"><div class="col-lg-8">
    <div class="card shadow-sm border-0"><div class="card-body p-4">
      <h1 class="h3 text-success mb-3">Pembayaran Awal (DP)</h1>
      <p class="text-muted">Unggah bukti transfer DP untuk transaksi sewa Anda.</p>
      <?php if (!empty($flash)): ?><div class="alert alert-<?= $e(($flash['type'] ?? '') === 'success' ? 'success' : 'danger') ?>"><?= $e($flash['message'] ?? '') ?></div><?php endif; ?>
      <div class="table-responsive"><table class="table table-bordered">
        <tr><th>Kode Pembayaran</th><td><?= $e($p['kode_bayar'] ?? '') ?></td></tr>
        <tr><th>Kode Transaksi</th><td><?= $e($p['kode_sewa'] ?? '') ?></td></tr>
        <tr><th>Jenis Pembayaran</th><td><?= $e(strtoupper($p['jns_byr'] ?? 'DP')) ?></td></tr>
        <tr><th>Metode</th><td><?= $e(ucfirst($p['mtode_byr'] ?? 'transfer')) ?></td></tr>
        <tr><th>Nominal DP</th><td><strong>Rp <?= number_format((int)($p['nominal'] ?? 0), 0, ',', '.') ?></strong></td></tr>
        <tr><th>Status</th><td><span class="badge bg-<?= $status === 'valid' ? 'success' : ($status === 'invalid' ? 'danger' : 'warning') ?>"><?= $e($statusLabel) ?></span></td></tr>
      </table></div>
      <?php if ($status === 'pending'): ?>
        <?php if (!empty($p['ft_byr'])): ?><div class="alert alert-info">Bukti pembayaran sudah dikirim. Anda dapat menggantinya selama admin belum melakukan verifikasi.</div><p><a target="_blank" rel="noopener" href="<?= $e(base_url('uploads/bayar/' . rawurlencode(basename($p['ft_byr'])))) ?>">Lihat bukti yang diunggah</a></p><?php endif; ?>
        <form action="<?= $e(site_url('transaksi/pembayaran/unggah')) ?>" method="post" enctype="multipart/form-data">
          <input type="hidden" name="kode_sewa" value="<?= $e($p['kode_sewa'] ?? '') ?>">
          <input type="hidden" name="kode_bayar" value="<?= $e($p['kode_bayar'] ?? '') ?>">
          <div class="mb-3"><label for="ft_byr" class="form-label">Bukti transfer (JPEG, PNG, WEBP; maks. 5 MB)</label><input class="form-control" type="file" id="ft_byr" name="ft_byr" accept="image/jpeg,image/png,image/webp" required></div>
          <button class="btn btn-success" type="submit">Kirim Bukti Pembayaran</button>
        </form>
      <?php elseif ($status === 'valid'): ?>
        <div class="alert alert-success mb-0">Pembayaran DP telah diterima. Transaksi Anda disetujui.</div>
      <?php else: ?>
        <div class="alert alert-danger mb-0">Pembayaran ditolak. Silakan hubungi admin untuk informasi lebih lanjut.</div>
      <?php endif; ?>
      <a class="btn btn-outline-secondary mt-3" href="<?= $e(site_url('katalog')) ?>">Kembali ke katalog</a>
    </div></div>
  </div></div>
</main>
<?php require __DIR__ . '/../katalog/footer.php'; ?>
