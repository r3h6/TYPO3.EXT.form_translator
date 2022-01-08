<?php

namespace R3H6\FormTranslator\Service;

use R3H6\FormTranslator\Translation\Items;
use R3H6\FormTranslator\Utility\PathUtility;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class LocalizationService
{
    /**
     * @var ResourceFactory
     */
    protected $resourceFactory;

    /**
     * @var string
     */
    protected $storage;

    public function __construct(ResourceFactory $resourceFactory, string $storage)
    {
        $this->resourceFactory = $resourceFactory;
        $this->storage = $storage;
    }

    public function saveXliff(string $persistenceIdentifier, SiteLanguage $siteLanguage, Items $items): string
    {
        $languagePrefix = $siteLanguage->getTypo3Language() === 'default' ? '' : $siteLanguage->getTypo3Language() . '.';

        $formPath = PathUtility::getAbsPathForPersistenceIdentifier($persistenceIdentifier);
        $storage = PathUtility::makeAbsolute($this->storage, dirname($formPath));
        $fileName = rtrim($storage, '/') . '/' . $languagePrefix . basename($formPath, '.form.yaml') . '.xlf';
        $originalFile = rtrim($storage, '/') . '/' . basename($formPath, '.form.yaml') . '.xlf';

        GeneralUtility::mkdir_deep($storage);

        if (!file_exists($originalFile)) {
            file_put_contents($originalFile, $this->renderXliff([
                'items' => [],
                'siteLanguage' => new SiteLanguage(0, 'en_US.UTF-8', new Uri('/'), []),
                'originalFile' => $originalFile,
            ]));
        }

        file_put_contents($fileName, $this->renderXliff([
            'items' => $items,
            'siteLanguage' => $siteLanguage,
            'originalFile' => $originalFile,
        ]));

        return PathUtility::makeRelative($originalFile);
    }

    public function renderXliff(array $variables): string
    {
        /** @var StandaloneView $xliff */
        $xliff = GeneralUtility::makeInstance(StandaloneView::class);
        $xliff->setTemplatePathAndFilename('EXT:form_translator/Resources/Private/Templates/Xliff/V1.xlf');
        $xliff->assignMultiple($variables);

        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML($xliff->render());
        $output = $doc->saveXML();
        if ($output === false) {
            throw new \RuntimeException('Could not save xml', 1641680429711);
        }
        return $output;
    }
}
