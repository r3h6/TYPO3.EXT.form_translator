<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use R3H6\FormTranslator\Controller\TranslationController;
use R3H6\FormTranslator\Service\MemoryTranslationService;
use R3H6\FormTranslator\Service\TranslationServiceInterface;
use R3H6\FormTranslator\Service\Typo3LanguageService;

class TranslationControllerTest extends TestCase
{
    #[Test]
    public function translateActionRejectsNonArrayRequestBody(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn((object)['text' => 'Hello']);

        $response = $this->createSubject()->translateAction($request);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(['error' => 'Invalid request body'], json_decode((string)$response->getBody(), true));
    }

    private function createSubject(): TranslationController
    {
        return new TranslationController(
            $this->createMock(MemoryTranslationService::class),
            $this->createMock(TranslationServiceInterface::class),
            $this->createMock(Typo3LanguageService::class)
        );
    }
}
