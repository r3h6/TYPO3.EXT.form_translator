<?php

namespace R3H6\FormTranslator\Service;

use R3H6\FormTranslator\Parser\FormDefinitionLabelsParser;
use R3H6\FormTranslator\Translation\Item;
use R3H6\FormTranslator\Translation\ItemCollection;
use R3H6\FormTranslator\Utility\PathUtility;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as FormConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;

class FormService
{
    protected const TRANSLATION_FILE_KEY = 99;

    public function __construct(
        protected FormDefinitionLabelsParser $formDefinitionLabelsParser,
        protected LocalizationFactory $localizationFactory,
        protected FormPersistenceManagerInterface $formPersistenceManager,
        protected ConfigurationManagerInterface $configurationManager,
        protected FormConfigurationManagerInterface $extFormConfigurationManager,
        protected string $locallangPath,
    ) {
    }

    public function getItems(string $persistenceIdentifier, SiteLanguage $siteLanguage): ItemCollection
    {
        $items = $this->extractLabels($persistenceIdentifier);

        $this->getTranslation($items, $persistenceIdentifier, $siteLanguage);

        return $items;
    }

    public function listForms(): array
    {
        $settings = $this->getFormSettings();
        return $this->formPersistenceManager->listForms($settings);
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

    public function getTranslation(ItemCollection $items, string $persistenceIdentifier, SiteLanguage $siteLanguage): ItemCollection
    {
        $form = $this->parseForm($persistenceIdentifier);
        $dir = Environment::getPublicPath();
        $localLanguage = [];
        $translationFiles = $form['renderingOptions']['translation']['translationFiles'] ?? [];
        foreach ($translationFiles as $translationFile) {
            $path = PathUtility::makeAbsolute($translationFile, $dir);
            $localLanguage = array_replace_recursive($localLanguage, $this->localizationFactory->getParsedData($path, $siteLanguage->getTypo3Language()));
        }

        if (array_key_exists($siteLanguage->getTypo3Language(), $localLanguage) && is_array($localLanguage[$siteLanguage->getTypo3Language()])) {
            foreach ($localLanguage[$siteLanguage->getTypo3Language()] as $identifier => $values) {
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
        $locallangFile = (strpos($locallangFile, Environment::getExtensionsPath()) === 0) ?
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
            $settings = $this->getFormSettings();
            $storageIdentifier = (string)array_key_first($this->formPersistenceManager->getAccessibleFormStorageFolders($settings));
            $storage = PathUtility::makeAbsolute($storageIdentifier);
        }

        return rtrim($storage, '/') . '/' . basename($formPath, '.form.yaml') . '.xlf';
    }

    public function isWritable(string $persistenceIdentifier): bool
    {
        if (Environment::getContext()->isDevelopment()) {
            return true;
        }
        $settings = $this->getFormSettings();
        foreach ($this->formPersistenceManager->listForms($settings) as $form) {
            if ($form['persistenceIdentifier'] === $persistenceIdentifier && $form['readOnly'] === false) {
                return true;
            }
        }
        return false;
    }

    protected function getFormSettings(): array
    {
        $typoScriptSettings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'form');
        $formSettings = $this->extFormConfigurationManager->getYamlConfiguration($typoScriptSettings, false);
        if (!isset($formSettings['formManager'])) {
            // Config sub array formManager is crucial and should always exist. If it does
            // not, this indicates an issue in config loading logic. Except in this case.
            throw new \LogicException('Configuration could not be loaded', 1723717461);
        }
        return $formSettings;
    }
}
