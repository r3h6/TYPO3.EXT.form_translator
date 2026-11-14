<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Service;

use R3H6\FormTranslator\Translation\Dto\Typo3Language;
use R3H6\FormTranslator\Translation\ItemCollection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        /** @var ItemCollection $items */
        $items = $variables['items'];
        /** @var Typo3Language $language */
        $language = $variables['language'];
        $isDefaultLanguage = $language->getTypo3Language() === 'default';

        $doc = new \DOMDocument('1.0', 'utf-8');
        $doc->xmlStandalone = true;
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;

        $xliff = $doc->createElement('xliff');
        $xliff->setAttribute('version', '1.0');
        $doc->appendChild($xliff);

        $file = $doc->createElement('file');
        $file->setAttribute('source-language', 'en');
        if (!$isDefaultLanguage) {
            $file->setAttribute('target-language', $language->getTypo3Language());
        }
        $file->setAttribute('datatype', 'plaintext');
        $file->setAttribute('original', (string)$variables['originalFile']);
        $file->setAttribute('date', '');
        $file->setAttribute('product-name', 'form');
        $xliff->appendChild($file);

        $file->appendChild($doc->createElement('header'));

        $body = $doc->createElement('body');
        $file->appendChild($body);

        foreach ($items as $item) {
            $transUnit = $doc->createElement('trans-unit');
            $transUnit->setAttribute('id', $item->getIdentifier());

            $source = $doc->createElement('source');
            $source->appendChild($doc->createTextNode(
                $isDefaultLanguage ? $item->getTarget() : $item->getSource()
            ));
            $transUnit->appendChild($source);

            if (!$isDefaultLanguage) {
                $target = $doc->createElement('target');
                $target->appendChild($doc->createTextNode($item->getTarget()));
                $transUnit->appendChild($target);
            }

            $body->appendChild($transUnit);
        }

        $output = $doc->saveXML();
        if ($output === false) {
            throw new \RuntimeException('Could not save xml', 1641680429711);
        }
        return $output;
    }
}
