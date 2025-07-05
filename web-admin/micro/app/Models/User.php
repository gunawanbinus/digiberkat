<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon; // Add this import
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'username',
        'email',
        'password',
        'role',
        'api_token',
        'token_expires_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'token_expires_at' => 'datetime',
    ];

    /**
     * Simpan data user dari response API Golang
     */


/**
 * Dekode JWT untuk mendapatkan expiry time
 */
public static function getTokenExpiryFromJWT(string $token): ?int
{
    try {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        if (isset($payload['exp'])) {
            return $payload['exp']; // Return timestamp langsung, bukan selisih
        }

        return null;
    } catch (\Exception $e) {
        return null;
    }
}

public static function storeUserInSession(array $apiResponse): void
{
    $token = $apiResponse['token'];
    $expiryTimestamp = self::getTokenExpiryFromJWT($token);

    session([
        'user' => [
            'id' => $apiResponse['user']['id'],
            'username' => $apiResponse['user']['username'],
            'role' => $apiResponse['role'], // "admin" or "employee"
        ],
        'api_token' => $token,
        'token_expires_at' => $expiryTimestamp
            ? Carbon::createFromTimestamp($expiryTimestamp)
            : now()->addSeconds(config('auth.token_lifetime', 86400))
    ]);
}



    /**
     * Cek apakah token masih valid
     */
    public function isTokenValid(): bool
    {
        return $this->api_token &&
               $this->token_expires_at &&
               $this->token_expires_at->isFuture();
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'username';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->username;
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string|null
     */
    public function getRememberToken()
    {
        return $this->remember_token;
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string|null  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }
    // app/Helpers/AuthHelper.php
    public static function isTokenExpired(): bool
    {
        return !session('token_expires_at') || now()->gt(session('token_expires_at'));
    }

}

