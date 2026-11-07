<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Tests\Unit\Hooks;

use PHPUnit\Framework\Attributes\Test;
use R3H6\FormTranslator\Hooks\RegisterTranslationFile;
use R3H6\FormTranslator\Service\TranslationFileService;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RegisterTranslationFileTest extends UnitTestCase
{
    #[Test]
    public function registersSharedFileAndKeepsExistingTranslationFiles(): void
    {
        $translationFileService = $this->createTranslationFileService(
            'fileadmin/form_translations/locallang.xlf',
            true
        );
        $form = $this->createForm();
        $page = $this->createPage($form);

        (new RegisterTranslationFile($translationFileService))->afterBuildingFinished($page);

        $translationFiles = $form->getRenderingOptions()['translation']['translationFiles'];
        self::assertSame('EXT:form/Resources/Private/Language/locallang.xlf', $translationFiles[10]);
        self::assertSame('fileadmin/form_translations/locallang.xlf', $translationFiles[TranslationFileService::TRANSLATION_FILE_KEY]);
    }

    #[Test]
    public function doesNotRegisterMissingFile(): void
    {
        $translationFileService = $this->createTranslationFileService(
            'fileadmin/form_translations/locallang.xlf',
            false
        );
        $form = $this->createForm();
        $page = $this->createPage($form);

        (new RegisterTranslationFile($translationFileService))->afterBuildingFinished($page);

        $translationFiles = $form->getRenderingOptions()['translation']['translationFiles'];
        self::assertArrayNotHasKey(TranslationFileService::TRANSLATION_FILE_KEY, $translationFiles);
    }

    private function createTranslationFileService(string $reference, bool $exists): TranslationFileService
    {
        $translationFileService = $this->createMock(TranslationFileService::class);
        $translationFileService->method('getReference')->willReturn($reference);
        $translationFileService->method('exists')->willReturn($exists);

        return $translationFileService;
    }

    private function createForm(): FormDefinition
    {
        $form = new FormDefinition('example');
        $form->setRenderingOption('translation', [
            'translationFiles' => [10 => 'EXT:form/Resources/Private/Language/locallang.xlf'],
        ]);
        return $form;
    }

    private function createPage(FormDefinition $form): Page
    {
        $page = new Page('page-1');
        $page->setParentRenderable($form);
        return $page;
    }
}
