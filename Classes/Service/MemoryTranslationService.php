<?php

namespace R3H6\FormTranslator\Service;

use R3H6\FormTranslator\Translation\ItemCollection;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class MemoryTranslationService implements TranslationServiceInterface
{
    public function __construct(protected FormService $formService) {}

    public function getName(): string
    {
        return 'Memory';
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function translate(string $text, SiteLanguage $targetLanguage): string
    {
        $forms = $this->formService->listForms();
        $items = new ItemCollection();
        foreach ($forms as $form) {
            $this->formService->getTranslation($items, $form['persistenceIdentifier'], $targetLanguage);
        }

        foreach ($items as $item) {
            if ($item->getSource() === $text) {
                return $item->getTarget();
            }
        }
        return '';
    }
}
