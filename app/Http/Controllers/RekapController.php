<?php

namespace App\Http\Controllers;

use App\Models\rekap;

class RekapController extends Controller
{

    public function index()
    {
        $rekaps = rekap::latest('rekap_date')
            ->paginate(10);


        return view('admin.rekap.index', [

            'menu' => 'rekap',

            'rekaps' => $rekaps,

        ]);
    }



    public function show(rekap $rekap)
    {

        return view('admin.rekap.show', [

            'menu' => 'rekap',

            'rekap' => $rekap,

        ]);
    }
}
