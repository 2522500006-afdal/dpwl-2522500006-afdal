<?php
$detail = $detailPelunasan ?? [];
$riwayat = $detail['riwayat_pembayaran'] ?? [];
$kendaraan = $detail['daftar_kendaraan'] ?? [];
$e = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$total = (int) ($detail['total_transaksi'] ?? 0);
$terbayar = (int) ($detail['total_terbayar'] ?? 0);
$sisa = (int) ($detail['sisa_kewajiban'] ?? 0);
$kode = (string) ($detail['kode_sewa'] ?? '');
?>
<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Dokumen Transaksi <?= $e($kode) ?></title>
<style>
*{box-sizing:border-box}body{margin:0;padding:24px;background:#f2f2f2;color:#222;font:13px/1.55 Arial,Helvetica,sans-serif}.document{max-width:900px;margin:0 auto;padding:34px;background:#fff;border:1px solid #ddd}.document-header{text-align:center;border-bottom:2px solid #333;padding-bottom:18px;margin-bottom:22px}.document-header h1{font-size:22px;margin:0 0 5px}.document-header p{margin:0;color:#555}.meta{display:flex;justify-content:space-between;gap:20px;margin-bottom:20px}.section{margin:20px 0}.section h3{font-size:15px;border-bottom:1px solid #aaa;padding-bottom:6px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 24px}.item{display:flex;gap:8px}.item b{min-width:125px}table{width:100%;border-collapse:collapse;margin-top:8px}th,td{border:1px solid #bbb;padding:8px;text-align:left}th{background:#eee}.number{text-align:right;white-space:nowrap}.summary{width:360px;margin-left:auto}.summary td:last-child{text-align:right;font-weight:bold}.status{text-transform:uppercase;font-weight:bold}.signature{display:flex;justify-content:space-between;margin-top:55px;text-align:center}.signature div{width:220px}.actions{text-align:center;margin:0 auto 16px;max-width:900px}.actions button{border:0;border-radius:4px;background:#337ab7;color:#fff;padding:10px 18px;cursor:pointer}.muted{color:#666}@media print{body{background:#fff;padding:0}.document{border:0;max-width:none;padding:0}.actions{display:none}a{color:#222;text-decoration:none}thead{display:table-header-group}tr{page-break-inside:avoid}}
</style></head><body>
<div class="actions"><button onclick="window.print()"><i> </i> Cetak / Simpan sebagai PDF</button></div>
<main class="document">
<header class="document-header"><h1>DOKUMEN TRANSAKSI PENYEWAAN KENDARAAN</h1><p>BUKTI RINCIAN TRANSAKSI DAN PEMBAYARAN</p></header>
<div class="meta"><div><b>Kode Transaksi:</b> <?= $e($kode) ?><br><b>Status Transaksi:</b> <span class="status"><?= $e($detail['status_sewa'] ?? '-') ?></span></div><div><b>Tanggal Cetak:</b> <?= date('d-m-Y H:i:s') ?></div></div>
<section class="section"><h3>Identitas Penyewa</h3><div class="grid">
<div class="item"><b>Nama</b><span><?= $e($detail['nm_lngkp'] ?? '-') ?></span></div><div class="item"><b>Username</b><span><?= $e($detail['user_plg'] ?? '-') ?></span></div>
<div class="item"><b>NIK</b><span><?= $e($detail['nik'] ?? '-') ?></span></div><div class="item"><b>No. HP</b><span><?= $e($detail['no_hp'] ?? '-') ?></span></div>
<div class="item" style="grid-column:1/-1"><b>Alamat</b><span><?= $e($detail['almt_lngkp'] ?? '-') ?></span></div></div></section>
<section class="section"><h3>Informasi Sewa</h3><div class="grid">
<div class="item"><b>Tanggal Mulai</b><span><?= $e($detail['tgl_mulai'] ?? '-') ?></span></div><div class="item"><b>Tanggal Selesai</b><span><?= $e($detail['tgl_selesai'] ?? '-') ?></span></div><div class="item"><b>Waktu Pengambilan</b><span><?= $e($detail['wkt_ambil'] ?? '-') ?></span></div></div></section>
<section class="section"><h3>Rincian Kendaraan</h3><table><thead><tr><th>No</th><th>Nama Kendaraan</th><th>No. Plat</th><th class="number">Tarif/Hari</th><th class="number">Hari</th><th class="number">Subtotal</th></tr></thead><tbody>
<?php foreach ($kendaraan as $i=>$k): ?><tr><td><?= (int)$i+1 ?></td><td><?= $e($k['nm_mobil'] ?? '-') ?></td><td><?= $e($k['no_plat'] ?? '-') ?></td><td class="number">Rp <?= number_format((int)($k['hrg_sewa'] ?? 0),0,',','.') ?></td><td class="number"><?= (int)($k['n_hari'] ?? 0) ?></td><td class="number">Rp <?= number_format((int)($k['subtotal'] ?? 0),0,',','.') ?></td></tr><?php endforeach; ?>
</tbody></table></section>
<section class="section"><h3>Riwayat Pembayaran</h3><table><thead><tr><th>No</th><th>Kode Bayar</th><th>Tanggal</th><th>Jenis</th><th>Metode</th><th class="number">Nominal</th><th>Status</th></tr></thead><tbody>
<?php foreach ($riwayat as $i=>$r): ?><tr><td><?= (int)$i+1 ?></td><td><?= $e($r['kode_bayar'] ?? '-') ?></td><td><?= $e($r['tgl_wkt_byr'] ?? '-') ?></td><td><?= $e($r['jns_byr'] ?? '-') ?></td><td><?= $e($r['mtode_byr'] ?? '-') ?></td><td class="number">Rp <?= number_format((int)($r['nominal'] ?? 0),0,',','.') ?></td><td><?= $e($r['sttus_byr'] ?? '-') ?></td></tr><?php endforeach; ?>
</tbody></table></section>
<section class="section"><h3>Ringkasan Pembayaran</h3><table class="summary"><tbody><tr><td>Total Transaksi</td><td>Rp <?= number_format($total,0,',','.') ?></td></tr><tr><td>Total Pembayaran Valid</td><td>Rp <?= number_format($terbayar,0,',','.') ?></td></tr><tr><td>Sisa Kewajiban</td><td>Rp <?= number_format($sisa,0,',','.') ?></td></tr></tbody></table></section>
<div class="signature"><div><p>Penyewa,</p><br><br><br><b>( <?= $e($detail['nm_lngkp'] ?? '........................') ?> )</b></div><div><p>Admin,</p><br><br><br><b>( ................................ )</b></div></div>
<p class="muted" style="text-align:center;margin-top:35px">Dokumen ini dicetak dari sistem informasi penyewaan kendaraan.</p>
</main></body></html>
