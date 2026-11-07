<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Service;

use R3H6\FormTranslator\Translation\Dto\Typo3Language;
use R3H6\FormTranslator\Translation\Item;
use R3H6\FormTranslator\Translation\ItemCollection;
use R3H6\FormTranslator\Utility\PathUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TranslationFileService
{
    public const TRANSLATION_FILE_NAME = 'locallang.xlf';
    public const TRANSLATION_FILE_KEY = 99;

    public function __construct(
        protected readonly LocalizationFactory $localizationFactory,
        protected readonly LocalizationService $localizationService,
        protected readonly string $translationFilesDirectory,
    ) {}

    public function getReference(): string
    {
        $file = $this->getFile();
        $publicPath = rtrim(Environment::getPublicPath(), '/') . '/';
        if (str_starts_with($file, $publicPath)) {
            return substr($file, strlen($publicPath));
        }
        return $file;
    }

    public function getFile(): string
    {
        $directory = rtrim($this->translationFilesDirectory, '/');
        return PathUtility::makeAbsolute(
            $directory . '/' . self::TRANSLATION_FILE_NAME,
            Environment::getPublicPath()
        );
    }

    public function exists(): bool
    {
        return is_file($this->getFile());
    }

    public function read(Typo3Language $language): ItemCollection
    {
        return $this->readFromFile($this->getFile(), $language);
    }

    public function readFromFile(string $file, Typo3Language $language): ItemCollection
    {
        $items = new ItemCollection();
        if (!is_file($file)) {
            return $items;
        }
        $typo3Language = $language->getTypo3Language();
        $localLanguage = $this->localizationFactory->getParsedData($file, $typo3Language);
        foreach ($localLanguage[$typo3Language] ?? [] as $identifier => $values) {
            $item = new Item($identifier);
            $item->setSource($values[0]['source'] ?? '');
            $item->setTarget($values[0]['target'] ?? '');
            $items->addItem($item);
        }
        return $items;
    }

    /**
     * @param string[] $replacedIdentifiers Identifiers that belong to the form currently edited
     */
    public function merge(array $replacedIdentifiers, Typo3Language $language, ItemCollection $submitted): ItemCollection
    {
        $merged = $this->read($language);
        foreach ($replacedIdentifiers as $identifier) {
            $merged->removeItem($identifier);
        }
        foreach ($submitted as $item) {
            if ($item->getTarget() === '') {
                continue;
            }
            $merged->addItem($item);
        }
        return $merged;
    }

    public function save(Typo3Language $language, ItemCollection $items): void
    {
        $this->localizationService->saveXliff($this->getFile(), $language, $items);
    }

    /**
     * @param string[] $identifiers
     */
    public function remove(array $identifiers): void
    {
        if ($identifiers === []) {
            return;
        }
        $files = GeneralUtility::getFilesInDir(dirname($this->getFile()), 'xlf', true);
        if (!is_array($files)) {
            return;
        }
        foreach ($files as $file) {
            $language = self::getLanguageFromFileName(basename($file), self::TRANSLATION_FILE_NAME);
            if ($language === null) {
                continue;
            }
            $languageDto = new Typo3Language($language, $language, '');
            $items = $this->readFromFile($file, $languageDto);
            foreach ($identifiers as $identifier) {
                $items->removeItem($identifier);
            }
            $this->localizationService->saveXliff($this->getFile(), $languageDto, $items);
        }
    }

    public static function getLanguageFromFileName(string $fileName, string $baseFileName): ?string
    {
        if ($fileName === $baseFileName) {
            return 'default';
        }
        $suffix = '.' . $baseFileName;
        if (!str_ends_with($fileName, $suffix)) {
            return null;
        }
        $language = substr($fileName, 0, -strlen($suffix));
        return preg_match('/^[A-Za-z0-9_]+$/', $language) === 1 ? $language : null;
    }
}
