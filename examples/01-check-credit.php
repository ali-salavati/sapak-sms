<?php

/**
 * Example 01: Get Account Credit
 *
 * This is the simplest "Hello, World!" test for the SDK.
 * It connects to the API and retrieves your current account credit.
 *
 * How to run:
 * 1. Create a .env file in the *root* directory (e.g., sapak-sms-php/.env)
 * 2. Add your API key: SAPAK_API_KEY="YOUR_KEY"
 * 3. Run: php examples/01-get-credit.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Sapak\Sms\SapakClient;
use Sapak\Sms\Exceptions\AuthenticationException;
use Sapak\Sms\Exceptions\ApiException;

try {
    // 1. Load environment variables from the .env file in the root
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    $apiKey = $_ENV['SAPAK_API_KEY'] ?? null;
    if (!$apiKey) {
        throw new Exception("SAPAK_API_KEY not found in .env file.");
    }

    // 2. Instantiate the Client
    $client = new SapakClient($apiKey);

    // 3. Make the API call
    $creditDto = $client->account()->getCredit();

    echo "[SUCCESS] \n";
    echo "Your current credit is: " . $creditDto->credit . " Rials\n";

} catch (AuthenticationException $e) {
    echo "[AUTHENTICATION FAILED] \n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Please check your SAPAK_API_KEY in the .env file.\n";

} catch (ApiException $e) {
    echo "[API ERROR] \n";
    echo "Error: " . $e->getMessage() . "\n";

} catch (Exception $e) {
    echo "[GENERAL ERROR] \n";
    echo "Error: " . $e->getMessage() . "\n";
}