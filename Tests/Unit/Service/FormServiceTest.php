<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use R3H6\FormTranslator\Facade\FormPersistenceManagerInterface;
use R3H6\FormTranslator\Parser\FormDefinitionLabelsParser;
use R3H6\FormTranslator\Service\FormService;
use R3H6\FormTranslator\Service\TranslationFileService;
use R3H6\FormTranslator\Translation\Dto\Typo3Language;
use R3H6\FormTranslator\Translation\ItemCollection;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FormServiceTest extends UnitTestCase
{
    #[Test]
    public function parseFormLoadsDefinitionThroughPersistenceManager(): void
    {
        $formDefinition = [
            'identifier' => 'example',
            'label' => 'Example',
            'renderingOptions' => ['submitButtonLabel' => 'Submit'],
        ];
        $persistenceManager = $this->createMock(FormPersistenceManagerInterface::class);
        $persistenceManager->expects($this->once())
            ->method('loadForm')
            ->with('1:/forms/example.form.yaml')
            ->willReturn($formDefinition);

        $formService = new FormService(
            $this->createMock(FormDefinitionLabelsParser::class),
            $persistenceManager,
            $this->createMock(TranslationFileService::class),
        );

        self::assertSame($formDefinition, $formService->parseForm('1:/forms/example.form.yaml'));
    }

    #[Test]
    public function saveTranslationMergesIdentifiersOfLoadedForm(): void
    {
        $parser = $this->createMock(FormDefinitionLabelsParser::class);
        $parser->method('parse')->willReturn([
            'example.label' => 'Label',
            'example.other' => 'Other',
        ]);

        $persistenceManager = $this->createMock(FormPersistenceManagerInterface::class);
        $persistenceManager->method('loadForm')->willReturn(['identifier' => 'example']);

        $items = new ItemCollection();
        $language = new Typo3Language('de', 'German', 'de');
        $translationFileService = $this->createMock(TranslationFileService::class);
        $translationFileService->expects($this->once())
            ->method('merge')
            ->with(['example.label', 'example.other'], $language, $items)
            ->willReturn(new ItemCollection());
        $translationFileService->expects($this->once())
            ->method('save')
            ->with($language, self::isInstanceOf(ItemCollection::class));

        $formService = new FormService($parser, $persistenceManager, $translationFileService);
        $formService->saveTranslation('1:/forms/example.form.yaml', $language, $items);
    }

    #[Test]
    public function saveFormDelegatesToPersistenceManager(): void
    {
        $formDefinition = ['identifier' => 'example'];
        $persistenceManager = $this->createMock(FormPersistenceManagerInterface::class);
        $persistenceManager->expects($this->once())
            ->method('saveForm')
            ->with('42', $formDefinition);

        $formService = new FormService(
            $this->createMock(FormDefinitionLabelsParser::class),
            $persistenceManager,
            $this->createMock(TranslationFileService::class),
        );

        $formService->saveForm('42', $formDefinition);
    }
}
