<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Service;

use R3H6\FormTranslator\Translation\Dto\Typo3Language;
use R3H6\FormTranslator\Translation\Item;
use R3H6\FormTranslator\Translation\ItemCollection;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class TranslationFileService
{
    public const TRANSLATION_FILE_NAME = 'locallang.xlf';
    public const TRANSLATION_FILE_KEY = 99;

    private ?string $resolvedDirectory = null;

    public function __construct(
        protected readonly LocalizationService $localizationService,
        protected readonly StorageRepository $storageRepository,
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
        return $this->getDirectory() . '/' . self::TRANSLATION_FILE_NAME;
    }

    private function getDirectory(): string
    {
        if ($this->resolvedDirectory !== null) {
            return $this->resolvedDirectory;
        }

        [$storageUid, $path] = array_pad(explode(':', $this->translationFilesDirectory, 2), 2, null);
        $path = $path === null ? '' : trim($path, '/');

        if (!MathUtility::canBeInterpretedAsInteger($storageUid) || (int)$storageUid < 1 || $path === '') {
            throw new \InvalidArgumentException(
                sprintf(
                    'translationFilesDirectory must be a FAL combined identifier "<storageUid>:<path>", "%s" given.',
                    $this->translationFilesDirectory
                ),
                1794009600
            );
        }

        if (!GeneralUtility::validPathStr($path)) {
            throw new \InvalidArgumentException(
                sprintf('translationFilesDirectory contains an invalid path "%s".', $path),
                1794009601
            );
        }

        $storage = $this->storageRepository->findByUid((int)$storageUid);
        if ($storage === null) {
            throw new \InvalidArgumentException(
                sprintf('Storage "%d" configured in translationFilesDirectory does not exist.', (int)$storageUid),
                1794009602
            );
        }

        if ($storage->getDriverType() !== 'Local') {
            throw new \InvalidArgumentException(
                sprintf('Storage "%d" configured in translationFilesDirectory is not a local storage.', (int)$storageUid),
                1794009603
            );
        }

        $configuration = $storage->getConfiguration();
        $basePath = (string)($configuration['basePath'] ?? '');
        if ($basePath === '') {
            throw new \InvalidArgumentException(
                sprintf('Storage "%d" configured in translationFilesDirectory has no base path.', (int)$storageUid),
                1794009604
            );
        }

        if ((string)($configuration['pathType'] ?? 'absolute') === 'relative') {
            $basePath = Environment::getPublicPath() . '/' . ltrim($basePath, '/');
        }

        return $this->resolvedDirectory = rtrim($basePath, '/') . '/' . $path;
    }

    public function exists(): bool
    {
        return is_file($this->getFile());
    }

    public function read(Typo3Language $language): ItemCollection
    {
        $file = $this->getFile();
        $typo3Language = $language->getTypo3Language();
        if ($typo3Language !== 'default') {
            $localizedFile = dirname($file) . '/' . $typo3Language . '.' . basename($file);
            if (is_file($localizedFile)) {
                $file = $localizedFile;
            }
        }
        return $this->readFromFile($file, $language);
    }

    public function readFromFile(string $file, Typo3Language $language): ItemCollection
    {
        $items = new ItemCollection();
        if (!is_file($file)) {
            return $items;
        }

        $document = new \DOMDocument();
        $previousErrorHandling = libxml_use_internal_errors(true);
        try {
            if (!$document->load($file)) {
                return $items;
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorHandling);
        }

        foreach ($document->getElementsByTagName('trans-unit') as $transUnit) {
            $source = $this->getElementContent($transUnit, 'source') ?? '';
            $target = $this->getElementContent($transUnit, 'target');
            $item = new Item($transUnit->getAttribute('id'));
            $item->setSource($source);
            // Default-language files only contain <source>; use it as the target.
            $item->setTarget($target ?? $source);
            $items->addItem($item);
        }

        return $items;
    }

    private function getElementContent(\DOMElement $parent, string $tagName): ?string
    {
        $element = $parent->getElementsByTagName($tagName)->item(0);
        return $element instanceof \DOMElement ? $element->textContent : null;
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
