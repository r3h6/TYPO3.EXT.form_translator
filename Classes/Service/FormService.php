<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Service;

use R3H6\FormTranslator\Facade\FormPersistenceManagerInterface;
use R3H6\FormTranslator\Parser\FormDefinitionLabelsParser;
use R3H6\FormTranslator\Translation\Dto\Typo3Language;
use R3H6\FormTranslator\Translation\Item;
use R3H6\FormTranslator\Translation\ItemCollection;
use R3H6\FormTranslator\Utility\PathUtility;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as FormConfigurationManagerInterface;

class FormService
{
    public const TRANSLATION_FILE_KEY = 99;

    public function __construct(
        protected readonly FormDefinitionLabelsParser $formDefinitionLabelsParser,
        protected readonly LocalizationFactory $localizationFactory,
        protected readonly FormPersistenceManagerInterface $formPersistenceManager,
        protected readonly ConfigurationManagerInterface $configurationManager,
        protected readonly FormConfigurationManagerInterface $extFormConfigurationManager,
        protected string $locallangPath,
    ) {}

    public function getItems(string $persistenceIdentifier, Typo3Language $language): ItemCollection
    {
        $items = $this->extractLabels($persistenceIdentifier);

        $this->getTranslation($items, $persistenceIdentifier, $language);

        return $items;
    }

    public function listForms(): array
    {
        return $this->formPersistenceManager->listForms();
    }

    public function extractLabels(string $persistenceIdentifier): ItemCollection
    {
        $items = new ItemCollection();
        $form = $this->parseForm($persistenceIdentifier);
        foreach ($this->formDefinitionLabelsParser->parse($form) as $identifier => $original) {
            $item = new Item($identifier);
            $item->setOriginal($original);
            $items->addItem($item);
        }
        return $items;
    }

    public function getTranslation(ItemCollection $items, string $persistenceIdentifier, Typo3Language $language): ItemCollection
    {
        $form = $this->parseForm($persistenceIdentifier);
        $dir = Environment::getPublicPath();
        $localLanguage = [];
        $translationFiles = $form['renderingOptions']['translation']['translationFiles'] ?? [];
        foreach ($translationFiles as $translationFile) {
            $path = PathUtility::makeAbsolute($translationFile, $dir);
            $localLanguage = array_replace_recursive($localLanguage, $this->localizationFactory->getParsedData($path, $language->getTypo3Language()));
        }

        if (array_key_exists($language->getTypo3Language(), $localLanguage) && is_array($localLanguage[$language->getTypo3Language()])) {
            foreach ($localLanguage[$language->getTypo3Language()] as $identifier => $values) {
                $item = $items->getItem($identifier) ?? new Item($identifier);
                $item->setSource($values[0]['source']);
                $item->setTarget($values[0]['target']);
                $items->addItem($item);
            }
        }

        return $items;
    }

    public function getTitle(string $persistenceIdentifier): string
    {
        $form = $this->parseForm($persistenceIdentifier);
        return $form['label'] ?? 'Undefined';
    }

    public function parseForm(string $persistenceIdentifier): array
    {
        $path = PathUtility::makeAbsolute($persistenceIdentifier);
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException('Could not read file', 1641680505885);
        }
        return Yaml::parse($content);
    }

    public function addTranslationFile(string $persistenceIdentifier, string $locallangFile): void
    {
        // Normalize locallang path
        $locallangFile = (str_starts_with($locallangFile, Environment::getExtensionsPath())) ?
            'EXT:' . ltrim(str_replace(Environment::getExtensionsPath(), '', $locallangFile), '/') :
            ltrim(str_replace(Environment::getPublicPath(), '', $locallangFile), '/');

        $form = $this->parseForm($persistenceIdentifier);
        $translationFiles = $form['renderingOptions']['translation']['translationFiles'] ?? [];
        if (in_array($locallangFile, $translationFiles)) {
            return;
        }
        $form['renderingOptions']['translation']['translationFiles'][self::TRANSLATION_FILE_KEY] = $locallangFile;
        $yaml = Yaml::dump($form, 99, 2);

        $formPath = PathUtility::makeAbsolute($persistenceIdentifier);
        GeneralUtility::writeFile($formPath, $yaml);
    }

    public function getLocallangFileFromPersistenceIdentifier(string $persistenceIdentifier): string
    {
        $form = $this->parseForm($persistenceIdentifier);
        $translationFile = $form['renderingOptions']['translation']['translationFiles'][self::TRANSLATION_FILE_KEY] ?? null;
        if ($translationFile !== null) {
            return PathUtility::makeAbsolute($translationFile, Environment::getPublicPath());
        }

        $formPath = PathUtility::makeAbsolute($persistenceIdentifier);
        $storage = PathUtility::makeAbsolute($this->locallangPath, dirname($formPath));
        if ($this->isWritable($persistenceIdentifier) === false) {
            $storageIdentifier = (string)array_key_first($this->formPersistenceManager->getAccessibleFormStorageFolders());
            $storage = PathUtility::makeAbsolute($storageIdentifier);
        }

        return rtrim($storage, '/') . '/' . basename($formPath, '.form.yaml') . '.xlf';
    }

    public function isWritable(string $persistenceIdentifier): bool
    {
        if (Environment::getContext()->isDevelopment()) {
            return true;
        }
        foreach ($this->formPersistenceManager->listForms() as $form) {
            if ($form['persistenceIdentifier'] === $persistenceIdentifier && $form['readOnly'] === false) {
                return true;
            }
        }
        return false;
    }
}
