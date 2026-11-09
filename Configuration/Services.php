<?php

declare(strict_types=1);

namespace R3H6\FormTranslator;

use R3H6\FormTranslator\Facade\FormPersistenceManagerInterface;
use R3H6\FormTranslator\Facade\FormPersistenceManagerV13;
use R3H6\FormTranslator\Facade\FormPersistenceManagerV14;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

return function (ContainerConfigurator $container, ContainerBuilder $containerBuilder): void {
    $typo3Version = VersionNumberUtility::getCurrentTypo3Version();

    if (version_compare($typo3Version, '14.2.0', '>=')) {
        $containerBuilder->setAlias(FormPersistenceManagerInterface::class, FormPersistenceManagerV14::class);
    } else {
        $containerBuilder->setAlias(FormPersistenceManagerInterface::class, FormPersistenceManagerV13::class);
    }

    // Use literal class names: the inactive version's class cannot be reflected
    // by static analysis against the installed TYPO3 version.
    $upgradeWizardClass = version_compare($typo3Version, '14.0.0', '>=')
        ? 'R3H6\\FormTranslator\\Updates\\SharedTranslationFilesUpdateWizardV14'
        : 'R3H6\\FormTranslator\\Updates\\SharedTranslationFilesUpdateWizardV13';

    $containerBuilder->register($upgradeWizardClass, $upgradeWizardClass)
        ->setAutowired(true)
        ->setAutoconfigured(true)
        ->setPublic(true);
};
