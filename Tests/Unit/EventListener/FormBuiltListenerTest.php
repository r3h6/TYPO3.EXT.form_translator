<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use R3H6\FormTranslator\EventListener\RegisterTranslationFileOnFormBuilt;
use R3H6\FormTranslator\EventListener\TranslateValidationErrorMessagesOnFormBuilt;
use R3H6\FormTranslator\Hooks\RegisterTranslationFile;
use R3H6\FormTranslator\Hooks\TranslateValidationErrorMessages;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement;
use TYPO3\CMS\Form\Event\AfterFormIsBuiltEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FormBuiltListenerTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists(AfterFormIsBuiltEvent::class)) {
            self::markTestSkipped('Requires TYPO3 v14 form events');
        }
    }

    #[Test]
    public function registersTranslationFileOnRootForm(): void
    {
        $form = $this->createMock(FormDefinition::class);
        $hook = $this->createMock(RegisterTranslationFile::class);
        $hook->expects($this->once())->method('register')->with($form);

        (new RegisterTranslationFileOnFormBuilt($hook))(new AfterFormIsBuiltEvent($form));
    }

    #[Test]
    public function translatesValidationErrorMessagesOfAllFormElements(): void
    {
        $element = $this->createMock(AbstractFormElement::class);
        $form = $this->createMock(FormDefinition::class);
        $form->method('getRenderablesRecursively')->willReturn([$element]);

        $hook = $this->createMock(TranslateValidationErrorMessages::class);
        $hook->expects($this->once())->method('translateRenderable')->with($element);

        (new TranslateValidationErrorMessagesOnFormBuilt($hook))(new AfterFormIsBuiltEvent($form));
    }
}
