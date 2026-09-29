<?php
class Home extends Controller
{
    public function index(): void
    {
        $data = [
            'judul' => 'Fondasi MVC DPWL',
            'pesan' => 'Request telah melewati front controller, Router, Controller, dan View.'
        ];
        $this->view('home/index', $data);
    }

    public function info(string $topik = 'mvc'): void
    {
        $this->view('home/info', ['topik' => $topik]);
    }

    public function guru(string $id = '2522500006'): void
    {
        $data = [
            'title' => 'Detail Guru',
            'id'   => $id,
            'nama'  => 'Afdal Pramadeni',
            'kelas' => 'SI3A'
        ];
        $this->view('home/guru', $data);
    }
}