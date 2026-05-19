<?php

namespace App\News\Source;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractJsonApiFetcher implements NewsSourceFetcherInterface
{
    public function __construct(
        protected readonly HttpClientInterface $httpClient,
        protected readonly LoggerInterface $logger,
    ) {}

    protected function getJson(string $url, array $options = []): array
    {
        return $this->httpClient->request('GET', $url, $options)->toArray();
    }
}
