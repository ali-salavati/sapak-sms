# Sapak SMS PHP SDK

A modern, clean, and opinionated PHP SDK for interacting with the Sapak SMS API.

This SDK is built with developers in mind, focusing on clean code principles, strong typing, and a safe, ergonomic developer experience.

> **Note:** This is an unofficial SDK.  
> The official Sapak API documentation can be found at: [https://docs.sapak.me/](https://docs.sapak.me/)

---

## Table of Contents

- [Features](#features)  
- [Installation](#installation)  
- [Quick Start: Instantiation](#quick-start-instantiation)  
- [Error Handling](#error-handling)  
- [Usage Examples](#usage-examples)  
  - [A. Get Account Credit](#a-get-account-credit)  
  - [B. Send Message (One-to-Many)](#b-send-message-one-to-many)  
  - [C. Send Messages (Peer-to-Peer)](#c-send-messages-peer-to-peer)  
  - [D. Get Message Statuses (The Safe Way)](#d-get-message-statuses-the-safe-way)  
  - [E. Find Received Messages (Smart Date Handling)](#e-find-received-messages-smart-date-handling)  
- [Testing](#testing)  

---

## Features

- **Clean, Modern Interface:** Uses typed DTOs (Data Transfer Objects) instead of messy arrays.  
- **Opinionated Design:** Actively prevents unsafe operations (like N+1 queries).  
- **Smart Date Handling:** Accepts standard DateTime objects and automatically handles the complex Jalali conversion required by the API.  
- **Robust Error Handling:** Throws custom, catchable exceptions (`ValidationException`, `AuthenticationException`) instead of generic Guzzle errors.  
- **Helper Methods:** Provides "value-add" features, like constants for status codes (`STATUS_DELIVERED`) and text helpers (`getStatusText()`).  
- **Fully Tested:** High test coverage to ensure reliability.  

---

## Installation

Install the package via Composer:

```bash
composer require salavati/sapak-sms-php
```

---

## Quick Start: Instantiation

You only need your API key to get started.

```php
use Sapak\Sms\SapakClient;

$apiKey = 'YOUR_API_KEY_HERE';
$client = new SapakClient($apiKey);
```

---

## Error Handling

This SDK abstracts all HTTP errors into specific exceptions. Always wrap your calls in a `try...catch` block.

```php
use Sapak\Sms\Exceptions\ValidationException;
use Sapak\Sms\Exceptions\AuthenticationException;
use Sapak\Sms\Exceptions\ApiException;

try {
    // Make an API call, e.g., $client->messages()->send(...)
    
} catch (ValidationException $e) {
    // 400 Bad Request or 422 Unprocessable
    // The request was malformed (e.g., missing 'text').
    echo "Validation Error: " . $e->getMessage();

} catch (AuthenticationException $e) {
    // 401 Unauthorized or 403 Forbidden
    // Your API key is likely invalid.
    echo "Auth Error: " . $e->getMessage();

} catch (ApiException $e) {
    // 500, 404, 429, or other generic errors
    echo "API Error: " . $e->getMessage();
}
```

---

## Usage Examples

### A. Get Account Credit

Access account information via the `account()` resource.

```php
// Returns a DTO, not a raw number
$creditDto = $client->account()->getCredit();

echo "Your credit is: " . $creditDto->credit . " Rials";
```

### B. Send Message (One-to-Many)

Use the `SendMessage` DTO to prepare your request. This provides client-side validation.

```php
use Sapak\Sms\DTOs\Requests\SendMessage;

$message = new SendMessage(
    from: '985000...',
    to: ['98912...', '98913...'],
    text: 'This is a test message.'
);

// You can also schedule it (must be an ATOM/RFC3339 string)
$futureDate = (new \DateTime('+10 minutes'))->format(\DateTime::ATOM);
$scheduledMessage = new SendMessage(
    from: '985000...',
    to: ['98912...'],
    text: 'A scheduled message.',
    sendAt: $futureDate
);

$results = $client->messages()->send($message);

echo "Message submitted. ID: " . $results[0]->id;
echo "Status code: " . $results[0]->status;
```

### C. Send Messages (Peer-to-Peer)

Create an array of `SendPeerToPeer` DTOs.

```php
use Sapak\Sms\DTOs\Requests\SendPeerToPeer;

$p2p_messages = [
    new SendPeerToPeer(
        sender: '985000...',
        recipient: '98912...',
        message: 'Hello, User 1.'
    ),
    new SendPeerToPeer(
        sender: '985000...',
        recipient: '98935...',
        message: 'Hello, User 2.'
    )
];

$results = $client->messages()->sendPeerToPeer($p2p_messages);
```

### D. Get Message Statuses (The Safe Way)

To prevent N+1 API requests, this SDK only supports checking statuses in a batch. If you need to check only one ID, pass it as an array: `getStatuses([12345])`.

```php
use Sapak\Sms\DTOs\Responses\SentMessageStatus;

$messageIds = [12345, 12346, 12347];

$statuses = $client->messages()->getStatuses($messageIds);

foreach ($statuses as $status) {
    echo "ID: " . $status->id . " has status code: " . $status->status . "\n";
    
    // Use the built-in constants for safe checking (No Magic Numbers!)
    if ($status->status === SentMessageStatus::STATUS_DELIVERED) {
        echo "Message {$status->id} was delivered!\n";
    }
    
    // Use the helper method for a human-readable text
    echo "Meaning: " . $status->getStatusText() . "\n";
}
```

### E. Find Received Messages (Smart Date Handling)

Pass standard `DateTimeInterface` objects. The SDK handles the complex Jalali conversion for you.

> **Note on Date Handling:**  
> The Sapak API requires a non-standard Jalali string format (`YYYY-MM-DD HH:mm:ss`). Instead of forcing you to handle this, the SDK does the work. It accepts standard PHP `DateTimeInterface` objects (Gregorian) and automatically converts them to the required Jalali format for the request. It also converts the Jalali date strings in the response back to standard `DateTimeImmutable` objects.

```php
use Sapak\Sms\DTOs\Requests\FindMessages;

$filters = new FindMessages(
    pageNumber: 1,
    pageSize: 20,
    fromDate: new \DateTime('-3 days'), // <-- Standard DateTime object
    toDate: new \DateTime('now')        // <-- Standard DateTime object
);

$receivedMessages = $client->messages()->find($filters);

// The DTO converts the API's Jalali string back to DateTimeImmutable!
foreach ($receivedMessages as $message) {
    echo "From: " . $message->fromNumber . "\n";
    echo "Text: " . $message->body . "\n";
    echo "Received At: " . $message->date->format('Y-m-d H:i:s') . "\n";
}
```

---

## Testing

To run the test suite, clone the repository, install dependencies, and run PHPUnit:

```bash
git clone https://github.com/sapak/sapak-sms-php.git
cd sapak-sms-php
composer install
composer test
```
