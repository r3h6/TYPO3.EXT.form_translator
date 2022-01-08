<?php

namespace R3H6\FormTranslator\Service;

use GuzzleHttp\RequestOptions;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class LibreTranslateTranslationService implements TranslationServiceInterface
{

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var string
     */
    protected $api;

    /**
     * @var string
     */
    protected $apiKey;

    public function __construct(RequestFactory $requestFactory, string $api, string $apiKey)
    {
        $this->requestFactory = $requestFactory;
        $this->api = $api;
        $this->apiKey = $apiKey;
    }

    public function getName(): string
    {
        return 'LibreTranslate';
    }

    public function isEnabled(): bool
    {
        return empty($this->api) === false;
    }

    public function translate(string $text, SiteLanguage $targetLanguage): string
    {
        $additionalOptions = [
            RequestOptions::TIMEOUT => 5,
            RequestOptions::JSON => [
                'q' => $text,
                'source' => 'auto',
                'target' => $targetLanguage->getTwoLetterIsoCode(),
                'format' => 'text',
            ],
        ];

        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $this->requestFactory->request(rtrim($this->api, '/') . '/translate', 'POST', $additionalOptions);
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException($response->getBody(), $response->getStatusCode());
        }

        return json_decode((string)$response->getBody(), true)['translatedText'];
    }
}
