<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Updates;

use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('formTranslatorSharedTranslationFiles')]
final class SharedTranslationFilesUpdateWizardV13 extends SharedTranslationFilesUpdateWizard implements UpgradeWizardInterface {}
