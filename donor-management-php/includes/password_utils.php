<?php
/**
 * Password utility functions for secure password handling
 */

/**
 * Hash a password for storage
 * Uses PASSWORD_DEFAULT which is currently bcrypt with cost 10
 * 
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hash_password($password) {
    $options = [
        'cost' => 10  // Higher cost = more secure but slower
    ];
    return password_hash($password, PASSWORD_DEFAULT, $options);
}

/**
 * Verify a password against its hash
 * 
 * @param string $password Plain text password to verify
 * @param string $hash Stored hash to check against
 * @return bool True if password matches, false otherwise
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate a random password
 * 
 * @param int $length Length of password to generate
 * @return string Random password
 */
function generate_random_password($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=';
    $password = '';
    $max = strlen($chars) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }
    
    return $password;
}

/**
 * Check if a password meets minimum requirements
 * 
 * @param string $password Password to check
 * @return array Array with 'valid' boolean and 'message' string
 */
function validate_password($password) {
    $min_length = 8;
    $errors = [];
    
    if (strlen($password) < $min_length) {
        $errors[] = "Password harus minimal $min_length karakter";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password harus mengandung minimal 1 huruf besar";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password harus mengandung minimal 1 huruf kecil";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password harus mengandung minimal 1 angka";
    }
    
    if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
        $errors[] = "Password harus mengandung minimal 1 karakter spesial (!@#$%^&*()_+-=)";
    }
    
    return [
        'valid' => empty($errors),
        'message' => empty($errors) ? 'Password valid' : implode('. ', $errors)
    ];
}

/**
 * Check if a password needs rehashing
 * Useful when password hashing parameters change
 * 
 * @param string $hash Current password hash
 * @return bool True if password needs rehashing
 */
function needs_rehash($hash) {
    return password_needs_rehash($hash, PASSWORD_DEFAULT, ['cost' => 10]);
}
