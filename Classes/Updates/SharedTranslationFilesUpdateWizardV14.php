<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Updates;

use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;

#[UpgradeWizard('formTranslatorSharedTranslationFiles')]
final class SharedTranslationFilesUpdateWizardV14 extends SharedTranslationFilesUpdateWizard implements UpgradeWizardInterface {}
