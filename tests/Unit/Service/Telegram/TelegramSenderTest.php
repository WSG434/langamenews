<?php

namespace App\Tests\Unit\Service\Telegram;

use App\Service\Telegram\TelegramSender;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class TelegramSenderTest extends TestCase
{
    public function testSendSuccessfully(): void
    {
        $client = new MockHttpClient(new MockResponse('{}', ['http_code' => 200]));
        $sender = new TelegramSender($client, 'token', '12345', new NullLogger());

        $sender->send('Hello');
        $this->expectNotToPerformAssertions();
    }

    public function testSendThrowsRecoverableOn5xx(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 500]));
        $sender = new TelegramSender($client, 'token', '12345', new NullLogger());

        $this->expectException(RecoverableMessageHandlingException::class);
        $sender->send('Hello');
    }

    public function testSendThrowsUnrecoverableOn4xx(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 400]));
        $sender = new TelegramSender($client, 'token', '12345', new NullLogger());

        $this->expectException(UnrecoverableMessageHandlingException::class);
        $sender->send('Hello');
    }
}
