<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Facade;

use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Configuration\Exception\NoServerRequestGivenException;
use TYPO3\CMS\Form\Domain\DTO\FormMetadata;
use TYPO3\CMS\Form\Domain\DTO\SearchCriteria;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as FormConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;

class FormPersistenceManagerV14 implements FormPersistenceManagerInterface
{
    public function __construct(
        private readonly FormPersistenceManager $formPersistenceManager,
        private readonly ConfigurationManagerInterface $configurationManager,
        private readonly FormConfigurationManagerInterface $extFormConfigurationManager,
    ) {}

    public function listForms(): array
    {
        return array_values(array_map(
            static fn(FormMetadata $form): array => $form->toArray(),
            $this->formPersistenceManager->listForms($this->getFormSettings(), new SearchCriteria())
        ));
    }

    public function loadForm(string $persistenceIdentifier): array
    {
        return $this->formPersistenceManager->load($persistenceIdentifier);
    }

    public function saveForm(string $persistenceIdentifier, array $formDefinition): void
    {
        $this->formPersistenceManager->save($persistenceIdentifier, $formDefinition, $this->getFormSettings());
    }

    private function getFormSettings(): array
    {
        try {
            $typoScriptSettings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'form');
        } catch (NoServerRequestGivenException) {
            // CLI and install-tool contexts have no Extbase request; form YAML is
            // auto-discovered from Configuration/Form/<Set>/ in TYPO3 v14.
            $typoScriptSettings = [];
        }
        $formSettings = $this->extFormConfigurationManager->getYamlConfiguration($typoScriptSettings, false);
        if (!isset($formSettings['formManager'])) {
            // Config sub array formManager is crucial and should always exist. If it does
            // not, this indicates an issue in config loading logic. Except in this case.
            throw new \LogicException('Configuration could not be loaded', 1723717461);
        }
        return $formSettings;
    }
}
