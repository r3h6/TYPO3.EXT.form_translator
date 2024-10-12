<?php

namespace R3H6\FormTranslator\Facade;

interface FormPersistenceManagerInterface
{
    public function listForms(): array;

    public function getAccessibleFormStorageFolders(): array;
}
