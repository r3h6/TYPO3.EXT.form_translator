<?php

namespace R3H6\FormTranslator\Facade;

use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as FormConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;

class FormPersistenceManagerV13 implements FormPersistenceManagerInterface
{
    public function __construct(
        private readonly FormPersistenceManager $formPersistenceManager,
        private readonly ConfigurationManagerInterface $configurationManager,
        private readonly FormConfigurationManagerInterface $extFormConfigurationManager,
    ) {}

    public function listForms(): array
    {
        $settings = $this->getFormSettings();
        return $this->formPersistenceManager->listForms($settings);
    }

    public function getAccessibleFormStorageFolders(): array
    {
        $settings = $this->getFormSettings();
        return $this->formPersistenceManager->getAccessibleFormStorageFolders($settings);
    }

    private function getFormSettings(): array
    {
        $typoScriptSettings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'form');
        $formSettings = $this->extFormConfigurationManager->getYamlConfiguration($typoScriptSettings, false);
        if (!isset($formSettings['formManager'])) {
            // Config sub array formManager is crucial and should always exist. If it does
            // not, this indicates an issue in config loading logic. Except in this case.
            throw new \LogicException('Configuration could not be loaded', 1723717461);
        }
        return $formSettings;
    }
}
