<?php
/**
 * WPSA Token Encryption
 * 
 * Encrypts and decrypts sensitive OAuth tokens using AES-256-CBC.
 * Uses WordPress AUTH_KEY and SECURE_AUTH_KEY as encryption key.
 * 
 * @package WPSA_Booking
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPSA_Token_Encryption {
    
    /**
     * Encryption method
     */
    private const METHOD = 'AES-256-CBC';
    
    /**
     * Get encryption key from WordPress constants
     * 
     * @return string 32-byte encryption key
     */
    private static function get_key() {
        // Combine AUTH_KEY and SECURE_AUTH_KEY
        $key_material = AUTH_KEY . SECURE_AUTH_KEY;
        
        // Hash to get exactly 32 bytes for AES-256
        return substr(hash('sha256', $key_material, true), 0, 32);
    }
    
    /**
     * Encrypt data
     * 
     * @param string $data Data to encrypt
     * @return string|false Base64-encoded encrypted data, or false on failure
     */
    public static function encrypt($data) {
        if (empty($data)) {
            return false;
        }
        
        try {
            // Generate random IV (16 bytes for AES-256-CBC)
            $iv = openssl_random_pseudo_bytes(16);
            
            // Encrypt data
            $encrypted = openssl_encrypt(
                $data,
                self::METHOD,
                self::get_key(),
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($encrypted === false) {
                error_log('WPSA Encryption: openssl_encrypt failed');
                return false;
            }
            
            // Combine IV and encrypted data
            $combined = $iv . $encrypted;
            
            // Base64 encode for storage
            return base64_encode($combined);
            
        } catch (Exception $e) {
            error_log('WPSA Encryption error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Decrypt data
     * 
     * @param string $encrypted_data Base64-encoded encrypted data
     * @return string|false Decrypted data, or false on failure
     */
    public static function decrypt($encrypted_data) {
        if (empty($encrypted_data)) {
            return false;
        }
        
        try {
            // Base64 decode
            $combined = base64_decode($encrypted_data, true);
            
            if ($combined === false) {
                error_log('WPSA Decryption: base64_decode failed');
                return false;
            }
            
            // Extract IV (first 16 bytes)
            $iv = substr($combined, 0, 16);
            
            // Extract encrypted data (rest)
            $ciphertext = substr($combined, 16);
            
            // Decrypt
            $decrypted = openssl_decrypt(
                $ciphertext,
                self::METHOD,
                self::get_key(),
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($decrypted === false) {
                error_log('WPSA Decryption: openssl_decrypt failed');
                return false;
            }
            
            return $decrypted;
            
        } catch (Exception $e) {
            error_log('WPSA Decryption error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test encryption/decryption
     * 
     * @return bool True if encryption works
     */
    public static function test() {
        $test_data = 'test_token_' . uniqid();
        
        $encrypted = self::encrypt($test_data);
        if ($encrypted === false) {
            return false;
        }
        
        $decrypted = self::decrypt($encrypted);
        
        return $decrypted === $test_data;
    }
}
