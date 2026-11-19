<?php
require_once APPPATH . 'models/Pembayaran_model.php';
require_once APPPATH . 'models/Transaksi_model.php';

class Pembayaran_controller extends Controller
{
    private Pembayaran_model $model;
    private Transaksi_model $transaksi;

    public function __construct()
    {
        $this->model = new Pembayaran_model();
        $this->transaksi = new Transaksi_model();
    }

    private function wajibPenyewa(): string
    {
        $user = trim((string) ($_SESSION['penyewa_user_plg'] ?? ''));
        if ($user === '') {
            $_SESSION['flash_penyewa_nav'] = 'Silakan login sebagai penyewa untuk melanjutkan pembayaran.';
            header('Location: ' . site_url('auth/login-penyewa'));
            exit;
        }
        return $user;
    }

    public function index(): void
    {
        $user = $this->wajibPenyewa();
        $kodeSewa = trim((string) ($_GET['kode_sewa'] ?? ''));
        if ($kodeSewa === '') { http_response_code(400); exit('Kode transaksi tidak ditemukan.'); }
        try {
            $pembayaran = $this->model->getPembayaranPenyewa($kodeSewa, $user);
            if (!$pembayaran) { http_response_code(404); exit('Data pembayaran tidak ditemukan atau bukan milik akun Anda.'); }
            $this->view('transaksi/pembayaran', [
                'pembayaran' => $pembayaran,
                'jumlahKeranjang' => $this->transaksi->getJumlahMobilKeranjang($user),
                'flash' => $_SESSION['flash_pembayaran'] ?? null
            ]);
            unset($_SESSION['flash_pembayaran']);
        } catch (Throwable $e) {
            error_log('Halaman pembayaran gagal: ' . $e->getMessage());
            http_response_code(500); exit('Halaman pembayaran belum dapat ditampilkan.');
        }
    }

    public function unggah(): void
    {
        $user = $this->wajibPenyewa();
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405); header('Allow: POST'); exit('Method Not Allowed');
        }
        $kodeSewa = trim((string) ($_POST['kode_sewa'] ?? ''));
        $kodeBayar = trim((string) ($_POST['kode_bayar'] ?? ''));
        if ($kodeSewa === '' || $kodeBayar === '') { http_response_code(400); exit('Data pembayaran tidak lengkap.'); }
        $data = $this->model->getPembayaranPenyewa($kodeSewa, $user);
        if (!$data || $data['kode_bayar'] !== $kodeBayar || $data['sttus_byr'] !== 'pending') {
            $_SESSION['flash_pembayaran'] = ['type' => 'error', 'message' => 'Pembayaran tidak ditemukan atau sudah diverifikasi.'];
            header('Location: ' . site_url('transaksi/pembayaran?kode_sewa=' . rawurlencode($kodeSewa))); exit;
        }
        if (empty($_FILES['ft_byr']) || ($_FILES['ft_byr']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['flash_pembayaran'] = ['type' => 'error', 'message' => 'Pilih file bukti pembayaran terlebih dahulu.'];
            header('Location: ' . site_url('transaksi/pembayaran?kode_sewa=' . rawurlencode($kodeSewa))); exit;
        }
        $file = $_FILES['ft_byr'];
        if (!is_uploaded_file($file['tmp_name']) || (int)$file['size'] < 1 || (int)$file['size'] > 5 * 1024 * 1024) {
            $_SESSION['flash_pembayaran'] = ['type' => 'error', 'message' => 'Ukuran file harus lebih dari 0 byte dan maksimal 5 MB.'];
            header('Location: ' . site_url('transaksi/pembayaran?kode_sewa=' . rawurlencode($kodeSewa))); exit;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime]) || @getimagesize($file['tmp_name']) === false) {
            $_SESSION['flash_pembayaran'] = ['type' => 'error', 'message' => 'Bukti harus berupa gambar JPEG, PNG, atau WEBP yang valid.'];
            header('Location: ' . site_url('transaksi/pembayaran?kode_sewa=' . rawurlencode($kodeSewa))); exit;
        }
        $dir = dirname(APPPATH) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'bayar' . DIRECTORY_SEPARATOR;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            $_SESSION['flash_pembayaran'] = ['type' => 'error', 'message' => 'Folder upload tidak dapat dibuat.'];
            header('Location: ' . site_url('transaksi/pembayaran?kode_sewa=' . rawurlencode($kodeSewa))); exit;
        }
        try { $filename = 'bukti_' . date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime]; }
        catch (Throwable $e) { $filename = ''; }
        if ($filename === '' || !move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            $_SESSION['flash_pembayaran'] = ['type' => 'error', 'message' => 'File bukti pembayaran gagal disimpan.'];
            header('Location: ' . site_url('transaksi/pembayaran?kode_sewa=' . rawurlencode($kodeSewa))); exit;
        }
        try {
            if (!$this->model->unggahBukti($kodeBayar, $user, $filename)) {
                @unlink($dir . $filename);
                throw new RuntimeException('Pembayaran tidak dapat diperbarui.');
            }
            if (!empty($data['ft_byr'])) @unlink($dir . basename($data['ft_byr']));
            $_SESSION['flash_pembayaran'] = ['type' => 'success', 'message' => 'Bukti pembayaran berhasil dikirim dan menunggu verifikasi admin.'];
        } catch (Throwable $e) {
            @unlink($dir . $filename);
            error_log('Unggah bukti bayar gagal: ' . $e->getMessage());
            $_SESSION['flash_pembayaran'] = ['type' => 'error', 'message' => 'Bukti pembayaran belum berhasil disimpan. Silakan coba lagi.'];
        }
        header('Location: ' . site_url('transaksi/pembayaran?kode_sewa=' . rawurlencode($kodeSewa)));
        exit;
    }
}
