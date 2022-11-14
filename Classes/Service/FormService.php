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
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;

class FormService
{
    protected const TRANSLATION_FILE_KEY = 99;

    /**
     * @var FormDefinitionLabelsParser
     */
    protected $formDefinitionLabelsParser;

    /**
     * @var LocalizationFactory
     */
    protected $localizationFactory;

    /**
     * @var FormPersistenceManagerInterface
     */
    protected $formPersistenceManager;

    /**
     * @var string
     */
    protected $locallangPath;

    public function __construct(
        FormDefinitionLabelsParser $formDefinitionLabelsParser,
        LocalizationFactory $localizationFactory,
        FormPersistenceManagerInterface $formPersistenceManager,
        string $locallangPath
    ) {
        $this->formDefinitionLabelsParser = $formDefinitionLabelsParser;
        $this->localizationFactory = $localizationFactory;
        $this->formPersistenceManager = $formPersistenceManager;
        $this->locallangPath = $locallangPath;
    }

    public function getItems(string $persistenceIdentifier, SiteLanguage $siteLanguage): ItemCollection
    {
        $items = $this->extractLabels($persistenceIdentifier);

        $this->getTranslation($items, $persistenceIdentifier, $siteLanguage);

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
        file_put_contents($formPath, $yaml);
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
