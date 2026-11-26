<?php
require APPPATH . 'views/templates/header.php';
require APPPATH . 'views/templates/sidebar.php';
$e = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$daftarPelunasan = $daftarPelunasan ?? [];
?>
<div class="content-wrapper">
  <section class="content-header">
    <h1>Pelunasan Transaksi <small>Daftar transaksi yang masih memiliki kewajiban</small></h1>
  </section>
  <section class="content">
    <?php if (!empty($flash)): ?>
      <div class="alert alert-<?= $e(($flash['type'] ?? '') === 'success' ? 'success' : 'danger') ?> alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button><?= $e($flash['message'] ?? '') ?>
      </div>
    <?php endif; ?>
    <div class="box box-primary">
      <div class="box-header with-border"><h3 class="box-title">Daftar Transaksi Penyewaan</h3></div>
      <div class="box-body table-responsive">
        <table class="table table-bordered table-striped js-data-table">
          <thead><tr><th>No</th><th>Kode Sewa</th><th>Penyewa</th><th>Periode Sewa</th><th>Total Transaksi</th><th>Terbayar</th><th>Sisa Kewajiban</th><th>Status</th><th>Aksi</th></tr></thead>
          <tbody>
          <?php foreach ($daftarPelunasan as $i => $item): ?>
            <tr>
              <td><?= (int) $i + 1 ?></td>
              <td><?= $e($item['kode_sewa'] ?? '') ?></td>
              <td><?= $e(($item['nm_lngkp'] ?? '') . ' (' . ($item['user_plg'] ?? '') . ')') ?></td>
              <td><?= $e($item['tgl_mulai'] ?? '-') ?> s.d. <?= $e($item['tgl_selesai'] ?? '-') ?></td>
              <td>Rp <?= number_format((int) ($item['total_transaksi'] ?? 0), 0, ',', '.') ?></td>
              <td>Rp <?= number_format((int) ($item['total_terbayar'] ?? 0), 0, ',', '.') ?></td>
              <td><strong>Rp <?= number_format((int) ($item['sisa_kewajiban'] ?? 0), 0, ',', '.') ?></strong></td>
              <td><span class="label label-<?= ($item['status_sewa'] ?? '') === 'lunas' ? 'success' : 'warning' ?>"><?= $e($item['status_sewa'] ?? '-') ?></span></td>
              <td><a class="btn btn-primary btn-xs" href="<?= $e(site_url('admin/pembayaran/pelunasan/' . str_replace('/', '~', (string) ($item['kode_sewa'] ?? '')))) ?>"><i class="fa fa-search"></i> Detail</a>
                <?php if (($item['status_sewa'] ?? '') === 'lunas'): ?><a class="btn btn-default btn-xs" target="_blank" rel="noopener" href="<?= $e(site_url('admin/pembayaran/dokumen/' . str_replace('/', '~', (string) ($item['kode_sewa'] ?? '')))) ?>"><i class="fa fa-print"></i> Dokumen</a><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?php require APPPATH . 'views/templates/footer.php'; ?>
