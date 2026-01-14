<?php

// Set the reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ (E_NOTICE | E_DEPRECATED));

class Config
{
    public static function DB_NAME()
    {
        return self::get_env("DB_NAME", "zim_furniture_store");
    }
    public static function DB_PORT()
    {
        return self::get_env("DB_PORT", 3306);
    }
    public static function DB_USER()
    {
        return self::get_env("DB_USER", 'root');
    }
    public static function DB_PASSWORD()
    {
        return self::get_env("DB_PASSWORD", 'rootroot');
    }
    public static function DB_HOST()
    {
        return self::get_env("DB_HOST", '127.0.0.1');
    }
    public static function JWT_SECRET()
    {
        return self::get_env("JWT_SECRET", 'my_jwt_strong_secret_development_only_32_chars_min');
    }
    public static function get_env($name, $default)
    {
        return isset($_ENV[$name]) && trim($_ENV[$name]) != "" ? $_ENV[$name] : $default;
    }
}
