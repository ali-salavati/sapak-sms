<?php

namespace Sapak\Sms\Tests\Feature;

use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Sapak\Sms\SapakClient;
use Sapak\Sms\DTOs\Requests\SendMessage;
use Sapak\Sms\DTOs\Responses\SentMessageStatus;
use Sapak\Sms\Exceptions\ApiException;
use Sapak\Sms\Exceptions\AuthenticationException;
use Sapak\Sms\Exceptions\ValidationException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;

/**
 * Feature tests for the main SapakClient.
 * These tests verify the complete request/response flow without real API calls.
 */
class SapakClientTest extends TestCase
{
    private MockHandler $mockHandler;
    private array $historyContainer = [];
    private SapakClient $client;
    private SendMessage $validMessageDto;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Prepare a Guzzle Mock Handler
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);

        // 2. Add a history middleware to inspect outgoing requests
        $historyMiddleware = Middleware::history($this->historyContainer);
        $handlerStack->push($historyMiddleware);

        // 3. Initialize the SDK client with mocked Guzzle handler
        $this->client = new SapakClient(
            apiKey: 'TEST_API_KEY',
            guzzleConfig: [
                'handler' => $handlerStack
            ]
        );

        // 4. Prepare a valid DTO for message sending tests
        $this->validMessageDto = new SendMessage(
            from: '985000',
            to: ['98912'],
            text: 'Test'
        );
    }

    /**
     * @throws ValidationException
     * @throws AuthenticationException
     * @throws GuzzleException
     * @throws ApiException
     */
    public function test_it_sends_message_successfully_and_returns_dto(): void
    {
        // Mock a successful API response (200)
        $mockApiResponse = [
            ['id' => 12345, 'status' => 1],
            ['id' => 12346, 'status' => 3]
        ];
        $this->mockHandler->append(new Response(200, [], json_encode($mockApiResponse)));

        // Execute method
        $result = $this->client->messages()->send($this->validMessageDto);

        // Assert the response structure
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(SentMessageStatus::class, $result[0]);
        $this->assertEquals(12345, $result[0]->id);
        $this->assertEquals(3, $result[1]->status);

        // Assert the outgoing request
        $this->assertCount(1, $this->historyContainer);
        $request = $this->historyContainer[0]['request'];

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/v1/messages', $request->getUri()->getPath());
        $this->assertEquals('TEST_API_KEY', $request->getHeaderLine('X-API-KEY'));
        $this->assertEquals(json_encode($this->validMessageDto), (string) $request->getBody());
    }

    /**
     * @throws ValidationException
     * @throws GuzzleException
     * @throws ApiException
     */
    public function test_it_throws_authentication_exception_on_401(): void
    {
        // Mock a 401 Unauthorized response
        $mockApiResponse = ['message' => 'Invalid API Key'];
        $this->mockHandler->append(new Response(401, [], json_encode($mockApiResponse)));

        // Expect an AuthenticationException
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid API Key');

        $this->client->messages()->send($this->validMessageDto);
    }

    /**
     * @throws AuthenticationException
     * @throws GuzzleException
     * @throws ApiException
     */
    public function test_it_throws_validation_exception_on_400(): void
    {
        // Mock a 400 Bad Request response
        $mockApiResponse = ['message' => 'Text is empty'];
        $this->mockHandler->append(new Response(400, [], json_encode($mockApiResponse)));

        // Expect a ValidationException
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Text is empty');

        $this->client->messages()->send($this->validMessageDto);
    }

    /**
     * @throws ValidationException
     * @throws AuthenticationException
     * @throws GuzzleException
     */
    public function test_it_throws_api_exception_on_500(): void
    {
        // Mock a 500 Internal Server Error response
        $this->mockHandler->append(new Response(500, [], 'Internal Server Error'));

        // Expect a generic ApiException
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('API server error');

        $this->client->messages()->send($this->validMessageDto);
    }
}
