<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Service;

use R3H6\FormTranslator\Facade\FormPersistenceManagerInterface;
use R3H6\FormTranslator\Parser\FormDefinitionLabelsParser;
use R3H6\FormTranslator\Translation\Dto\Typo3Language;
use R3H6\FormTranslator\Translation\Item;
use R3H6\FormTranslator\Translation\ItemCollection;

class FormService
{
    public function __construct(
        protected readonly FormDefinitionLabelsParser $formDefinitionLabelsParser,
        protected readonly FormPersistenceManagerInterface $formPersistenceManager,
        protected readonly TranslationFileService $translationFileService,
    ) {}

    public function getItems(string $persistenceIdentifier, Typo3Language $language): ItemCollection
    {
        $items = $this->extractLabels($persistenceIdentifier);

        foreach ($this->getTranslations($language) as $translation) {
            $item = $items->getItem($translation->getIdentifier());
            if ($item === null) {
                continue;
            }
            $item->setSource($translation->getSource());
            $item->setTarget($translation->getTarget());
        }

        return $items;
    }

    public function getTranslations(Typo3Language $language): ItemCollection
    {
        return $this->translationFileService->read($language);
    }

    public function saveTranslation(string $persistenceIdentifier, Typo3Language $language, ItemCollection $items): void
    {
        $identifiers = array_keys($this->extractLabels($persistenceIdentifier)->toArray());
        $merged = $this->translationFileService->merge($identifiers, $language, $items);
        $this->translationFileService->save($language, $merged);
    }

    public function removeTranslations(string $persistenceIdentifier): void
    {
        $identifiers = array_keys($this->extractLabels($persistenceIdentifier)->toArray());
        $this->translationFileService->remove($identifiers);
    }

    public function getTranslationFileReference(): string
    {
        return $this->translationFileService->getReference();
    }

    public function listForms(): array
    {
        return $this->formPersistenceManager->listForms();
    }

    public function extractLabels(string $persistenceIdentifier): ItemCollection
    {
        $items = new ItemCollection();
        $form = $this->parseForm($persistenceIdentifier);
        foreach ($this->formDefinitionLabelsParser->parse($form) as $identifier => $original) {
            $item = new Item($identifier);
            $item->setOriginal($original);
            $items->addItem($item);
        }
        return $items;
    }

    public function getTitle(string $persistenceIdentifier): string
    {
        $form = $this->parseForm($persistenceIdentifier);
        return $form['label'] ?? 'Undefined';
    }

    public function parseForm(string $persistenceIdentifier): array
    {
        return $this->formPersistenceManager->loadForm($persistenceIdentifier);
    }

    /**
     * @param array<string, mixed> $formDefinition
     */
    public function saveForm(string $persistenceIdentifier, array $formDefinition): void
    {
        $this->formPersistenceManager->saveForm($persistenceIdentifier, $formDefinition);
    }
}
