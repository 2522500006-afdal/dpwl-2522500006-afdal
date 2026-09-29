# pertemuan-02
1. Tujuan Praktikum [Jelaskan tujuan P2 dengan kalimat sendiri.] 
jawaban:
Tujuan praktikum P2 adalah memahami dan membangun dasar aplikasi web menggunakan MVC, serta mempelajari struktur direktori, routing, Front Controller, dan alur request-response.
 2. Struktur Direktori [Tampilkan tree struktur P2 dan jelaskan fungsi setiap bagian.]
jawaban:
```text
pertemuan-02/
├── application/
│   ├── config/
│   │   ├── config.php       -> Mengatur konfigurasi dasar aplikasi, seperti Base URL
│   │   └── routes.php       -> Mengatur pemetaan URL ke Controller
│   ├── controllers/
│   │   └── Home.php         -> Menangani logika request dan memanggil View
│   ├── helpers/
│   │   └── url_helper.php   -> Menyediakan fungsi bantuan base_url() dan site_url()
│   └── views/
│       └── home/
│           ├── guru.php    -> View untuk menampilkan informasi guru
│           ├── index.php   -> View untuk halaman utama
│           └── info.php    -> View untuk menampilkan informasi routing
├── assets/
│   └── css/
│       └── app.css         -> Mengatur desain dan tampilan halaman web
├── system/
│   └── core/
│       ├── Controller.php  -> Base Controller untuk fungsi umum
│       └── router.php      -> Membaca URL dan menentukan Controller
├── .gitkeep                -> Menjaga folder kosong agar dapat dilacak Git
├── index.php               -> Front Controller atau pintu masuk utama aplikasi
└── README.md               -> Dokumentasi proyek dan cara penggunaan
```
3. Front controller
[Jelaskan peran index.php sebagai satu titik masuk aplikasi.]
jawaban:
index.php menerima request, lalu Router mencocokkan URL dengan routes.php dan mengarahkannya ke Home::info("routing"). Selanjutnya, Controller memanggil View untuk menampilkan halaman.
4. Routing dan Pemetaan URL 
jawaban:
| URL/Route | Controller | Method | Parameter | View |
|---|---|---|---|---|
| / | Home | index | - | home/index.php |
| home/index | Home | index | - | home/index.php |
| home/info/mvc | Home | info | mvc | home/info.php |
| info/routing | Home | info | routing | home/info.php |
| guru/(:num) | Home | guru | $1 | Home/guru.php |

**Penjelasan Pemetaan Rute Modifikasi ATM:**

Route (`guru/(:num)`):Menangkap permintaan URL yang diawali kata `guru/` dan diikuti oleh angka variabel `(:num)` (misalnya NIK `1901071010060006`).

Controller (`Home`):Menunjuk ke kelas `Home` pada berkas `application/controllers/Home.php`.

Method (`guru`): Mengeksekusi fungsi/method `guru()` di dalam Controller `Home`.

Parameter (`$1`):Nilai angka NIK dari URL ditangkap oleh wildcard `(:num)` dan dikirim sebagai argumen ke method `guru($nik)`.
(`home/guru.php`):Controller mengolah data profil (NIK: 1901071010060006 Nama: Afdal pramadeni) 

lalu memuat tampilan akhir pada file View `home/guru.php`.
5. Base URL dan Helper
jawaban:
- *base_url(): Berfungsi untuk menghasilkan URL dasar (*root URL) proyek yang mengarah ke lokasi folder atau file fisik statis di direktori publik.
  - *Contoh Penggunaan P2:* Memanggil file stylesheet CSS pada file View (application/views/home/index.php):
    php
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css'); ?>">
    

