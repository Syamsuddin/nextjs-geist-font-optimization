<?php
session_start();

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function is_admin() {
    return isset($_SESSION['level_name']) && $_SESSION['level_name'] === 'superadmin';
}

function is_operator() {
    return isset($_SESSION['level_name']) && $_SESSION['level_name'] === 'operator';
}

function require_admin() {
    check_login();
    if (!is_admin()) {
        http_response_code(403);
        echo "Akses ditolak. Hanya admin yang dapat mengakses halaman ini.";
        exit;
    }
}

function require_operator_or_admin() {
    check_login();
    if (!is_admin() && !is_operator()) {
        http_response_code(403);
        echo "Akses ditolak.";
        exit;
    }
}
?>
