<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Hooks;

use R3H6\FormTranslator\Service\FormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class DeleteTranslationFile
{
    public function __construct(private readonly FormService $formService) {}

    public function beforeFormDelete(string $formPersistenceIdentifier): void
    {
        $form = $this->formService->parseForm($formPersistenceIdentifier);
        $translationFile = $form['renderingOptions']['translation']['translationFiles'][FormService::TRANSLATION_FILE_KEY] ?? null;
        if ($translationFile === null) {
            return;
        }
        $path = GeneralUtility::getFileAbsFileName($translationFile);
        if (!file_exists($path)) {
            return;
        }
        $directory = dirname($path);
        $basename = basename($path);

        $paths = (array)GeneralUtility::getFilesInDir($directory, $basename, true);
        $paths[] = $path;
        foreach ($paths as $path) {
            unlink($path);
        }
    }
}