- *site_url()*: Berfungsi untuk membentuk URL navigasi internal aplikasi yang terintegrasi dengan Front Controller (index.php) dan sistem routing.
  - *Contoh Penggunaan P2:* Membentuk link navigasi rute pada View:
    php
    <!-- Navigasi ke rute kustom -->
    <a href="<?= site_url('info/routing'); ?>">Uji custom route</a>

    <!-- Navigasi kembali ke beranda -->
    <a href="<?= site_url(); ?>">Kembali ke Beranda</a>
    ```
6. Alur Request-response Jelaskan dua alur berikut:  1. Alur eksekusi aktual P2: Browser → index.php → Router → Controller → View → Response. 2. Posisi Model dalam arsitektur MVC lengkap: Browser → index.php → Router → Controller → Model → basis data/data → Model → Controller → View → Response.
jawaban:
1.Workspace dpwl-nim
Berfungsi sebagai tempat untuk mengembangkan, mengimplementasikan, dan menguji aplikasi MVC selama praktikum P2. Folder ini digunakan sebagai lingkungan kerja lokal yang dapat dijalankan melalui Laragon dan diakses menggunakan localhost.
Contoh penggunaan P2:
Membuat struktur folder MVC, mengatur Front Controller, routing, Controller, View, Base URL, dan Helper di dalam folder dpwl-nim.
2.Repositori dpwl-nim-nama
Berfungsi sebagai tempat penyimpanan lokal Git untuk menampung snapshot hasil pengerjaan setiap pertemuan sebelum dikirim (push) ke repositori GitHub. Repositori ini digunakan untuk mencatat perkembangan proyek melalui riwayat commit.
Contoh penggunaan P2:
Menyalin hasil pengerjaan P2 ke folder pertemuan-02, kemudian menjalankan perintah Git untuk melakukan add, commit, dan push ke GitHub.
Perbedaan:
Workspace dpwl-nim digunakan untuk mengembangkan dan menguji aplikasi secara langsung, sedangkan repositori dpwl-nim-nama digunakan untuk menyimpan, melacak, dan mendokumentasikan perkembangan kode menggunakan Git sebelum disinkronkan ke GitHub.
7. Hasil Pengujian dan Debugging Catat skenario pengujian valid dan tidak valid beserta hasilnya. Jika ditemukan kesalahan selama implementasi, dokumentasikan sekurang-kurangnya satu proses debugging yang memuat:  Gejala → Penyebab → Perbaikan → Hasil Uji Ulang  Jika seluruh implementasi langsung berjalan sesuai hasil yang diharapkan, jelaskan hasil pemeriksaan sintaks dan pengujian yang telah dilakukan. 
jawaban:
1.Front Controller (index.php)
Berfungsi sebagai satu titik masuk (single entry point) bagi setiap request yang diterima aplikasi sebelum diteruskan ke proses routing.
Konsep serupa pada CI3: index.php yang menjadi pintu masuk utama aplikasi CodeIgniter 3.
2.Router (system/core/Router.php)
Berfungsi untuk membaca URL, mencocokkan route, menentukan Controller dan method yang akan dijalankan, serta meneruskan parameter dari URL.
Konsep serupa pada CI3: Sistem routing bawaan CI3 yang mengatur pemetaan URL ke Controller dan method.
3.Routes (application/config/routes.php)
Berfungsi untuk mengatur pemetaan URL atau route ke Controller, method, dan parameter tertentu.
Konsep serupa pada CI3: application/config/routes.php yang digunakan untuk mendefinisikan route aplikasi.
4.Base Controller (system/core/Controller.php)
Berfungsi sebagai class induk yang menyediakan fungsi umum bagi Controller aplikasi, seperti method view() untuk memanggil View.
Konsep serupa pada CI3: CI_Controller yang menjadi class induk bagi Controller aplikasi.
5.Controller Aplikasi (application/controllers/Home.php)
Berfungsi untuk menerima proses dari Router, mengelola alur aplikasi, menyiapkan data, dan menentukan View yang akan ditampilkan.
Konsep serupa pada CI3: Controller yang berada di dalam folder application/controllers/.
6.URL Helper (application/helpers/url_helper.php)
Berfungsi menyediakan fungsi yang dapat digunakan kembali untuk membentuk URL dasar dan URL navigasi, seperti base_url() dan site_url().
Konsep serupa pada CI3: URL Helper bawaan CI3 yang menyediakan fungsi pembentuk URL aplikasi.
7.Configuration (application/config/config.php)
Berfungsi untuk menyimpan pengaturan dasar aplikasi, seperti base_url dan index_page.
Konsep serupa pada CI3: File konfigurasi application/config/config.php yang digunakan untuk mengatur konfigurasi aplikasi.
8.View (application/views/)
Berfungsi untuk menampilkan antarmuka aplikasi dalam bentuk HTML berdasarkan data yang disiapkan oleh Controller.
Konsep serupa pada CI3: View yang disimpan di dalam folder application/views/.
8.  Bukti Tangkapan Layar Sisipkan gambar yang relevan dari folder dokumentasi/ dengan perintah:
jawaban:
 Gambar 1. Hasil Pengujian Halaman Utama 
![Gambar 1](dokumentasi/gambar1.jpg)   ### Gambar 2. Hasil Pengujian Custom Route 
![Gambar 2](dokumentasi/gambar2.jpg) 
Gambar 3. Data Profil Guru 
![Gambar 3](dokumentasi/gambar3.jpg)
9. Kesimpulan P2 Jelaskan apa yang sudah dapat dilakukan kerangka MVC dan apa yang baru akan ditambahkan pada P3.
jawaban:
 Pada P2, kerangka MVC sudah dapat menjalankan Front Controller, routing URL, Controller, dan View untuk menampilkan halaman sesuai permintaan pengguna. Selain itu, base_url() dan site_url() sudah dapat digunakan untuk mengatur URL aplikasi.
Pada P3, kerangka ini akan dikembangkan dengan menambahkan Model, koneksi basis data, MySQLi/prepared statement, autentikasi, sesi, kontrol akses, dan integrasi AdminLTE