<?php
// ============================================================
// classes/Encryption.php
// AES-256-CBC Encryption & Decryption
// OOP Concept: Encapsulation (private key, private cipher)
// ============================================================
require_once __DIR__ . '/../config/config.php';

class Encryption {
    private string $key;
    private string $cipher = 'AES-256-CBC';

    public function __construct() {
        // Derive a 32-byte key from the config key using SHA-256
        $this->key = hash('sha256', ENCRYPTION_KEY, true);
    }

    /**
     * Encrypt a string value
     * Returns base64-encoded string: base64(iv::encrypted_data)
     */
    public function encrypt(?string $data): ?string {
        if ($data === null || $data === '') return $data;

        $ivLength  = openssl_cipher_iv_length($this->cipher);
        $iv        = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt($data, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) return null;

        // Store IV alongside encrypted data, separated by ::
        return base64_encode($iv . '::' . $encrypted);
    }

    /**
     * Decrypt a previously encrypted string
     */
    public function decrypt(?string $data): ?string {
        if ($data === null || $data === '') return $data;

        $decoded = base64_decode($data);
        if ($decoded === false) return $data; // Not encrypted, return as-is

        $ivLength = openssl_cipher_iv_length($this->cipher);

        // Check if data contains the separator
        $separatorPos = strpos($decoded, '::');
        if ($separatorPos === false) return $data;

        $iv        = substr($decoded, 0, $ivLength);
        $encrypted = substr($decoded, $ivLength + 2); // +2 for '::'

        $decrypted = openssl_decrypt($encrypted, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);

        return $decrypted !== false ? $decrypted : $data;
    }

    /**
     * Encrypt an associative array of fields
     * Usage: $enc->encryptFields($data, ['name','phone','email'])
     */
    public function encryptFields(array $data, array $fields): array {
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->encrypt($data[$field]);
            }
        }
        return $data;
    }

    /**
     * Decrypt an associative array of fields
     */
    public function decryptFields(array $data, array $fields): array {
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->decrypt($data[$field]);
            }
        }
        return $data;
    }

    /**
     * Decrypt a list (array of rows) for the given fields
     */
    public function decryptRows(array $rows, array $fields): array {
        return array_map(fn($row) => $this->decryptFields($row, $fields), $rows);
    }
}
