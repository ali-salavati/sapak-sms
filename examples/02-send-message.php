<?php

/**
 * Example 02: Send a Message and Check Its Status
 *
 * This demonstrates the full workflow:
 * 1. Sending a message using a DTO.
 * 2. Getting the Message ID from the response.
 * 3. Using that ID to check the message status.
 *
 * How to run:
 * 1. Make sure your .env file is set up (see 01-get-credit.php).
 * 2. Run: php examples/02-send-and-check-status.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Sapak\Sms\SapakClient;
use Sapak\Sms\DTOs\Requests\SendMessage;
use Sapak\Sms\DTOs\Responses\SentMessageStatus;
use Sapak\Sms\Exceptions\AuthenticationException;
use Sapak\Sms\Exceptions\ApiException;

// --- !! CONFIGURE THESE !! ---
$SENDER_NUMBER = '985000...'; // Your Sapak sender number
$RECIPIENT_NUMBER = '98912...'; // Your own mobile number for testing
// -----------------------------

try {
    // 1. Load environment variables
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
    $apiKey = $_ENV['SAPAK_API_KEY'] ?? null;

    if (!$apiKey || $SENDER_NUMBER === '985000...' || $RECIPIENT_NUMBER === '98912...') {
        throw new Exception(
            "Please configure SAPAK_API_KEY in .env and set \$SENDER_NUMBER and \$RECIPIENT_NUMBER in this file."
        );
    }

    $client = new SapakClient($apiKey);

    // 2. --- Send the Message ---
    echo "Attempting to send message...\n";

    $messageDto = new SendMessage(
        from: $SENDER_NUMBER,
        to: [$RECIPIENT_NUMBER],
        text: 'Sapak SDK Test Message / ' . date('H:i:s')
    );

    $sendResults = $client->messages()->send($messageDto);
    $messageId = $sendResults[0]->id;
    $initialStatus = $sendResults[0]->status;

    echo "[SUCCESS] Message sent.\n";
    echo "  -> Message ID: $messageId\n";
    echo "  -> Initial Status: $initialStatus (" . $sendResults[0]->getStatusText() . ")\n";
    echo "Waiting 5 seconds before checking status...\n";

    sleep(5); // Give the API time to process

    // 3. --- Check the Status ---
    echo "\nAttempting to check status for ID: $messageId\n";

    // Note: We use an array, even for one ID, as per our SDK design.
    $statusResults = $client->messages()->getStatuses([$messageId]);
    $finalStatus = $statusResults[0];

    echo "[SUCCESS] Status retrieved.\n";
    echo "  -> Final Status: $finalStatus->status (" . $finalStatus->getStatusText() . ")\n";

    if ($finalStatus->status === SentMessageStatus::STATUS_BLACKLISTED) {
        echo "  -> NOTE: The message was blacklisted. This is common for test numbers.\n";
    }


} catch (AuthenticationException $e) {
    echo "[AUTHENTICATION FAILED] \n";
    echo "Error: " . $e->getMessage() . "\n";
} catch (ApiException $e) {
    echo "[API ERROR] \n";
    echo "Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "[GENERAL ERROR] \n";
    echo "Error: " . $e->getMessage() . "\n";
}