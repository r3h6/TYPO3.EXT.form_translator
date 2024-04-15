<?php

namespace R3H6\FormTranslator\Service;

use R3H6\FormTranslator\Translation\ItemCollection;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class LocalizationService
{
    public function saveXliff(string $locallangFile, string $locale, ItemCollection $items): string
    {
        $originalFile = $locallangFile;
        $locallangDir = dirname($locallangFile);

        $locallangFile = $locallangDir . '/' . $locale . '.' . basename($locallangFile);

        GeneralUtility::mkdir_deep($locallangDir);

        if (!file_exists($originalFile)) {
            file_put_contents($originalFile, $this->renderXliff([
                'items' => [],
                'siteLanguage' => new SiteLanguage(0, 'en_US.UTF-8', new Uri('/'), []),
                'originalFile' => $originalFile,
            ]));
        }

        file_put_contents($locallangFile, $this->renderXliff([
            'items' => $items,
            'locale' => $locale,
            'originalFile' => $originalFile,
        ]));

        return $locallangFile;
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
