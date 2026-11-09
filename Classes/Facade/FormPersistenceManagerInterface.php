<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Facade;

interface FormPersistenceManagerInterface
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listForms(): array;

    /**
     * @return array<string, mixed>
     */
    public function loadForm(string $persistenceIdentifier): array;

    /**
     * @param array<string, mixed> $formDefinition
     */
    public function saveForm(string $persistenceIdentifier, array $formDefinition): void;
}
