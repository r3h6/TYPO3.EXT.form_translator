<?php

namespace R3H6\FormTranslator\Service;

use R3H6\FormTranslator\Parser\FormDefinitionLabelsParser;
use R3H6\FormTranslator\Translation\Item;
use R3H6\FormTranslator\Translation\Items;
use R3H6\FormTranslator\Utility\PathUtility;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;

class FormService
{
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

    public function __construct(FormDefinitionLabelsParser $formDefinitionLabelsParser, LocalizationFactory $localizationFactory, FormPersistenceManagerInterface $formPersistenceManager)
    {
        $this->formDefinitionLabelsParser = $formDefinitionLabelsParser;
        $this->localizationFactory = $localizationFactory;
        $this->formPersistenceManager = $formPersistenceManager;
    }

    public function getItems(string $persistenceIdentifier, SiteLanguage $siteLanguage): Items
    {
        $items = $this->extractLabels($persistenceIdentifier);

        $this->getTranslation($items, $persistenceIdentifier, $siteLanguage);

        return $items;
    }

    public function listForms(): array
    {
        return $this->formPersistenceManager->listForms();
    }

    public function extractLabels(string $persistenceIdentifier): Items
    {
        $items = new Items();
        $form = $this->parseForm($persistenceIdentifier);
        foreach ($this->formDefinitionLabelsParser->parse($form) as $identifier => $original) {
            $item = new Item($identifier);
            $item->setOriginal($original);
            $items->addItem($item);
        }
        return $items;
    }

    public function getTranslation(Items $items, string $persistenceIdentifier, SiteLanguage $siteLanguage): Items
    {
        $form = $this->parseForm($persistenceIdentifier);
        $dir = dirname(PathUtility::getAbsPathForPersistenceIdentifier($persistenceIdentifier));
        $localLanguage = [];
        $translationFiles = $form['renderingOptions']['translation']['translationFiles'] ?? [];
        foreach ($translationFiles as $translationFile) {
            $path = PathUtility::makeAbsolute($translationFile, $dir);
            $localLanguage = array_replace_recursive($localLanguage, $this->localizationFactory->getParsedData($path, $siteLanguage->getTypo3Language()));
        }

        if (is_array($localLanguage[$siteLanguage->getTypo3Language()])) {
            foreach ($localLanguage[$siteLanguage->getTypo3Language()] as $identifier => $values) {
                $item = $items->getItem($identifier) ?? new Item($identifier);
                $item->setSource($values[0]['source']);
                $item->setTarget($values[0]['target']);
                $items->addItem($item);
            }
        }

        return $items;
    }

    public function parseForm(string $persistenceIdentifier): array
    {
        $path = PathUtility::getAbsPathForPersistenceIdentifier($persistenceIdentifier);
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException('Could not read file', 1641680505885);
        }
        return Yaml::parse($content);
    }

    public function addTranslation(string $persistenceIdentifier, string $fileName): void
    {
        $form = $this->parseForm($persistenceIdentifier);
        $translationFiles = $form['renderingOptions']['translation']['translationFiles'] ?? [];
        if (in_array($fileName, $translationFiles)) {
            return;
        }
        $form['renderingOptions']['translation']['translationFiles'][time()] = $fileName;
        $yaml = Yaml::dump($form, 99, 2);

        $path = PathUtility::getAbsPathForPersistenceIdentifier($persistenceIdentifier);
        file_put_contents($path, $yaml);
    }
}
