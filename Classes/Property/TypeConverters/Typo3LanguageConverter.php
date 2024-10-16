<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Property\TypeConverters;

use R3H6\FormTranslator\Service\Typo3LanguageService;
use TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;

class Typo3LanguageConverter extends AbstractTypeConverter
{
    public function __construct(protected Typo3LanguageService $languageService) {}

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function convertFrom($source, string $targetType, array $convertedChildProperties = [], ?PropertyMappingConfigurationInterface $configuration = null)
    {
        $language = $this->languageService->find($source);
        if ($language !== null) {
            return $language;
        }

        throw new TargetNotFoundException('Typo3Language with languageId "' . print_r($source, true) . '" not found', 1640815204297);
    }
}
