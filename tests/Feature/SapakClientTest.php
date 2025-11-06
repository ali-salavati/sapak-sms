<?php

namespace Sapak\Sms\Tests\Feature;

use DateTime;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Sapak\Sms\DTOs\Requests\FindMessages;
use Sapak\Sms\DTOs\Responses\ReceivedMessage;
use Sapak\Sms\SapakClient;
use Sapak\Sms\DTOs\Requests\SendMessage;
use Sapak\Sms\DTOs\Requests\SendPeerToPeer;
use Sapak\Sms\DTOs\Responses\SentMessageStatus;
use Sapak\Sms\Exceptions\ApiException;
use Sapak\Sms\Exceptions\AuthenticationException;
use Sapak\Sms\Exceptions\ValidationException;

class SapakClientTest extends TestCase
{
    protected MockHandler $mockHandler;
    protected array $historyContainer = [];
    protected HandlerStack $handlerStack;

    protected function setUp(): void
    {
        parent::setUp();

        // Prepare a mock handler and history middleware
        $this->mockHandler = new MockHandler();
        $this->handlerStack = HandlerStack::create($this->mockHandler);
        $history = Middleware::history($this->historyContainer);
        $this->handlerStack->push($history);
    }

    /**
     * @throws ValidationException
     * @throws AuthenticationException
     * @throws ApiException
     */
    public function test_it_sends_message_successfully_and_returns_dto(): void
    {
        // Arrange
        $mockResponseJson = json_encode([['id' => 12345, 'status' => 1]]);
        $mockResponse = new Response(200, ['Content-Type' => 'application/json'], $mockResponseJson);
        $this->mockHandler->append($mockResponse);

        $client = new SapakClient('TEST_API_KEY', guzzleConfig: ['handler' => $this->handlerStack]);

        // Act
        $messageDto = new SendMessage('985000', ['98912...'], 'Test Body');
        $results = $client->messages()->send($messageDto);

        // Assert DTO
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertInstanceOf(SentMessageStatus::class, $results[0]);
        $this->assertEquals(12345, $results[0]->id);
        $this->assertEquals(1, $results[0]->status);

        // Assert request
        $this->assertCount(1, $this->historyContainer);
        $request = $this->historyContainer[0]['request'];
        $this->assertEquals('/v1/messages', $request->getUri()->getPath());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('TEST_API_KEY', $request->getHeaderLine('X-API-KEY'));
        $this->assertEquals('application/json', $request->getHeaderLine('Accept'));

        $expectedBody = [
            'from' => '985000',
            'to' => ['98912...'],
            'text' => 'Test Body',
            'isFlash' => false,
            'sendAt' => null,
        ];
        $this->assertEquals($expectedBody, json_decode($request->getBody()->getContents(), true));
    }

