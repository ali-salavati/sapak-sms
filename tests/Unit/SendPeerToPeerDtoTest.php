<?php

namespace Sapak\Sms\Tests\Unit;

use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Sapak\Sms\DTOs\Requests\SendPeerToPeer;

/**
 * Unit test for the SendPeerToPeer DTO.
 *
 * This test validates the input logic (constructor validation) for a *single*
 * peer-to-peer message item, ensuring its internal state is always valid.
 * It also implicitly tests the integration of the ValidatesSendAt trait.
 */
class SendPeerToPeerDtoTest extends TestCase
{
    /**
     * @var array A valid set of parameters to be used as a "happy path" default.
     */
    private array $validParams;

    /**
     * Set up a clean, valid state before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->validParams = [
            'sender' => '985000',
            'recipient' => '989120000000',
            'message' => 'Valid message',
        ];
    }

    public function test_it_throws_exception_if_sender_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('P2P "sender" cannot be empty.');

        $this->validParams['sender'] = ' '; // Test trim logic
        new SendPeerToPeer(...$this->validParams);
    }

    public function test_it_throws_exception_if_recipient_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('P2P "recipient" cannot be empty.');

        $this->validParams['recipient'] = '';
        new SendPeerToPeer(...$this->validParams);
    }

    public function test_it_throws_exception_if_message_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('P2P "message" cannot be empty.');

        $this->validParams['message'] = '';
        new SendPeerToPeer(...$this->validParams);
    }

    public function test_it_throws_exception_if_send_at_is_invalid_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "sendAt" format. Expected a valid ATOM/RFC3339 timestamp');


        // Invalid (not ATOM/RFC3339)
        $this->validParams['sendAt'] = '2025-01-01 12:00:00';
        new SendPeerToPeer(...$this->validParams);
    }

    public function test_it_throws_exception_if_send_at_is_in_the_past(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be in the past');

        // Valid format but past date
        $pastDate = (new DateTime())->modify('-1 day')->format(DateTimeInterface::ATOM);
        $this->validParams['sendAt'] = $pastDate;
        new SendPeerToPeer(...$this->validParams);
    }

    public function test_it_constructs_successfully_with_valid_data(): void
    {
        $dto = new SendPeerToPeer(...$this->validParams);

        $this->assertEquals('985000', $dto->sender);
        $this->assertEquals('989120000000', $dto->recipient);
        $this->assertNull($dto->sendAt);
    }

    public function test_it_constructs_successfully_with_a_valid_future_date(): void
    {
        $futureDate = (new DateTime())->modify('+10 days')->format(DateTimeInterface::ATOM);
        $this->validParams['sendAt'] = $futureDate;

        $dto = new SendPeerToPeer(...$this->validParams);

        $this->assertEquals($futureDate, $dto->sendAt);
    }
}
