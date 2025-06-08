<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function account()
    {
        $user = Auth::user(); // Mendapatkan data user yang sedang login
        return view('account', compact('user'));
    }
}
