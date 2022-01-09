<?php

namespace R3H6\FormTranslator\Parser;

use Psr\EventDispatcher\EventDispatcherInterface;
use R3H6\FormTranslator\Event\AfterParseFormEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FormDefinitionLabelsParser
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var array<string, string>
     */
    protected $labels = [
        '$[?(@.identifier = "EmailToReceiver")].options.title' => '<form-identifier>.finisher.<finisher-identifier>.title',
        '$[?(@.identifier = "EmailToReceiver")].options.subject' => '<form-identifier>.finisher.<finisher-identifier>.subject',
        '$[?(@.type = "Form")].renderingOptions.submitButtonLabel' => 'element.<form-identifier>.renderingOptions.submitButtonLabel',
        '$[?(@.type = "Page")].renderingOptions.previousButtonLabel' => '<form-identifier>.element.<element-identifier>.renderingOptions.previousButtonLabel',
        '$[?(@.type = "Page")].renderingOptions.nextButtonLabel' => '<form-identifier>.element.<element-identifier>.renderingOptions.nextButtonLabel',
        '$[?(@.type = "SummaryPage")].renderingOptions.previousButtonLabel' => '<form-identifier>.element.<element-identifier>.renderingOptions.previousButtonLabel',
        '$[?(@.type = "SummaryPage")].renderingOptions.nextButtonLabel' => '<form-identifier>.element.<element-identifier>.renderingOptions.nextButtonLabel',
        '$.renderable.label' => '<form-identifier>.element.<element-identifier>.properties.label',
        '$.renderable.properties.fluidAdditionalAttributes.placeholder' => '<form-identifier>.element.<element-identifier>.properties.placeholder',
        '$.renderable.properties.elementDescription' => '<form-identifier>.element.<element-identifier>.properties.elementDescription',
        '$.renderable.properties.prependOptionLabel' => '<form-identifier>.element.<element-identifier>.properties.prependOptionLabel',
    ];

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;

        if (!class_exists('Flow\\JSONPath\\JSONPath')) {
            require_once 'phar://' . GeneralUtility::getFileAbsFileName('EXT:form_translator/Resources/Private/Php/jsonpath.phar') . '/vendor/autoload.php';
        }
    }

    public function parse(array $form): array
    {
        $items = [];

        $json = new \Flow\JSONPath\JSONPath($form);
        $formIdentifier = $form['identifier'];

        $items['element.' . $formIdentifier . '.renderingOptions.submitButtonLabel'] = $form['renderingOptions']['submitButtonLabel'];

        $finishers = $json->find('..finishers[*]')->getData();
        foreach ($finishers as $finisher) {
            $sub = new \Flow\JSONPath\JSONPath(['finisher' => $finisher]);
            $identifier = $finisher['identifier'];
            foreach ($this->labels as $path => $id) {
                $value = $sub->find($path)->getData();
                if (count($value)) {
                    $id = str_replace(['<form-identifier>', '<finisher-identifier>'], [$formIdentifier, $identifier], $id);
                    $items[$id] = implode("\n", $value);
                }
            }
        }

        $renderables = $json->find('..renderables[*]')->getData();
        foreach ($renderables as $renderable) {
            $sub = new \Flow\JSONPath\JSONPath(['renderable' => $renderable]);
            $identifier = $renderable['identifier'];
            foreach ($this->labels as $path => $id) {
                $value = $sub->find($path)->getData();
                if (count($value)) {
                    $id = str_replace(['<form-identifier>', '<element-identifier>'], [$formIdentifier, $identifier], $id);
                    $items[$id] = implode("\n", $value);
                }
            }
            $options = $sub->find('renderable.properties.options')->getData()[0] ?? null;
            if ($options !== null) {
                foreach ($options as $optionValue => $optionLabel) {
                    $id = $formIdentifier . '.element.' . $identifier . '.properties.options.' . $optionValue;
                    $items[$id] = $optionLabel;
                }
            }
        }

        // $validationErrorMessages = $json->find('..validationErrorMessages[*]')->getData();
        // foreach ($validationErrorMessages as $validationErrorMessage) {
        //     $id = str_replace(['<form-identifier>', '<error-code>'], [$formIdentifier, $validationErrorMessage['code']], '<form-identifier>.validation.error.<error-code>');
        //     $items[$id] = '';
        // }

        $event = new AfterParseFormEvent($items);
        $this->dispatcher->dispatch($event);
        return $event->getItems();
    }
}
