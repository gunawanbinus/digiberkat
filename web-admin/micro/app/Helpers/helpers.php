<?php

if (!function_exists('currentUser')) {
    function currentUser($key = null)
    {
        $user = session('user');

        if (is_null($user)) {
            return null;
        }

        if ($key) {
            return $user[$key] ?? null;
        }

        return (object) $user; // bisa akses ->username, ->name, dll
    }
}

// ambil data dari session yang diatur di User.php lihat dibawah ini temukan di User.php

// public static function storeUserInSession(array $apiResponse): void
// {
//     $token = $apiResponse['token'];
//     $expiryTimestamp = self::getTokenExpiryFromJWT($token);

//     session([
//         'user' => [
//             'id' => $apiResponse['user']['id'],
//             'username' => $apiResponse['user']['username'],
//             'role' => $apiResponse['role'], // "admin" or "employee"
//         ],
//         'api_token' => $token,
//         'token_expires_at' => $expiryTimestamp
//             ? Carbon::createFromTimestamp($expiryTimestamp)
//             : now()->addSeconds(config('auth.token_lifetime', 86400))
//     ]);
// }

