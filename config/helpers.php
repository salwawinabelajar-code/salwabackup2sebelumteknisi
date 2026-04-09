<?php
// config/helpers.php

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
    }
}

if (!function_exists('isTeknisi')) {
    function isTeknisi() {
        return isset($_SESSION['role']) && $_SESSION['role'] == 'teknisi';
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit();
    }
}

if (!function_exists('generateInitials')) {
    function generateInitials($nama) {
        if (empty($nama)) return '?';
        $words = explode(' ', trim($nama));
        $initials = '';
        if (count($words) >= 2) {
            $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        } else {
            $initials = strtoupper(substr($words[0], 0, 2));
        }
        return $initials;
    }
}