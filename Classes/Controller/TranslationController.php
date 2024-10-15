<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use R3H6\FormTranslator\Service\MemoryTranslationService;
use R3H6\FormTranslator\Service\TranslationServiceInterface;
use R3H6\FormTranslator\Service\Typo3LanguageService;
use TYPO3\CMS\Core\Http\JsonResponse;

class TranslationController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(protected MemoryTranslationService $memoryTranslationService, protected TranslationServiceInterface $translationService, protected Typo3LanguageService $languageService) {}

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

        $language = $this->languageService->find($params['target']);
        $translation = $this->memoryTranslationService->translate($params['text'], $language);

        if ($translation === '' && $this->translationService->isEnabled()) {
            try {
                $translation = $this->translationService->translate($params['text'], $language);
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
