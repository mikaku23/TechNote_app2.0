<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function admin()
    {
        return view('admin.index',[
            'menu' => 'dashboardAdmin',
            'title' => 'Admin Dashboard',
        ]);
    }

    public function mahasiswa()
    {
        return view('mhs.index',[
            'menu' => 'dashboardMhs',
            'title' => 'Mahasiswa Dashboard',
        ]);
    }
}
