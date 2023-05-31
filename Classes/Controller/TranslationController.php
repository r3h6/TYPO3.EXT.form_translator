<?php

namespace R3H6\FormTranslator\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use R3H6\FormTranslator\Service\MemoryTranslationService;
use R3H6\FormTranslator\Service\SiteLanguageService;
use R3H6\FormTranslator\Service\TranslationServiceInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

class TranslationController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected TranslationServiceInterface $translationService;

    protected SiteLanguageService $siteLanguageService;

    protected MemoryTranslationService $memoryTranslationService;

    public function __construct(MemoryTranslationService $memoryTranslationService, TranslationServiceInterface $translationService, SiteLanguageService $siteLanguageService)
    {
        $this->translationService = $translationService;
        $this->siteLanguageService = $siteLanguageService;
        $this->memoryTranslationService = $memoryTranslationService;
    }

    public function translateAction(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();

        if (!isset($params['text'])) {
            return new JsonResponse(['error' => 'Missing text parameter'], 400);
        }

        if (!isset($params['target'])) {
            return new JsonResponse(['error' => 'Missing target parameter'], 400);
        }

        if (trim($params['text']) === '') {
            return new JsonResponse([
                'translation' => '',
            ]);
        }

        $siteLanguage = $this->siteLanguageService->findAll()[(int)$params['target']];

        $translation = $this->memoryTranslationService->translate($params['text'], $siteLanguage);

        if ($translation === '' && $this->translationService->isEnabled()) {
            try {
                $translation = $this->translationService->translate($params['text'], $siteLanguage);
            } catch (\Exception $exception) {
                $this->logger->error('Translation service failure', ['reason' => $exception->getMessage()]);
                return new JsonResponse([
                    'code' => $exception->getCode(),
                    'error' => $exception->getMessage(),
                ], 500);
            }
        }

        return new JsonResponse([
            'translation' => $translation,
        ]);
    }
}
