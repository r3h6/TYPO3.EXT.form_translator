<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Property\TypeConverters;

use R3H6\FormTranslator\Service\SiteLanguageService;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;

class SiteLanguageConverter extends AbstractTypeConverter
{
    protected $sourceTypes = ['int', 'string'];
    protected $targetType = SiteLanguage::class;
    protected $priority = 1;

    public function __construct(protected SiteLanguageService $siteLanguageService) {}

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function convertFrom($source, string $targetType, array $convertedChildProperties = [], ?PropertyMappingConfigurationInterface $configuration = null)
    {
        if (MathUtility::canBeInterpretedAsInteger($source)) {
            $siteLanguages = $this->siteLanguageService->findAll();
            if (isset($siteLanguages[(int)$source])) {
                return $siteLanguages[(int)$source];
            }
        }
        throw new TargetNotFoundException('SiteLanguage with languageId "' . print_r($source, true) . '" not found', 1640815204297);
    }
}
