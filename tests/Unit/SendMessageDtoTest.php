<?php

namespace Sapak\Sms\Tests\Unit;

use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Sapak\Sms\DTOs\Requests\SendMessage;
use InvalidArgumentException;

/**
 * Unit tests for the SendMessage DTO.
 *
 * These tests validate constructor input and ensure
 * the class enforces all business rules without external dependencies.
 */
class SendMessageDtoTest extends TestCase
{
    private array $validParams;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validParams = [
            'from' => '985000',
            'to' => ['989120000000'],
            'text' => 'Valid message',
        ];
    }

    public function test_it_throws_exception_if_from_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sender "from" cannot be empty.');

        $this->validParams['from'] = '';
        new SendMessage(...$this->validParams);
    }

    public function test_it_throws_exception_if_to_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Recipients "to" array cannot be empty.');

        $this->validParams['to'] = [];
        new SendMessage(...$this->validParams);
    }

    public function test_it_throws_exception_if_to_is_over_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('more than 100 recipients');

        $this->validParams['to'] = range(1, 101);
        new SendMessage(...$this->validParams);
    }

    public function test_it_throws_exception_if_text_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message "text" cannot be empty.');

        $this->validParams['text'] = '';
        new SendMessage(...$this->validParams);
    }

    public function test_it_throws_exception_if_send_at_is_invalid_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "sendAt"');

        $this->validParams['sendAt'] = '2025-01-01 12:00:00'; // Not RFC3339 / ATOM
        new SendMessage(...$this->validParams);
    }

    public function test_it_throws_exception_if_send_at_is_in_the_past(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be in the past');

        $pastDate = (new DateTime())->modify('-1 day')->format(DateTimeInterface::ATOM);
        $this->validParams['sendAt'] = $pastDate;

        new SendMessage(...$this->validParams);
    }

    public function test_it_throws_exception_if_send_at_is_too_far_in_future(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('more than one year in the future');

        $futureDate = (new DateTime())->modify('+2 years')->format(DateTimeInterface::ATOM);
        $this->validParams['sendAt'] = $futureDate;

        new SendMessage(...$this->validParams);
    }

    public function test_it_constructs_successfully_with_valid_data(): void
    {
        $dto = new SendMessage(...$this->validParams);

        $this->assertEquals('985000', $dto->from);
        $this->assertEquals(['989120000000'], $dto->to);
        $this->assertEquals('Valid message', $dto->text);
        $this->assertFalse($dto->isFlash);
        $this->assertNull($dto->sendAt);
    }
}
