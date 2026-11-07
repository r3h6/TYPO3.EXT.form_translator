<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Tests\Functional\Updates;

use PHPUnit\Framework\Attributes\Test;
use R3H6\FormTranslator\Service\FormService;
use R3H6\FormTranslator\Service\LocalizationService;
use R3H6\FormTranslator\Service\TranslationFileService;
use R3H6\FormTranslator\Translation\Dto\Typo3Language;
use R3H6\FormTranslator\Translation\Item;
use R3H6\FormTranslator\Translation\ItemCollection;
use R3H6\FormTranslator\Updates\SharedTranslationFilesUpdateWizard;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\LanguageStore;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class SharedTranslationFilesUpdateWizardTest extends FunctionalTestCase
{
    protected string $testDirectory;
    protected LocalizationService $localizationService;
    protected TranslationFileService $translationFileService;
    protected SharedTranslationFilesUpdateWizard $wizard;

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
        $this->testDirectory = Environment::getPublicPath()
            . '/fileadmin/form_translations_test_' . bin2hex(random_bytes(4));
        $this->localizationService = GeneralUtility::makeInstance(LocalizationService::class);
        $this->translationFileService = new TranslationFileService(
            GeneralUtility::makeInstance(LocalizationFactory::class),
            $this->localizationService,
            $this->testDirectory . '/shared'
        );
        $this->wizard = new SharedTranslationFilesUpdateWizard(
            $this->createMock(FormService::class),
            $this->translationFileService,
            $this->createMock(FrontendInterface::class)
        );
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->testDirectory, true);
        $this->flushL10nCache();
        parent::tearDown();
    }

    #[Test]
    public function migratesPerFormFilesAndRemovesRegistration(): void
    {
        $baseFile = $this->testDirectory . '/l10n/example.xlf';
        $this->saveItems($baseFile, new Typo3Language('default', 'English', 'gb'), ['default.label' => 'Default']);
        $this->saveItems($baseFile, new Typo3Language('de', 'German', 'de'), ['de.label' => 'Deutsch']);
        $this->saveItems($baseFile, new Typo3Language('de_CH', 'Swiss German', 'ch'), ['de_CH.label' => 'Schweizerdeutsch']);

        $formDefinition = [
            'identifier' => 'example',
            'renderingOptions' => [
                'submitButtonLabel' => 'Submit',
                'translation' => [
                    'translationFiles' => [
                        99 => $baseFile,
                    ],
                ],
            ],
        ];

        $migrated = $this->wizard->migrateFormDefinition($formDefinition);

        self::assertSame(['identifier' => 'example', 'renderingOptions' => ['submitButtonLabel' => 'Submit']], $migrated);
        self::assertSame('Default', $this->readFresh('default')->getItem('default.label')->getTarget());
        self::assertSame('Deutsch', $this->readFresh('de')->getItem('de.label')->getTarget());
        self::assertSame('Schweizerdeutsch', $this->readFresh('de_CH')->getItem('de_CH.label')->getTarget());
    }

    #[Test]
    public function migrationIsIdempotent(): void
    {
        $baseFile = $this->testDirectory . '/l10n/example.xlf';
        $this->saveItems($baseFile, new Typo3Language('de', 'German', 'de'), ['de.label' => 'Deutsch']);
        $formDefinition = [
            'identifier' => 'example',
            'renderingOptions' => [
                'translation' => [
                    'translationFiles' => [99 => $baseFile],
                ],
            ],
        ];

        $migrated = $this->wizard->migrateFormDefinition($formDefinition);
        $migratedAgain = $this->wizard->migrateFormDefinition($migrated);

        self::assertSame($migrated, $migratedAgain);
        self::assertCount(1, $this->readFresh('de'));
    }

    /**
     * @param array<string, string> $targets
     */
    private function saveItems(string $file, Typo3Language $language, array $targets): void
    {
        $items = new ItemCollection();
        foreach ($targets as $identifier => $target) {
            $item = new Item($identifier);
            $item->setSource($identifier);
            $item->setTarget($target);
            $items->addItem($item);
        }
        $this->localizationService->saveXliff($file, $language, $items);
    }

    private function readFresh(string $language): ItemCollection
    {
        $this->flushL10nCache();
        $translationFileService = new TranslationFileService(
            new LocalizationFactory(
                new LanguageStore(GeneralUtility::makeInstance(PackageManager::class)),
                GeneralUtility::makeInstance(CacheManager::class)
            ),
            $this->localizationService,
            $this->testDirectory . '/shared'
        );
        return $translationFileService->read(new Typo3Language($language, $language, ''));
    }

    private function flushL10nCache(): void
    {
        GeneralUtility::makeInstance(CacheManager::class)->getCache('l10n')->flush();
    }
}
