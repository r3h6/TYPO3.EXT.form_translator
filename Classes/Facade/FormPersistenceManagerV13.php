<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Facade;

use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Configuration\Exception\NoServerRequestGivenException;
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

    private function getFormSettings(): array
    {
        try {
            $typoScriptSettings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'form');
        } catch (NoServerRequestGivenException) {
            // CLI and install-tool contexts have no Extbase request; use the form defaults.
            $typoScriptSettings = [
                'yamlConfigurations' => [
                    10 => 'EXT:form/Configuration/Yaml/FormSetup.yaml',
                ],
            ];
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
