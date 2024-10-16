<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Service;

use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use R3H6\FormTranslator\Translation\Dto\Typo3Language;
use TYPO3\CMS\Core\Http\RequestFactory;

class LibreTranslateTranslationService implements TranslationServiceInterface
{
    public function __construct(
        protected readonly RequestFactory $requestFactory,
        protected readonly string $host,
        protected readonly string $apiKey,
    ) {}

    public function getName(): string
    {
        return 'LibreTranslate';
    }

    public function isEnabled(): bool
    {
        return empty($this->host) === false;
    }

    public function translate(string $text, Typo3Language $targetLanguage): string
    {
        $params = [
            'q' => $text,
            'source' => 'auto',
            'target' => $targetLanguage->getLanguageCode(),
            'format' => 'text',
        ];

        if ($this->apiKey) {
            $params['api_key'] = $this->apiKey;
        }

        $additionalOptions = [
            RequestOptions::TIMEOUT => 5,
            RequestOptions::JSON => $params,
        ];

        /** @var ResponseInterface $response */
        $response = $this->requestFactory->request(rtrim($this->host, '/') . '/translate', 'POST', $additionalOptions);
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException((string)$response->getBody(), $response->getStatusCode());
        }

        return json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR)['translatedText'];
    }
}
