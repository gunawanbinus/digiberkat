<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EmployeeAccountController extends Controller
{
    public function index()
    {
        $baseUrl = rtrim(config('services.golang_api.url'), '/');

        $token = session('api_token');
        if (!$token) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $response = Http::withToken($token)->timeout(10)->get("$baseUrl/stats/employees");

        $employees = $response->successful() ? $response->json()['data'] : [];

        return view('employee.index', compact('employees'));
    }
}
