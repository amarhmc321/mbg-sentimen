<?php

class Security
{
    public static function clean($text)
    {
        return htmlspecialchars(trim($text),ENT_QUOTES,'UTF-8');
    }

    public static function hashPassword($password)
    {
        return password_hash($password,PASSWORD_BCRYPT);
    }

    public static function verifyPassword($password,$hash)
    {
        return password_verify($password,$hash);
    }
}