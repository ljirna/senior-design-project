<?php

// Set the reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ (E_NOTICE | E_DEPRECATED));

class Config
{
    public static function DB_NAME()
    {
        return 'zim_furniture_store';
    }
    public static function DB_PORT()
    {
        return  3306;
    }
    public static function DB_USER()
    {
        return 'root';
    }
    public static function DB_PASSWORD()
    {
        return 'rootroot';
    }
    public static function DB_HOST()
    {
        return '127.0.0.1';
    }

    public static function JWT_SECRET()
    {
        // Prefer environment variable in production
        $env = getenv('JWT_SECRET');
        if ($env !== false) {
            if (strlen($env) < 32) {
                throw new \RuntimeException('JWT_SECRET environment variable is too short; use at least 32 characters (256 bits)');
            }
            return $env;
        }

        // Fallback for local/dev: use a hardcoded value only if long enough,
        // otherwise generate a secure random secret for this process.
        $fallback = 'my_jwt_strong_secret';
        if (strlen($fallback) < 32) {
            // Generate a secure, 64-hex (32-byte) string for development
            $fallback = bin2hex(random_bytes(32));
        }
        return $fallback;
    }
}
