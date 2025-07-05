<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redirect;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    // URL API Golang - disimpan di config
    private $apiBaseUrl;
    protected $positions = [];

    public function __construct()
    {
        $this->apiBaseUrl = config('services.golang_api.url');

        // $this->loadPositions();
    }

    public function index()
    {
        return view('login');
        // return view('auth.login');
    }

    public function adminRegister()
    {
        return view('admin.register');
    }

    // public function employeeRegister()
    // {
    //     return view('employee.register', [
    //         'positions' => $this->positions
    //     ]);
    // }
    public function employeeRegister()
    {
        if (!isset($this->positions)) {
            $this->loadPositions();
        }

        return view('employee.register', [
            'positions' => $this->positions
        ]);
    }

    public function doAdminRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
            'secret_code' => 'required|string'
        ]);

        if ($validator->fails()) {
            return Redirect::back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $response = Http::post("{$this->apiBaseUrl}register/admin", [
                'username' => $request->email,
                'password' => $request->password,
                'secret_code' => $request->secret_code
            ]);

            return $this->handleAuthResponse($response, 'admin');
        } catch (\Exception $e) {
            return Redirect::back()
                ->withErrors(['message' => 'Failed to connect to authentication service: '.$e->getMessage()])
                ->withInput();
        }
    }
    /**
     * Load positions from API
     */
    protected function loadPositions()
    {
        try {
            $token = session('api_token');

            if (!$token) {
                \Log::error('Admin token not found in session');
                return;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token
            ])->get("{$this->apiBaseUrl}position");

            if ($response->successful()) {
                $this->positions = $response->json()['data'] ?? [];
            } else {
                \Log::error('Failed to load positions: ' . $response->body());
            }
        } catch (\Exception $e) {
            \Log::error('Failed to load positions: ' . $e->getMessage());
        }
    }
    /**
     * Handle employee registration
     */
    public function doEmployeeRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
            'position_name' => 'required|string'
        ]);

        if ($validator->fails()) {
            return Redirect::back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Make the API call but don't handle the response
            Http::post("{$this->apiBaseUrl}register/employee", [
                'username' => $request->email,
                'password' => $request->password,
                'position_name' => $request->position_name
            ]);

            // Return to register page with success message that will redirect
            return redirect()->route('employee.register')
                ->with([
                    'success' => 'Registrasi karyawan berhasil! Anda akan diarahkan ke halaman login dalam 4 detik...',
                    'delayed_redirect' => true,
                    'redirect_url' => route('login'),
                    'redirect_time' => 4000 // milliseconds
                ]);

        } catch (\Exception $e) {
            return Redirect::back()
                ->withErrors(['message' => 'Failed to connect to authentication service: '.$e->getMessage()])
                ->withInput();
        }
    }
    public function authenticate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'role' => 'required|in:admin,employee'
        ]);

        if ($validator->fails()) {
            return Redirect::back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $response = Http::post("{$this->apiBaseUrl}login", [
                'username' => $request->email,
                'password' => $request->password,
                'role' => $request->role
            ]);

            return $this->handleAuthResponse($response, $request->role);
        } catch (\Exception $e) {
            return Redirect::back()
                ->withErrors(['message' => 'Failed to connect to authentication service: '.$e->getMessage()])
                ->withInput();
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    /**
     * Handle authentication response from API
     */
    private function handleAuthResponse($response, $role)
    {
        $responseData = $response->json();

        if ($response->successful()) {
            // Validasi response structure
            if (!isset($responseData['token']) || !isset($responseData['user']['username'])) {
                return Redirect::back()
                    ->withErrors(['message' => 'Invalid API response structure'])
                    ->withInput();
            }

            try {
                $user = User::storeUserInSession($responseData);

                // Set session berdasarkan response
                session([
                    'api_token' => $responseData['token'],
                    'user_role' => $responseData['role'],
                    'user_data' => $responseData['user']
                ]);

                return redirect()->route($this->getDashboardRoute($responseData['role']))
                    ->with('success', $responseData['message'] ?? 'Login successful');

            } catch (\Exception $e) {
                return Redirect::back()
                    ->withErrors(['message' => 'Failed to process user data: '.$e->getMessage()])
                    ->withInput();
            }
        } else {
            $errorMessage = $this->getErrorMessage($responseData['error'] ?? null);

            return Redirect::back()
                ->withErrors(['message' => $errorMessage])
                ->withInput();
        }
    }
    /**
     * Get the appropriate dashboard route based on user role
     */
    private function getDashboardRoute(string $role): string
    {
        try {
            return match($role) {
                'admin' => Route::has('admin.dashboard') ? 'admin.dashboard' : 'dashboard',
                'employee' => Route::has('employee.dashboard') ? 'employee.dashboard' : 'dashboard',
                default => 'dashboard'
            };
        } catch (\Exception $e) {
            return 'dashboard'; // Fallback jika terjadi error apapun
        }
    }



    /**
     * Map API error messages to user-friendly messages
     */
    private function getErrorMessage($apiError)
    {
        $errorMessages = [
            'Username dan password wajib diisi' => 'Email and password are required',
            'Format username harus berupa email yang valid' => 'Invalid email format',
            'Username minimal 3 karakter' => 'Email must be at least 3 characters',
            'Password minimal 6 karakter' => 'Password must be at least 6 characters',
            'Username sudah terdaftar' => 'Email already registered',
            'Username tidak ditemukan' => 'Email not found',
            'Password salah' => 'Incorrect password',
            'Role tidak valid' => 'Invalid role selected',
            'Secret code tidak valid' => 'Invalid admin secret code'
        ];

        return $errorMessages[$apiError] ?? ($apiError ?: 'Authentication failed');
    }
    public static function isTokenExpired()
    {
        $token = session('api_token');

        if (!$token) {
            return true; // Tidak ada token, dianggap expired
        }

        // Misal: kamu menyimpan expired time di session saat login
        $expiresAt = session('token_expires_at');

        if (!$expiresAt) {
            return true; // Tidak ada informasi expired time
        }

        return now()->greaterThan($expiresAt); // Cek apakah sekarang sudah lewat dari waktu expired
    }

}
