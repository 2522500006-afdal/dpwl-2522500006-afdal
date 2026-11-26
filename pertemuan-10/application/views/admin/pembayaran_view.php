<?php
require APPPATH . 'views/templates/header.php';
require APPPATH . 'views/templates/sidebar.php';
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$pembayaran = $pembayaran ?? [];
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Verifikasi Pembayaran <small>Pemeriksaan bukti pembayaran DP</small></h1></section>
  <section class="content">
    <?php if (!empty($flash)): ?><div class="alert alert-<?= $e(($flash['type'] ?? '') === 'success' ? 'success' : 'danger') ?> alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= $e($flash['message'] ?? '') ?></div><?php endif; ?>
    <div class="box"><div class="box-header"><h3 class="box-title">Daftar Pembayaran Penyewa</h3></div>
      <div class="box-body table-responsive">
        <table class="table table-bordered table-striped js-data-table">
          <thead><tr><th>No</th><th>Kode Bayar</th><th>Kode Sewa</th><th>Penyewa</th><th>Nominal DP</th><th>Tanggal</th><th>Bukti</th><th>Status</th><th>Aksi</th></tr></thead>
          <tbody>
          <?php foreach ($pembayaran as $i => $p): $st = (string)($p['sttus_byr'] ?? ''); ?>
            <tr>
              <td><?= (int)$i + 1 ?></td><td><?= $e($p['kode_bayar']) ?></td><td><?= $e($p['kode_sewa']) ?></td>
              <td><?= $e(($p['nm_lngkp'] ?? '') . ' (' . ($p['user_plg'] ?? '') . ')') ?></td>
              <td>Rp <?= number_format((int)$p['nominal'], 0, ',', '.') ?></td><td><?= $e($p['tgl_wkt_byr'] ?? '-') ?></td>
              <td><?php if (!empty($p['ft_byr'])): ?><a href="<?= $e(base_url('uploads/bayar/' . rawurlencode(basename($p['ft_byr'])))) ?>" target="_blank" rel="noopener">Lihat bukti</a><?php else: ?><span class="text-muted">Belum diunggah</span><?php endif; ?></td>
              <td><span class="label label-<?= $st === 'valid' ? 'success' : ($st === 'invalid' ? 'danger' : 'warning') ?>"><?= $e($st) ?></span></td>
              <td><?php if ($st === 'pending' && !empty($p['ft_byr'])): ?>
                <form action="<?= $e(site_url('admin/pembayaran/verifikasi')) ?>" method="post" style="display:inline-block" onsubmit="return confirm('Terima pembayaran ini?')"><input type="hidden" name="kode_bayar" value="<?= $e($p['kode_bayar']) ?>"><input type="hidden" name="keputusan" value="valid"><button class="btn btn-success btn-xs" type="submit"><i class="fa fa-check"></i> Terima</button></form>
                <form action="<?= $e(site_url('admin/pembayaran/verifikasi')) ?>" method="post" style="display:inline-block" onsubmit="return confirm('Tolak pembayaran ini?')"><input type="hidden" name="kode_bayar" value="<?= $e($p['kode_bayar']) ?>"><input type="hidden" name="keputusan" value="invalid"><button class="btn btn-danger btn-xs" type="submit"><i class="fa fa-times"></i> Tolak</button></form>
              <?php elseif ($st === 'pending'): ?><span class="text-muted">Menunggu bukti</span><?php else: ?><span class="text-muted">Selesai</span><?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?php require APPPATH . 'views/templates/footer.php'; ?>
