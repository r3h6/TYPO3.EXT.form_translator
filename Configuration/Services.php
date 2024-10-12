<?php

declare(strict_types=1);

namespace R3H6\FormTranslator;

use R3H6\FormTranslator\Facade\FormPersistenceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

return function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {

    if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '13.0.0', '<')) {
        $containerBuilder->setAlias(FormPersistenceManagerInterface::class, \R3H6\FormTranslator\Facade\FormPersistenceManagerV12::class);
        return;
    }

    $containerBuilder->setAlias(FormPersistenceManagerInterface::class, \R3H6\FormTranslator\Facade\FormPersistenceManagerV13::class);
};
