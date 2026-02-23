<?php

namespace App\Http\Controllers;

abstract class Controller
{


public function public()
{
    // Si necesitas datos:
    //$data = $this->getDashboardData(); // si ya tienes lógica separada

    //return view('dashboard.public', compact('data'));
}


}


