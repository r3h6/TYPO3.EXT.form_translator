<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Service;

use R3H6\FormTranslator\Translation\Dto\Typo3Language;
use R3H6\FormTranslator\Translation\ItemCollection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class LocalizationService
{
    public function saveXliff(string $locallangFile, Typo3Language $language, ItemCollection $items): void
    {
        $originalFile = $locallangFile;
        $locallangDir = dirname($locallangFile);
        if ($language->getTypo3Language() !== 'default') {
            $locallangFile = $locallangDir . '/' . $language->getTypo3Language() . '.' . basename($locallangFile);
        }

        $filteredItems = array_filter($items->toArray(), fn($item) => $item->getTarget() !== '');
        if (empty($filteredItems)) {
            return;
        }

        GeneralUtility::mkdir_deep($locallangDir);

        if (!file_exists($originalFile)) {
            GeneralUtility::writeFile($originalFile, $this->renderXliff([
                'items' => [],
                'language' => new Typo3Language('default', 'English', 'gb'),
                'originalFile' => $originalFile,
            ]));
        }

        GeneralUtility::writeFile($locallangFile, $this->renderXliff([
            'items' => $filteredItems,
            'language' => $language,
            'originalFile' => $originalFile,
        ]));
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
