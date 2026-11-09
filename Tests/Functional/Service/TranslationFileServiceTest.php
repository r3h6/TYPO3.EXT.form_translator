<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use R3H6\FormTranslator\Service\LocalizationService;
use R3H6\FormTranslator\Service\TranslationFileService;
use R3H6\FormTranslator\Translation\Dto\Typo3Language;
use R3H6\FormTranslator\Translation\Item;
use R3H6\FormTranslator\Translation\ItemCollection;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class TranslationFileServiceTest extends FunctionalTestCase
{
    protected TranslationFileService $translationFileService;
    protected LocalizationService $localizationService;
    protected Typo3Language $defaultLanguage;
    protected Typo3Language $germanLanguage;
    protected string $relativeTranslationDirectory;
    protected string $translationDirectory;

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/form_translator',
    ];

    protected array $coreExtensionsToLoad = [
        'form',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->flushL10nCache();
        $this->relativeTranslationDirectory = 'form_translations_test_' . bin2hex(random_bytes(4));
        $this->translationDirectory = Environment::getPublicPath() . '/fileadmin/' . $this->relativeTranslationDirectory;
        $this->localizationService = GeneralUtility::makeInstance(LocalizationService::class);
        $this->translationFileService = new TranslationFileService(
            $this->localizationService,
            GeneralUtility::makeInstance(StorageRepository::class),
            '1:' . $this->relativeTranslationDirectory
        );
        $this->defaultLanguage = new Typo3Language('default', 'English', 'gb');
        $this->germanLanguage = new Typo3Language('de', 'German', 'de');
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->translationDirectory, true);
        $this->flushL10nCache();
        parent::tearDown();
    }

    #[Test]
    public function referenceIsPublicRelativePathForDefaultStorage(): void
    {
        self::assertSame(
            'fileadmin/' . $this->relativeTranslationDirectory . '/locallang.xlf',
            $this->translationFileService->getReference()
        );
    }

    #[Test]
    #[DataProvider('invalidTranslationFilesDirectories')]
    public function invalidTranslationFilesDirectoryThrows(string $translationFilesDirectory): void
    {
        $translationFileService = new TranslationFileService(
            $this->localizationService,
            GeneralUtility::makeInstance(StorageRepository::class),
            $translationFilesDirectory
        );

        $this->expectException(\InvalidArgumentException::class);

        $translationFileService->getFile();
    }

    public static function invalidTranslationFilesDirectories(): \Generator
    {
        yield 'plain path' => ['fileadmin/form_translations/'];
        yield 'empty value' => [''];
        yield 'non-numeric storage' => ['foo:form_translations/'];
        yield 'empty path' => ['1:'];
        yield 'fallback storage' => ['0:form_translations/'];
        yield 'unknown storage' => ['999999:form_translations/'];
        yield 'path traversal' => ['1:../form_translations/'];
    }

    #[Test]
    public function readReturnsEmptyCollectionWhenFileDoesNotExist(): void
    {
        self::assertCount(0, $this->translationFileService->read($this->defaultLanguage));
    }

    #[Test]
    public function mergeKeepsOtherFormsAndReplacesCurrentForm(): void
    {
        $this->saveItems($this->defaultLanguage, [
            'other.label' => 'Other',
            'example.label' => 'Old',
            'example.empty' => 'To remove',
            'example.stale' => 'Not part of the form anymore',
        ]);

        $submitted = new ItemCollection();
        $label = new Item('example.label');
        $label->setSource('Example');
        $label->setTarget('New');
        $submitted->addItem($label);
        $empty = new Item('example.empty');
        $empty->setSource('Empty');
        $empty->setTarget('');
        $submitted->addItem($empty);

        $merged = $this->translationFileService->merge(
            ['example.label', 'example.empty'],
            $this->defaultLanguage,
            $submitted
        );
        $this->translationFileService->save($this->defaultLanguage, $merged);

        $items = $this->readFresh($this->defaultLanguage);

        self::assertCount(3, $items);
        self::assertSame('Other', $items->getItem('other.label')->getTarget());
        self::assertSame('New', $items->getItem('example.label')->getTarget());
        self::assertSame('Not part of the form anymore', $items->getItem('example.stale')->getTarget());
        self::assertNull($items->getItem('example.empty'));
    }

    #[Test]
    public function mergeWritesLanguageVariantAndKeepsOtherForms(): void
    {
        $this->saveItems($this->germanLanguage, [
            'other.label' => 'Andere',
            'example.label' => 'Alt',
        ]);

        $submitted = new ItemCollection();
        $label = new Item('example.label');
        $label->setSource('Example');
        $label->setTarget('Neu');
        $submitted->addItem($label);

        $merged = $this->translationFileService->merge(['example.label'], $this->germanLanguage, $submitted);
        $this->translationFileService->save($this->germanLanguage, $merged);

        self::assertFileExists($this->translationDirectory . '/locallang.xlf');
        self::assertFileExists($this->translationDirectory . '/de.locallang.xlf');

        $items = $this->readFresh($this->germanLanguage);
        self::assertCount(2, $items);
        self::assertSame('Andere', $items->getItem('other.label')->getTarget());
        self::assertSame('Neu', $items->getItem('example.label')->getTarget());
    }

    #[Test]
    public function removeDeletesOnlyGivenIdentifiersFromAllLanguageFiles(): void
    {
        $this->saveItems($this->defaultLanguage, [
            'example.a' => 'A',
            'example.b' => 'B',
        ]);
        $this->saveItems($this->germanLanguage, [
            'example.a' => 'A de',
            'example.b' => 'B de',
        ]);

        $this->translationFileService->remove(['example.a']);

        $defaultItems = $this->readFresh($this->defaultLanguage);
        self::assertNull($defaultItems->getItem('example.a'));
        self::assertSame('B', $defaultItems->getItem('example.b')->getTarget());

        $germanItems = $this->readFresh($this->germanLanguage);
        self::assertNull($germanItems->getItem('example.a'));
        self::assertSame('B de', $germanItems->getItem('example.b')->getTarget());
    }

    private function readFresh(Typo3Language $language): ItemCollection
    {
        $this->flushL10nCache();
        $translationFileService = new TranslationFileService(
            $this->localizationService,
            GeneralUtility::makeInstance(StorageRepository::class),
            '1:' . $this->relativeTranslationDirectory
        );
        return $translationFileService->read($language);
    }

    /**
     * @param array<string, string> $targets
     */
    private function saveItems(Typo3Language $language, array $targets): void
    {
        $items = new ItemCollection();
        foreach ($targets as $identifier => $target) {
            $item = new Item($identifier);
            $item->setSource($identifier);
            $item->setTarget($target);
            $items->addItem($item);
        }
        $this->localizationService->saveXliff(
            $this->translationFileService->getFile(),
            $language,
            $items
        );
    }

    private function flushL10nCache(): void
    {
        GeneralUtility::makeInstance(CacheManager::class)->getCache('l10n')->flush();
    }
}
