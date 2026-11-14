<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Hooks;

use R3H6\FormTranslator\Service\TranslationFileService;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;

class RegisterTranslationFile
{
    public function __construct(private readonly TranslationFileService $translationFileService) {}

    public function afterBuildingFinished(RenderableInterface $renderable): void
    {
        if (!$renderable instanceof AbstractRenderable) {
            return;
        }
        $this->register($renderable->getRootForm());
    }

    public function register(FormDefinition $form): void
    {
        $translationFiles = $form->getRenderingOptions()['translation']['translationFiles'] ?? [];
        if (($translationFiles[TranslationFileService::TRANSLATION_FILE_KEY] ?? null) === $this->translationFileService->getReference()) {
            return;
        }
        if (!$this->translationFileService->exists()) {
            return;
        }
        $form->setRenderingOption('translation', [
            'translationFiles' => [
                TranslationFileService::TRANSLATION_FILE_KEY => $this->translationFileService->getReference(),
            ],
        ]);
    }
}
