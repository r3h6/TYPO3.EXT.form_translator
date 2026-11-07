<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Hooks;

use R3H6\FormTranslator\Service\FormService;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

final class RemoveFormTranslations
{
    public function __construct(
        private readonly FormService $formService,
        private readonly FrontendInterface $l10nCache,
    ) {}

    public function beforeFormDelete(string $formPersistenceIdentifier): void
    {
        $this->formService->removeTranslations($formPersistenceIdentifier);
        $this->l10nCache->flush();
    }
}
