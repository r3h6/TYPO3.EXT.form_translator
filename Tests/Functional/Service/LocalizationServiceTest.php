<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Tests\Functional\Service;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\Test;
use R3H6\FormTranslator\Service\LocalizationService;
use R3H6\FormTranslator\Translation\Dto\Typo3Language;
use R3H6\FormTranslator\Translation\Item;
use R3H6\FormTranslator\Translation\ItemCollection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class LocalizationServiceTest extends FunctionalTestCase
{
    protected LocalizationService $localizationService;
    protected vfsStreamDirectory $root;
    private int $errorReporting;

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/form_translator',
    ];

    protected array $coreExtensionsToLoad = [
        'form',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->localizationService = GeneralUtility::makeInstance(LocalizationService::class);
        $this->root = vfsStream::setup('root');
        $this->errorReporting = error_reporting();
    }

    protected function tearDown(): void
    {
        error_reporting($this->errorReporting);
        parent::tearDown();
    }

    #[Test]
    public function translateToDefaultLanguageWritesFile(): void
    {
        error_reporting(0);

        $locallangFile = 'vfs://root/locallang.xlf';
        $language = new Typo3Language('default', 'English', 'gb');

        $item = new Item('test');
        $item->setSource('test.source');
        $item->setTarget('test.target');

        $items = new ItemCollection();
        $items->addItem($item);

        $this->localizationService->saveXliff($locallangFile, $language, $items);

        self::assertSame('default', $language->getTypo3Language());
        self::assertFileExists($locallangFile);
        self::assertFileDoesNotExist('vfs://root/default.locallang.xlf');
        self::assertXmlStringEqualsXmlString(
            $this->normalizeXml(dirname(__DIR__) . '/Assertions/default.xlf'),
            $this->normalizeXml($locallangFile)
        );
    }

    #[Test]
    public function translateToLanguageWritesDefaultAndLocalizedFile(): void
    {
        error_reporting(0);

        $locallangFile = 'vfs://root/locallang.xlf';
        $translationFile = 'vfs://root/en_US.locallang.xlf';
        $language = new Typo3Language('en_US', 'English (US)', 'us');

        $item = new Item('test');
        $item->setSource('test.source');
        $item->setTarget('test.target');

        $items = new ItemCollection();
        $items->addItem($item);

        $this->localizationService->saveXliff($locallangFile, $language, $items);

        self::assertSame('en_US', $language->getTypo3Language());
        self::assertFileExists($locallangFile);
        self::assertFileExists($translationFile);
        self::assertXmlStringEqualsXmlString(
            $this->normalizeXml(dirname(__DIR__) . '/Assertions/empty.xlf'),
            $this->normalizeXml($locallangFile),
            'Default file should be empty'
        );
        self::assertXmlStringEqualsXmlString(
            $this->normalizeXml(dirname(__DIR__) . '/Assertions/en_US.xlf'),
            $this->normalizeXml($translationFile),
            'Translation file should contain translation'
        );
    }

    #[Test]
    public function emptyTranslationDoesNotWriteFile(): void
    {
        error_reporting(0);

        $locallangFile = 'vfs://root/locallang.xlf';
        $language = new Typo3Language('default', 'English', 'gb');

        $items = new ItemCollection();

        $this->localizationService->saveXliff($locallangFile, $language, $items);

        self::assertSame('default', $language->getTypo3Language());
        self::assertFileDoesNotExist($locallangFile);
    }

    private function normalizeXml(string $file): string
    {
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->load($file);
        $doc->normalizeDocument();
        $output = $doc->saveXML();
        if ($output === false) {
            throw new \RuntimeException('Could not save xml', 1641680429711);
        }
        return $output;
    }
}
