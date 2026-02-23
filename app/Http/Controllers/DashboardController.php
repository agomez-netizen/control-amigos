<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.dashboard');
    }

    public function scatter()
    {
        return view('dashboard.scatter');
    }

    public function public()
{
    // Si NO necesitas datos aún, quita la línea $data y compact
    // $data = $this->getDashboardData();

    return view('dashboard.public');
}
}
