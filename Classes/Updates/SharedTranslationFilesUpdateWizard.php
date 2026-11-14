<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Updates;

use R3H6\FormTranslator\Service\FormService;
use R3H6\FormTranslator\Service\TranslationFileService;
use R3H6\FormTranslator\Translation\Dto\Typo3Language;
use R3H6\FormTranslator\Utility\PathUtility;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SharedTranslationFilesUpdateWizard
{
    public function __construct(
        private readonly FormService $formService,
        private readonly TranslationFileService $translationFileService,
        private readonly FrontendInterface $l10nCache,
    ) {}

    public function getTitle(): string
    {
        return 'Migrate form translations to shared files';
    }

    public function getDescription(): string
    {
        return 'Migrates the per-form XLIFF files registered by form_translator v3 into the shared translation files '
            . 'and removes the translation file registration from the affected form definitions. '
            . 'Forms in mounts that are not visible in the install tool context are not migrated. '
            . 'The old per-form files are left in place.';
    }

    public function getPrerequisites(): array
    {
        return [];
    }

    public function updateNecessary(): bool
    {
        foreach ($this->getForms() as $form) {
            $formDefinition = $this->formService->parseForm((string)$form['persistenceIdentifier']);
            if ($this->getTranslationFile($formDefinition) !== null) {
                return true;
            }
        }
        return false;
    }

    public function executeUpdate(): bool
    {
        foreach ($this->getForms() as $form) {
            $persistenceIdentifier = (string)$form['persistenceIdentifier'];
            $formDefinition = $this->formService->parseForm($persistenceIdentifier);
            if ($this->getTranslationFile($formDefinition) === null) {
                continue;
            }
            $formDefinition = $this->migrateFormDefinition($formDefinition);
            $this->formService->saveForm($persistenceIdentifier, $formDefinition);
        }
        $this->l10nCache->flush();
        return true;
    }

    /**
     * @param array<string, mixed> $formDefinition
     * @return array<string, mixed>
     */
    public function migrateFormDefinition(array $formDefinition): array
    {
        $translationFile = $this->getTranslationFile($formDefinition);
        if ($translationFile === null) {
            return $formDefinition;
        }

        $baseFile = PathUtility::makeAbsolute($translationFile, Environment::getPublicPath());
        $baseFileName = basename($baseFile);
        $files = GeneralUtility::getFilesInDir(dirname($baseFile), $baseFileName, true);
        if (!is_array($files)) {
            $files = [];
        }
        $files[] = $baseFile;

        foreach ($files as $file) {
            $language = TranslationFileService::getLanguageFromFileName(basename($file), $baseFileName);
            if ($language === null) {
                continue;
            }
            $languageDto = new Typo3Language($language, $language, '');
            $items = $this->translationFileService->readFromFile($file, $languageDto);
            if (count($items) === 0) {
                continue;
            }
            $merged = $this->translationFileService->merge(array_keys($items->toArray()), $languageDto, $items);
            $this->translationFileService->save($languageDto, $merged);
        }

        return $this->removeTranslationFile($formDefinition);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getForms(): array
    {
        return array_values(array_filter(
            $this->formService->listForms(),
            static fn(array $form): bool => ($form['invalid'] ?? false) === false
                && ($form['readOnly'] ?? false) === false
        ));
    }

    /**
     * @param array<string, mixed> $formDefinition
     */
    private function getTranslationFile(array $formDefinition): ?string
    {
        return $formDefinition['renderingOptions']['translation']['translationFiles'][TranslationFileService::TRANSLATION_FILE_KEY] ?? null;
    }

    /**
     * @param array<string, mixed> $formDefinition
     * @return array<string, mixed>
     */
    private function removeTranslationFile(array $formDefinition): array
    {
        unset($formDefinition['renderingOptions']['translation']['translationFiles'][TranslationFileService::TRANSLATION_FILE_KEY]);
        if (($formDefinition['renderingOptions']['translation']['translationFiles'] ?? null) === []) {
            unset($formDefinition['renderingOptions']['translation']['translationFiles']);
        }
        if (($formDefinition['renderingOptions']['translation'] ?? null) === []) {
            unset($formDefinition['renderingOptions']['translation']);
        }
        if (($formDefinition['renderingOptions'] ?? null) === []) {
            unset($formDefinition['renderingOptions']);
        }
        return $formDefinition;
    }
}
