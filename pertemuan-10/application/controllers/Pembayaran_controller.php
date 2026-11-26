<?php
require_once APPPATH . 'models/Pembayaran_model.php';
require_once APPPATH . 'models/Transaksi_model.php';
require_once APPPATH . 'helpers/auth_helper.php';

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
        try {
            $jumlahKeranjang = $this->transaksi->getJumlahMobilKeranjang($user);
            $flash = $_SESSION['flash_pembayaran'] ?? null;
            unset($_SESSION['flash_pembayaran']);

            // Tanpa kode_sewa, tampilkan daftar seluruh pembayaran milik penyewa.
            if ($kodeSewa === '') {
                $daftarPembayaran = $this->model->getDaftarPembayaranPenyewa($user);
                $this->view('transaksi/daftar_pembayaran', [
                    'daftarPembayaran' => $daftarPembayaran,
                    'jumlahKeranjang' => $jumlahKeranjang,
                    'flash' => $flash
                ]);
                return;
            }

            $pembayaran = $this->model->getPembayaranPenyewa($kodeSewa, $user);
            if (!$pembayaran) { http_response_code(404); exit('Data pembayaran tidak ditemukan atau bukan milik akun Anda.'); }
            $this->view('transaksi/pembayaran', [
                'pembayaran' => $pembayaran,
                'jumlahKeranjang' => $jumlahKeranjang,
                'flash' => $flash
            ]);
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

    /** Memastikan method HTTP sesuai kebutuhan endpoint. */
    private function wajibMetode(string $metode): void
    {
        $actual = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($actual !== strtoupper($metode)) {
            http_response_code(405);
            header('Allow: ' . strtoupper($metode));
            exit('Method Not Allowed');
        }
    }

    /** Pola Post-Redirect-Get dan notifikasi untuk halaman admin. */
    private function redirectDenganPesan(string $tujuan, string $tipe, string $pesan): never
    {
        $_SESSION['flash_pembayaran'] = ['type' => $tipe, 'message' => $pesan];
        header('Location: ' . site_url($tujuan));
        exit;
    }

    /** Validasi dan penyimpanan bukti transfer admin (P10). */
    private function simpanFileBukti(string $field): array
    {
        if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Pilih file bukti transfer terlebih dahulu.'];
        }
        $file = $_FILES[$field];
        if (!is_uploaded_file($file['tmp_name']) || (int) $file['size'] < 1 || (int) $file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'error' => 'Ukuran bukti harus lebih dari 0 byte dan maksimal 5 MB.'];
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime]) || @getimagesize($file['tmp_name']) === false) {
            return ['success' => false, 'error' => 'Bukti harus berupa gambar JPEG, PNG, atau WEBP yang valid.'];
        }
        $dir = dirname(APPPATH) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'bayar' . DIRECTORY_SEPARATOR;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['success' => false, 'error' => 'Folder uploads/bayar tidak dapat dibuat.'];
        }
        try { $filename = 'ft_byr_' . time() . '_' . bin2hex(random_bytes(7)) . '.' . $allowed[$mime]; }
        catch (Throwable $e) { return ['success' => false, 'error' => 'Nama file bukti gagal dibuat.']; }
        if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            return ['success' => false, 'error' => 'File bukti transfer gagal disimpan.'];
        }
        return ['success' => true, 'filename' => $filename];
    }

    private function hapusBuktiBaru(string $namaFile): void
    {
        $namaFile = basename($namaFile);
        $path = dirname(APPPATH) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'bayar' . DIRECTORY_SEPARATOR . $namaFile;
        if (is_file($path)) @unlink($path);
    }

    /** P10: daftar transaksi yang siap dilunasi. */
    public function pelunasanIndex(): void
    {
        require_admin_login();
        $this->wajibMetode('GET');
        try {
            $daftarPelunasan = $this->model->getDaftarPelunasan();
            $flash = $_SESSION['flash_pembayaran'] ?? null;
            unset($_SESSION['flash_pembayaran']);
            $this->view('admin/pelunasan', [
                'username' => $_SESSION['admin_username'],
                'daftarPelunasan' => $daftarPelunasan,
                'flash' => $flash
            ]);
        } catch (Throwable $e) {
            error_log('Daftar pelunasan gagal ditampilkan: ' . $e->getMessage());
            http_response_code(500);
            exit('Daftar transaksi pelunasan belum dapat ditampilkan. Periksa log aplikasi.');
        }
    }

    /** P10: detail transaksi dan riwayat pembayaran. */
    public function pelunasanDetail(string $kodeSewa = ''): void
    {
        require_admin_login();
        $this->wajibMetode('GET');
        $kodeSewa = trim(str_replace('~', '/', $kodeSewa));
        if ($kodeSewa === '') { http_response_code(400); exit('Kode transaksi sewa wajib tersedia.'); }
        try {
            $detailPelunasan = $this->model->getDetailPelunasan($kodeSewa);
            if ($detailPelunasan === null) { http_response_code(404); exit('Transaksi sewa tidak ditemukan.'); }
            $flash = $_SESSION['flash_pembayaran'] ?? null;
            unset($_SESSION['flash_pembayaran']);
            $this->view('admin/pelunasan_detail', [
                'username' => $_SESSION['admin_username'],
                'detailPelunasan' => $detailPelunasan,
                'flash' => $flash
            ]);
        } catch (Throwable $e) {
            error_log('Detail pelunasan gagal ditampilkan: ' . $e->getMessage());
            http_response_code(500);
            exit('Detail transaksi pelunasan belum dapat ditampilkan.');
        }
    }

    /** P10: proses pelunasan tunai atau transfer yang dicatat admin. */
    public function prosesPelunasan(string $kodeSewa = ''): void
    {
        require_admin_login();
        $this->wajibMetode('POST');
        $kodeSewa = trim(str_replace('~', '/', $kodeSewa));
        $metodeBayar = trim((string) ($_POST['mtode_byr'] ?? ''));
        $tujuan = 'admin/pembayaran/pelunasan/' . str_replace('/', '~', $kodeSewa);
        if ($kodeSewa === '') { http_response_code(400); exit('Kode transaksi sewa wajib tersedia.'); }
        if (!in_array($metodeBayar, ['tunai', 'transfer'], true)) {
            $this->redirectDenganPesan($tujuan, 'error', 'Metode pembayaran harus tunai atau transfer.');
        }
        $namaFileBukti = null;
        if ($metodeBayar === 'transfer') {
            $upload = $this->simpanFileBukti('ft_byr');
            if (!$upload['success']) $this->redirectDenganPesan($tujuan, 'error', $upload['error']);
            $namaFileBukti = (string) $upload['filename'];
        }
        try {
            $kodeBayar = $this->model->simpanPelunasan($kodeSewa, $metodeBayar, $namaFileBukti);
            $this->redirectDenganPesan($tujuan, 'success', 'Pelunasan berhasil dicatat dengan kode ' . $kodeBayar . '. Status pembayaran valid dan transaksi lunas.');
        } catch (InvalidArgumentException $e) {
            if ($namaFileBukti !== null) $this->hapusBuktiBaru($namaFileBukti);
            $this->redirectDenganPesan($tujuan, 'error', $e->getMessage());
        } catch (Throwable $e) {
            error_log('Proses pelunasan gagal: ' . $e->getMessage());
            if ($namaFileBukti !== null) $this->hapusBuktiBaru($namaFileBukti);
            $this->redirectDenganPesan($tujuan, 'error', 'Pelunasan gagal. Pastikan transaksi masih berstatus disetujui dan belum pernah dilunasi.');
        }
    }

    /** P10: dokumen transaksi untuk dicetak atau disimpan sebagai PDF. */
    public function dokumenTransaksi(string $kodeSewa = ''): void
    {
        require_admin_login();
        $this->wajibMetode('GET');
        $kodeSewa = trim(str_replace('~', '/', $kodeSewa));
        if ($kodeSewa === '') { http_response_code(400); exit('Kode transaksi sewa wajib tersedia.'); }
        try {
            $detailPelunasan = $this->model->getDetailPelunasan($kodeSewa);
            if ($detailPelunasan === null) { http_response_code(404); exit('Data transaksi tidak ditemukan.'); }
            $this->view('admin/dokumen_transaksi', ['detailPelunasan' => $detailPelunasan]);
        } catch (Throwable $e) {
            error_log('Dokumen transaksi gagal ditampilkan: ' . $e->getMessage());
            http_response_code(500);
            exit('Dokumen transaksi belum dapat ditampilkan.');
        }
    }

}
