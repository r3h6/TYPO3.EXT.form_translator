<?php

namespace R3H6\FormTranslator\Tests\Unit\Parser;

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
        $this->subject = new FormDefinitionLabelsParser();
        $this->form = Yaml::parseFile(GeneralUtility::getFileAbsFileName('EXT:form_translator/Tests/Unit/Fixtures/Form/test.form.yaml'));
    }

    /**
     * @test
     */
    public function resultContainsFormSubmitButtonLabel()
    {
        $result = $this->subject->parse($this->form);
        self::assertArrayHasKey('element.test.renderingOptions.submitButtonLabel', $result);
        self::assertSame('Submit', $result['element.test.renderingOptions.submitButtonLabel']);
    }

    /**
     * @test
     */
    public function resultContainsPrependOptionLabel()
    {
        $result = $this->subject->parse($this->form);
        self::assertArrayHasKey('test.element.singleselect-1.properties.prependOptionLabel', $result);
        self::assertSame('First option', $result['test.element.singleselect-1.properties.prependOptionLabel']);
    }

    /**
     * @test
     */
    public function resultContainsLabelFromText2()
    {
        $result = $this->subject->parse($this->form);
        self::assertArrayHasKey('test.element.text-2.properties.label', $result);
        self::assertSame('Text in grid', $result['test.element.text-2.properties.label']);
    }

    /**
     * @test
     */
    public function resultContainsOptionValues()
    {
        $result = $this->subject->parse($this->form);
        self::assertArrayHasKey('test.element.singleselect-1.properties.options.a', $result);
        self::assertSame('Option A', $result['test.element.singleselect-1.properties.options.a']);
        self::assertArrayHasKey('test.element.singleselect-1.properties.options.b', $result);
        self::assertSame('Option B', $result['test.element.singleselect-1.properties.options.b']);
    }
}
