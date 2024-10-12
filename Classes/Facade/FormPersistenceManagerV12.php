<?php

namespace R3H6\FormTranslator\Facade;

use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;

class FormPersistenceManagerV12 implements FormPersistenceManagerInterface
{
    public function __construct(
        private readonly FormPersistenceManager $formPersistenceManager,
    ) {}

    public function listForms(): array
    {
        return $this->formPersistenceManager->listForms();
    }

    public function getAccessibleFormStorageFolders(): array
    {
        return $this->formPersistenceManager->getAccessibleFormStorageFolders();
    }
}