    /**
     * @throws ValidationException
     * @throws ApiException
     */
    public function test_it_throws_authentication_exception_on_401(): void
    {
        $mockResponseJson = json_encode(['message' => 'Invalid API Key']);
        $mockResponse = new Response(401, ['Content-Type' => 'application/json'], $mockResponseJson);
        $this->mockHandler->append($mockResponse);

        $client = new SapakClient('INVALID_KEY', guzzleConfig: ['handler' => $this->handlerStack]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid API Key');

        $messageDto = new SendMessage('985000', ['98912...'], 'Test');
        $client->messages()->send($messageDto);
    }

    /**
     * @throws AuthenticationException
     * @throws ApiException
     */
    public function test_it_throws_validation_exception_on_400(): void
    {
        $mockResponseJson = json_encode(['message' => '"text" is required']);
        $mockResponse = new Response(400, ['Content-Type' => 'application/json'], $mockResponseJson);
        $this->mockHandler->append($mockResponse);

        $client = new SapakClient('TEST_API_KEY', guzzleConfig: ['handler' => $this->handlerStack]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('"text" is required');

        $messageDto = new SendMessage('985000', ['98912...'], 'Valid client-side text');
        $client->messages()->send($messageDto);
    }

    /**
     * @throws AuthenticationException
     * @throws ValidationException
     */
    public function test_it_throws_api_exception_on_500(): void
    {
        $mockResponseJson = json_encode(['message' => 'Internal Server Error']);
        $mockResponse = new Response(500, ['Content-Type' => 'application/json'], $mockResponseJson);
        $this->mockHandler->append($mockResponse);

        $client = new SapakClient('TEST_API_KEY', guzzleConfig: ['handler' => $this->handlerStack]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Internal Server Error');

        $messageDto = new SendMessage('985000', ['98912...'], 'Test');
        $client->messages()->send($messageDto);
    }

    /**
     * @throws AuthenticationException
     * @throws ValidationException
     * @throws ApiException
     */
    public function test_it_sends_p2p_message_successfully_and_returns_dto(): void
    {
        // Arrange
        $mockResponseJson = json_encode([
            ['id' => 98765, 'status' => 1],
            ['id' => 98766, 'status' => 2]
        ]);
        $mockResponse = new Response(200, ['Content-Type' => 'application/json'], $mockResponseJson);
        $this->mockHandler->append($mockResponse);

        $client = new SapakClient('TEST_API_KEY', guzzleConfig: ['handler' => $this->handlerStack]);

        // Act
        $p2pMessage1 = new SendPeerToPeer('985000', '98912111', 'Hello 1');
        $p2pMessage2 = new SendPeerToPeer('985000', '98912222', 'Hello 2');
        $results = $client->messages()->sendPeerToPeer([$p2pMessage1, $p2pMessage2]);

        // Assert DTOs
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertInstanceOf(SentMessageStatus::class, $results[0]);
        $this->assertEquals(98765, $results[0]->id);
        $this->assertEquals(2, $results[1]->status);

        // Assert request
        $this->assertCount(1, $this->historyContainer);
        $request = $this->historyContainer[0]['request'];
        $this->assertEquals('/v1/messages/p2p', $request->getUri()->getPath());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('TEST_API_KEY', $request->getHeaderLine('X-API-KEY'));
        $this->assertEquals('application/json', $request->getHeaderLine('Accept'));

        $expectedBody = [
            [
                'sender' => '985000',
                'recipient' => '98912111',
                'message' => 'Hello 1',
                'sendAt' => null
            ],
            [
                'sender' => '985000',
                'recipient' => '98912222',
                'message' => 'Hello 2',
                'sendAt' => null
            ]
        ];
        $this->assertEquals($expectedBody, json_decode($request->getBody()->getContents(), true));
    }

    /**
     * @throws AuthenticationException
     * @throws ValidationException
     * @throws ApiException
     */
    public function test_it_finds_messages_successfully_and_returns_dto(): void
    {
        $jalaliDateString = '1404/08/16 01:14:40';
        $mockResponseJson = json_encode([
            [
                "id" => 123,
                "date" => $jalaliDateString,
                "body" => "Hello",
                "fromNumber" => "98912...",
                "toNumber" => "985000"
            ]
        ]);
        $mockResponse = new Response(200, ['Content-Type' => 'application/json'], $mockResponseJson);
        $this->mockHandler->append($mockResponse);

        $client = new SapakClient('TEST_API_KEY', guzzleConfig: ['handler' => $this->handlerStack]);

        $gregorianDate = new DateTime('2023-10-30 10:00:00');
        $filters = new FindMessages(fromDate: $gregorianDate);

        $results = $client->messages()->find($filters);

        $this->assertIsArray($results);
        $this->assertInstanceOf(ReceivedMessage::class, $results[0]);
        $this->assertEquals(123, $results[0]->id);

        $this->assertInstanceOf(\DateTimeImmutable::class, $results[0]->date);

        $this->assertEquals(
            '2025-11-07 01:14:40',
            $results[0]->date->format('Y-m-d H:i:s')
        );

        $request = $this->historyContainer[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/v1/messages/find', $request->getUri()->getPath());

        $expectedQuery = 'fromDate=1402-08-08+10%3A00%3A00'; // 1402-08-08 10:00:00
        $this->assertEquals(
            str_replace('+', '%20', $expectedQuery),
            str_replace('+', '%20', $request->getUri()->getQuery())
        );
    }
}
