<?php
// includes/crypto.php - AES-256-GCM encryption for sensitive text (the private mood notes).
// The secret key is created automatically the first time it is needed and saved in includes/mood.key.
// includes/.htaccess blocks browsers from downloading that file.
// IMPORTANT: if you delete mood.key, old encrypted notes can no longer be read.

function app_key(): string {
    static $key = null;
    if ($key !== null) { return $key; }

    $file = __DIR__ . '/mood.key';
    if (!is_file($file)) {
        file_put_contents($file, bin2hex(random_bytes(32)), LOCK_EX);
    }
    $key = hex2bin(trim(file_get_contents($file)));
    return $key;
}

// Returns base64 text: [12-byte IV][16-byte auth tag][encrypted bytes]
function encrypt_text(string $plain): string {
    $iv  = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', app_key(), OPENSSL_RAW_DATA, $iv, $tag);
    return base64_encode($iv . $tag . $cipher);
}

// Returns the original text, or null if the data is empty or has been tampered with.
function decrypt_text(?string $data): ?string {
    if ($data === null || $data === '') { return null; }
    $raw = base64_decode($data, true);
    if ($raw === false || strlen($raw) < 28) { return null; }
    $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', app_key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
    return $plain === false ? null : $plain;
}
