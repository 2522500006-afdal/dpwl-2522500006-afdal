<?php
require APPPATH . 'views/templates/header.php';
require APPPATH . 'views/templates/sidebar.php';
$e = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$d = $detailPelunasan ?? [];
$riwayat = $d['riwayat_pembayaran'] ?? [];
$kendaraan = $d['daftar_kendaraan'] ?? [];
$total = (int) ($d['total_transaksi'] ?? 0);
$terbayar = (int) ($d['total_terbayar'] ?? 0);
$sisa = (int) ($d['sisa_kewajiban'] ?? 0);
$kode = (string) ($d['kode_sewa'] ?? '');
$pernahLunas = false;
foreach ($riwayat as $r) { if (($r['jns_byr'] ?? '') === 'pelunasan') { $pernahLunas = true; break; } }
$bolehLunas = ($d['status_sewa'] ?? '') === 'disetujui' && $sisa > 0 && !$pernahLunas;
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Detail Pelunasan <small><?= $e($kode) ?></small></h1></section>
  <section class="content">
    <?php if (!empty($flash)): ?><div class="alert alert-<?= $e(($flash['type'] ?? '') === 'success' ? 'success' : 'danger') ?> alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= $e($flash['message'] ?? '') ?></div><?php endif; ?>
    <div class="row">
      <div class="col-md-6"><div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Informasi Transaksi dan Penyewa</h3></div><div class="box-body">
        <dl class="dl-horizontal">
          <dt>Kode Sewa</dt><dd><?= $e($kode) ?></dd><dt>Status</dt><dd><span class="label label-<?= ($d['status_sewa'] ?? '') === 'lunas' ? 'success' : 'warning' ?>"><?= $e($d['status_sewa'] ?? '-') ?></span></dd>
          <dt>Nama Penyewa</dt><dd><?= $e($d['nm_lngkp'] ?? '-') ?></dd><dt>Username</dt><dd><?= $e($d['user_plg'] ?? '-') ?></dd><dt>NIK</dt><dd><?= $e($d['nik'] ?? '-') ?></dd><dt>No. HP</dt><dd><?= $e($d['no_hp'] ?? '-') ?></dd><dt>Alamat</dt><dd><?= $e($d['almt_lngkp'] ?? '-') ?></dd>
          <dt>Mulai Sewa</dt><dd><?= $e($d['tgl_mulai'] ?? '-') ?></dd><dt>Selesai Sewa</dt><dd><?= $e($d['tgl_selesai'] ?? '-') ?></dd><dt>Waktu Ambil</dt><dd><?= $e($d['wkt_ambil'] ?? '-') ?></dd>
        </dl>
      </div></div></div>
      <div class="col-md-6"><div class="box box-info"><div class="box-header with-border"><h3 class="box-title">Ringkasan Pembayaran</h3></div><div class="box-body">
        <h4>Total Transaksi <strong class="pull-right">Rp <?= number_format($total, 0, ',', '.') ?></strong></h4><hr>
        <h4>Total Terbayar <strong class="pull-right text-green">Rp <?= number_format($terbayar, 0, ',', '.') ?></strong></h4><hr>
        <h3>Sisa Kewajiban <strong class="pull-right text-red">Rp <?= number_format($sisa, 0, ',', '.') ?></strong></h3>
      </div></div></div>
    </div>
    <div class="box"><div class="box-header with-border"><h3 class="box-title">Rincian Kendaraan</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>No</th><th>Nama Kendaraan</th><th>No. Plat</th><th>Tarif/Hari</th><th>Jumlah Hari</th><th>Subtotal</th></tr></thead><tbody>
      <?php foreach ($kendaraan as $i => $k): ?><tr><td><?= (int) $i + 1 ?></td><td><?= $e($k['nm_mobil'] ?? '-') ?></td><td><?= $e($k['no_plat'] ?? '-') ?></td><td>Rp <?= number_format((int) ($k['hrg_sewa'] ?? 0), 0, ',', '.') ?></td><td><?= (int) ($k['n_hari'] ?? 0) ?></td><td>Rp <?= number_format((int) ($k['subtotal'] ?? 0), 0, ',', '.') ?></td></tr><?php endforeach; ?>
      </tbody></table></div></div>
    <div class="box"><div class="box-header with-border"><h3 class="box-title">Riwayat Pembayaran</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>No</th><th>Kode Bayar</th><th>Tanggal</th><th>Jenis</th><th>Metode</th><th>Nominal</th><th>Status</th><th>Bukti</th></tr></thead><tbody>
      <?php foreach ($riwayat as $i => $r): ?><tr><td><?= (int) $i + 1 ?></td><td><?= $e($r['kode_bayar'] ?? '-') ?></td><td><?= $e($r['tgl_wkt_byr'] ?? '-') ?></td><td><?= $e($r['jns_byr'] ?? '-') ?></td><td><?= $e($r['mtode_byr'] ?? '-') ?></td><td>Rp <?= number_format((int) ($r['nominal'] ?? 0), 0, ',', '.') ?></td><td><?= $e($r['sttus_byr'] ?? '-') ?></td><td><?php if (!empty($r['ft_byr'])): ?><a target="_blank" rel="noopener" href="<?= $e(base_url('uploads/bayar/' . rawurlencode(basename((string) $r['ft_byr'])))) ?>">Lihat bukti</a><?php else: ?>-<?php endif; ?></td></tr><?php endforeach; ?>
      </tbody></table></div></div>
    <?php if ($bolehLunas): ?>
      <div class="box box-success"><div class="box-header with-border"><h3 class="box-title">Catat Pelunasan</h3></div><form action="<?= $e(site_url('admin/pembayaran/pelunasan/proses/' . str_replace('/', '~', $kode))) ?>" method="post" enctype="multipart/form-data"><div class="box-body">
        <div class="alert alert-info">Nominal pelunasan otomatis menggunakan seluruh sisa kewajiban: <strong>Rp <?= number_format($sisa, 0, ',', '.') ?></strong>. Admin tidak mengisi nominal secara manual.</div>
        <div class="form-group"><label for="mtode_byr">Metode Pembayaran</label><select class="form-control" id="mtode_byr" name="mtode_byr" required><option value="">-- Pilih metode --</option><option value="tunai">Tunai</option><option value="transfer">Transfer</option></select></div>
        <div class="form-group" id="bukti-transfer-group" style="display:none"><label for="ft_byr">Bukti Transfer (JPEG/PNG/WEBP, maksimal 5 MB)</label><input class="form-control" type="file" id="ft_byr" name="ft_byr" accept="image/jpeg,image/png,image/webp"><p class="help-block">Bukti transfer yang diterima admin dari penyewa diunggah di sini.</p></div>
      </div><div class="box-footer"><button type="submit" class="btn btn-success" onclick="return confirm('Catat pelunasan sebesar Rp <?= number_format($sisa, 0, ',', '.') ?>?')"><i class="fa fa-check"></i> Simpan Pelunasan</button> <a class="btn btn-default" href="<?= $e(site_url('admin/pembayaran/pelunasan')) ?>">Kembali</a></div></form></div>
    <?php else: ?><div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php if (($d['status_sewa'] ?? '') === 'lunas' || $pernahLunas): ?>Transaksi ini sudah lunas.<?php else: ?>Transaksi belum memenuhi syarat pelunasan.<?php endif; ?> <a target="_blank" rel="noopener" href="<?= $e(site_url('admin/pembayaran/dokumen/' . str_replace('/', '~', $kode))) ?>">Lihat dokumen transaksi</a></div><?php endif; ?>
  </section>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var metode = document.getElementById('mtode_byr');
  var grup = document.getElementById('bukti-transfer-group');
  var bukti = document.getElementById('ft_byr');
  if (!metode || !grup) return;
  function aturBukti() { var transfer = metode.value === 'transfer'; grup.style.display = transfer ? 'block' : 'none'; if (bukti) bukti.required = transfer; }
  metode.addEventListener('change', aturBukti); aturBukti();
});
</script>
<?php require APPPATH . 'views/templates/footer.php'; ?>
