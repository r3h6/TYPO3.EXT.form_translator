<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Parser;

use Flow\JSONPath\JSONPath;
use Psr\EventDispatcher\EventDispatcherInterface;
use R3H6\FormTranslator\Event\AfterParseFormEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FormDefinitionLabelsParser
{
    /**
     * @var array<string, string>
     */
    protected $labels = [
        '$[?(@.identifier = "Confirmation")].options.message' => '<form-identifier>.finisher.<finisher-identifier>.message',
        '$[?(@.identifier = "EmailToReceiver")].options.title' => '<form-identifier>.finisher.<finisher-identifier>.title',
        '$[?(@.identifier = "EmailToReceiver")].options.subject' => '<form-identifier>.finisher.<finisher-identifier>.subject',
        '$[?(@.identifier = "EmailToReceiver")].options.senderName' => '<form-identifier>.finisher.<finisher-identifier>.senderName',
        '$[?(@.identifier = "EmailToSender")].options.title' => '<form-identifier>.finisher.<finisher-identifier>.title',
        '$[?(@.identifier = "EmailToSender")].options.subject' => '<form-identifier>.finisher.<finisher-identifier>.subject',
        '$[?(@.identifier = "EmailToSender")].options.senderName' => '<form-identifier>.finisher.<finisher-identifier>.senderName',
        '$[?(@.type = "Form")].renderingOptions.submitButtonLabel' => 'element.<form-identifier>.renderingOptions.submitButtonLabel',
        '$[?(@.type = "Page")].renderingOptions.previousButtonLabel' => '<form-identifier>.element.<element-identifier>.renderingOptions.previousButtonLabel',
        '$[?(@.type = "Page")].renderingOptions.nextButtonLabel' => '<form-identifier>.element.<element-identifier>.renderingOptions.nextButtonLabel',
        '$[?(@.type = "SummaryPage")].renderingOptions.previousButtonLabel' => '<form-identifier>.element.<element-identifier>.renderingOptions.previousButtonLabel',
        '$[?(@.type = "SummaryPage")].renderingOptions.nextButtonLabel' => '<form-identifier>.element.<element-identifier>.renderingOptions.nextButtonLabel',
        '$.renderable.label' => '<form-identifier>.element.<element-identifier>.properties.label',
        '$.renderable.properties.fluidAdditionalAttributes.placeholder' => '<form-identifier>.element.<element-identifier>.properties.placeholder',
        '$.renderable.properties.elementDescription' => '<form-identifier>.element.<element-identifier>.properties.elementDescription',
        '$.renderable.properties.prependOptionLabel' => '<form-identifier>.element.<element-identifier>.properties.prependOptionLabel',
        '$.renderable.properties.text' => '<form-identifier>.element.<element-identifier>.properties.text',
        '$.renderable.properties.linkText' => '<form-identifier>.element.<element-identifier>.properties.linkText',
    ];

    public function __construct(protected EventDispatcherInterface $dispatcher)
    {
        if (!class_exists('Flow\\JSONPath\\JSONPath')) {
            require_once 'phar://' . GeneralUtility::getFileAbsFileName('EXT:form_translator/Resources/Private/Php/jsonpath.phar') . '/vendor/autoload.php';
        }
    }

    public function parse(array $form): array
    {
        $items = [];

        $json = new JSONPath($form);
        $formIdentifier = $form['identifier'];

        $items['element.' . $formIdentifier . '.renderingOptions.submitButtonLabel'] = $form['renderingOptions']['submitButtonLabel'];

        $finishers = $json->find('..finishers[*]')->getData();
        foreach ($finishers as $finisher) {
            $sub = new JSONPath(['finisher' => $finisher]);
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
            $sub = new JSONPath(['renderable' => $renderable]);
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
            $validationErrorMessages = $sub->find('renderable.properties.validationErrorMessages[*]')->getData();
            if (!empty($validationErrorMessages)) {
                foreach ($validationErrorMessages as $message) {
                    $id = $formIdentifier . '.validation.error.' . $identifier . '.' . $message['code'];
                    $items[$id] = $message['message'];
                }
            }
        }

        $event = new AfterParseFormEvent($items);
        $this->dispatcher->dispatch($event);
        return $event->getItems();
    }
}
