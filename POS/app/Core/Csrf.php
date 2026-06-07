<?php

namespace App\Core;

class Csrf
{
    public static function token(string $key): string
    {
        if (!Session::has($key)) {
            Session::set($key, bin2hex(random_bytes(32)));
        }

        return Session::get($key);
    }

    public static function validate(string $key, ?string $token): bool
    {
        $sessionToken = Session::get($key);
        return $token !== null && is_string($sessionToken) && hash_equals($sessionToken, $token);
    }
}
