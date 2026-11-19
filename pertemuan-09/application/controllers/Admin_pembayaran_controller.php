<?php
require_once APPPATH . 'helpers/auth_helper.php';
require_once APPPATH . 'helpers/flash_helper.php';
require_once APPPATH . 'models/Pembayaran_model.php';

class Admin_pembayaran_controller extends Controller
{
    private Pembayaran_model $model;
    public function __construct() { $this->model = new Pembayaran_model(); }

    public function index(): void
    {
        require_admin_login();
        try {
            $this->view('admin/pembayaran_view', [
                'username' => $_SESSION['admin_username'],
                'pembayaran' => $this->model->getSemuaPembayaran(),
                'flash' => get_flash_message()
            ]);
        } catch (Throwable $e) {
            error_log('Daftar pembayaran admin gagal: ' . $e->getMessage());
            http_response_code(500); exit('Daftar pembayaran belum dapat ditampilkan.');
        }
    }

    public function verifikasi(): void
    {
        require_admin_login();
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405); header('Allow: POST'); exit('Method Not Allowed');
        }
        $kode = trim((string) ($_POST['kode_bayar'] ?? ''));
        $keputusan = trim((string) ($_POST['keputusan'] ?? ''));
        if ($kode === '' || !in_array($keputusan, ['valid', 'invalid'], true)) {
            set_flash_message('error', 'Data verifikasi tidak valid.');
            header('Location: ' . site_url('admin/pembayaran')); exit;
        }
        try {
            $this->model->verifikasi($kode, $keputusan);
            set_flash_message('success', $keputusan === 'valid' ? 'Pembayaran dinyatakan valid. Status transaksi telah disetujui.' : 'Pembayaran dinyatakan tidak valid. Status transaksi telah ditolak.');
        } catch (Throwable $e) {
            error_log('Verifikasi pembayaran gagal: ' . $e->getMessage());
            set_flash_message('error', 'Verifikasi gagal. Pastikan bukti sudah diunggah dan transaksi masih menunggu pemeriksaan.');
        }
        header('Location: ' . site_url('admin/pembayaran'));
        exit;
    }
}
