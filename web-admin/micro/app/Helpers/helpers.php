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

