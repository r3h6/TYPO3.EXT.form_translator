<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use R3H6\FormTranslator\Service\Typo3LanguageService;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;

class Typo3LanguageServiceTest extends TestCase
{
    private MockObject&Locales $localesMock;
    private MockObject&SiteFinder $siteFinderMock;
    private MockObject&EventDispatcherInterface $eventDispatcherMock;
    private Typo3LanguageService $typo3LanguageService;

    protected function setUp(): void
    {
        $GLOBALS['BE_USER'] = $this->createMock(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class);
        $this->localesMock = $this->createPartialMock(Locales::class, ['getActiveLanguages']);
        $this->siteFinderMock = $this->createMock(SiteFinder::class);
        $this->eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);

        $this->typo3LanguageService = new Typo3LanguageService(
            $this->localesMock,
            $this->siteFinderMock,
            $this->eventDispatcherMock,
            ''
        );
    }

    #[Test]
    public function findAllReturnsArrayOfTypo3Languages(): void
    {
        $GLOBALS['BE_USER']->groupData = ['allowed_languages' => '0'];
        $this->localesMock->method('getActiveLanguages')->willReturn(['de_DE']);
        $this->initializeMocks([
            0 => $this->createSiteLanguageStub(0, 'de_DE'),
        ]);

        $languages = $this->typo3LanguageService->findAll();

        self::assertEquals('de_DE', $languages[0]->getTypo3Language());
    }

    #[Test]
    public function findReturnsEnglishAsDefaultTypo3Language(): void
    {
        $GLOBALS['BE_USER']->groupData = ['allowed_languages' => '0'];
        $this->localesMock->method('getActiveLanguages')->willReturn(['default']);
        $this->initializeMocks([
            0 => $this->createSiteLanguageStub(0, 'en_US'),
        ]);

        $languages = $this->typo3LanguageService->findAll();

        self::assertEquals('default', $languages[0]->getTypo3Language());
    }

    #[Test]
    public function findReturnsOnlyAllowedLanguages(): void
    {
        $GLOBALS['BE_USER']->groupData = ['allowed_languages' => '0'];
        $this->localesMock->method('getActiveLanguages')->willReturn(['default', 'de_DE']);
        $this->initializeMocks([
            0 => $this->createSiteLanguageStub(0, 'de_DE'),
            1 => $this->createSiteLanguageStub(1, 'en_US'),
        ]);

        $languages = $this->typo3LanguageService->findAll();

        self::assertCount(1, $languages);
        self::assertEquals('de_DE', $languages[0]->getTypo3Language());
    }

    #[Test]
    public function findReturnsOnlyActiveLanguages(): void
    {
        $GLOBALS['BE_USER']->groupData = ['allowed_languages' => '0,1'];
        $this->localesMock->method('getActiveLanguages')->willReturn(['default', 'de_DE']);
        $this->initializeMocks([
            0 => $this->createSiteLanguageStub(0, 'de_DE'),
            1 => $this->createSiteLanguageStub(1, 'de_CH'),
        ]);

        $languages = $this->typo3LanguageService->findAll();

        self::assertCount(1, $languages);
        self::assertEquals('de_DE', $languages[0]->getTypo3Language());
    }

    #[Test]
    public function findReturnsPriorizedTypo3Language(): void
    {
        $GLOBALS['BE_USER']->groupData = ['allowed_languages' => '0,1'];
        $this->localesMock->method('getActiveLanguages')->willReturn(['de']);
        $this->initializeMocks([
            0 => $this->createSiteLanguageStub(0, 'de_CH'),
            1 => $this->createSiteLanguageStub(1, 'de_DE'),
        ]);

        $languages = $this->typo3LanguageService->findAll();

        self::assertCount(1, $languages);
        self::assertEquals('de', $languages[0]->getTypo3Language());
        self::assertEquals('German', $languages[0]->getTitle());
        self::assertEquals('de_DE', $languages[0]->getFlagIdentifier());
    }

    private function initializeMocks(array $languages): void
    {
        $siteMock = $this->createMock(Site::class);
        $siteMock->method('getAllLanguages')->willReturn($languages);
        $this->siteFinderMock->method('getAllSites')->willReturn([$siteMock]);
    }

    private function createSiteLanguageStub(int $id, string $locale): SiteLanguage
    {
        return new SiteLanguage($id, "$locale.UTF-8", new Uri("https://example.com/$locale"), ['title' => $locale, 'flag' => $locale]);
    }
}
