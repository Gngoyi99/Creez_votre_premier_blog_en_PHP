<?php
namespace Blog\Twig\Utils;

class Validator {

    public static function validateNameAndSurname($value) {
        $regex = '/^[a-zA-ZÀ-ÖØ-öø-ÿ\s\'\-]+$/';
        return preg_match($regex, $value);
    }

    public static function validateUsername($username) {
        $usernameRegex = '/^[a-zA-Z0-9_]+$/';
        return preg_match($usernameRegex, $username);
    }

    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validatePassword($password) {
        // Password must be at least 8 characters long, with at least one uppercase letter, one digit, and one special character
        $passwordRegex = '/^(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{8,}$/';
        return preg_match($passwordRegex, $password);
    }

    public static function validateMaxLength(string $string, int $max): bool {
        return !empty($string) && strlen($string) <= $max;
    }

    public static function validateContent($content) {
        return !empty($content);
    }

    public static function sanitizeString($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    public static function decodeString($string) {
        return htmlspecialchars_decode($string, ENT_QUOTES);
    }
}
