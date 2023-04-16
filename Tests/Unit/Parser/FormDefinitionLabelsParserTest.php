<?php

namespace R3H6\FormTranslator\Tests\Unit\Parser;

use Psr\EventDispatcher\EventDispatcherInterface;
use R3H6\FormTranslator\Parser\FormDefinitionLabelsParser;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FormDefinitionLabelsParserTest extends UnitTestCase
{
    /**
     * @var FormDefinitionLabelsParser
     */
    protected $subject;

    /**
     * @var array
     */
    protected $form;

    public function setUp(): void
    {
        parent::setUp();
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->subject = new FormDefinitionLabelsParser($dispatcher);
        $this->form = Yaml::parseFile(GeneralUtility::getFileAbsFileName('EXT:form_translator/Tests/Unit/Fixtures/Form/test.form.yaml'));
    }

    /**
     * @test
     */
    public function resultContainsFormSubmitButtonLabel(): void
    {
        $result = $this->subject->parse($this->form);
        self::assertArrayHasKey('element.test.renderingOptions.submitButtonLabel', $result);
        self::assertSame('Submit', $result['element.test.renderingOptions.submitButtonLabel']);
    }

    /**
     * @test
     */
    public function resultContainsLabelFromText2(): void
    {
        $result = $this->subject->parse($this->form);
        self::assertArrayHasKey('test.element.text-2.properties.label', $result);
        self::assertSame('Text in grid', $result['test.element.text-2.properties.label']);
    }

    /**
     * @test
     */
    public function resultContainsPrependOptionLabel(): void
    {
        $result = $this->subject->parse($this->form);
        self::assertArrayHasKey('test.element.singleselect-1.properties.prependOptionLabel', $result);
        self::assertSame('First option', $result['test.element.singleselect-1.properties.prependOptionLabel']);
    }

    /**
     * @test
     */
    public function resultContainsOptionValues(): void
    {
        $result = $this->subject->parse($this->form);
        self::assertArrayHasKey('test.element.singleselect-1.properties.options.a', $result);
        self::assertSame('Option A', $result['test.element.singleselect-1.properties.options.a']);
        self::assertArrayHasKey('test.element.singleselect-1.properties.options.b', $result);
        self::assertSame('Option B', $result['test.element.singleselect-1.properties.options.b']);
    }

    /**
     * @test
     */
    public function resultContainsEmailToSenderLabels(): void
    {
        $result = $this->subject->parse($this->form);
        self::assertArrayHasKey('test.finisher.EmailToSender.subject', $result);
        self::assertSame('Subject', $result['test.finisher.EmailToSender.subject']);
        self::assertArrayHasKey('test.finisher.EmailToSender.title', $result);
        self::assertSame('Fluid email title', $result['test.finisher.EmailToSender.title']);
        self::assertArrayHasKey('test.finisher.EmailToSender.senderName', $result);
        self::assertSame('Test', $result['test.finisher.EmailToSender.senderName']);
    }

    /**
     * @test
     */
    public function resultContainsEmailToReceiverLabels(): void
    {
        $result = $this->subject->parse($this->form);
        self::assertArrayHasKey('test.finisher.EmailToReceiver.subject', $result);
        self::assertSame('Subject', $result['test.finisher.EmailToReceiver.subject']);
        self::assertArrayHasKey('test.finisher.EmailToReceiver.title', $result);
        self::assertSame('Fluid email title', $result['test.finisher.EmailToReceiver.title']);
        self::assertArrayHasKey('test.finisher.EmailToReceiver.senderName', $result);
        self::assertSame('Test', $result['test.finisher.EmailToReceiver.senderName']);
    }

    /**
     * @test
     */
    public function resultContainsConfirmationMessage(): void
    {
        $result = $this->subject->parse($this->form);
        self::assertArrayHasKey('test.finisher.Confirmation.message', $result);
        self::assertSame('Confirmation message', $result['test.finisher.Confirmation.message']);
    }

    /**
     * @test
     */
    public function resultContainsStaticText(): void
    {
        $result = $this->subject->parse($this->form);
        self::assertArrayHasKey('test.element.statictext-1.properties.text', $result);
        self::assertSame('Lorem ipsum dolores...', $result['test.element.statictext-1.properties.text']);
    }

    /**
     * @test
     */
    public function resultContainsStandardFields(): void
    {
        $result = $this->subject->parse($this->form);
        self::assertArrayHasKey('test.element.text-1.properties.label', $result);
        self::assertSame('Text', $result['test.element.text-1.properties.label']);
        self::assertArrayHasKey('test.element.text-1.properties.placeholder', $result);
        self::assertSame('Text placeholder', $result['test.element.text-1.properties.placeholder']);
        self::assertArrayHasKey('test.element.text-1.properties.elementDescription', $result);
        self::assertSame('Text description', $result['test.element.text-1.properties.elementDescription']);
    }
}
