<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Hooks;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;

final class TranslateValidationErrorMessages
{
    public function __construct(
        private readonly LanguageServiceFactory $languageServiceFactory
    ) {}

    public function afterBuildingFinished(RenderableInterface $renderable): void
    {
        if (!$renderable instanceof AbstractFormElement) {
            return;
        }

        $validationErrorMessages = $renderable->getProperties()['validationErrorMessages'] ?? [];
        if (empty($validationErrorMessages)) {
            return;
        }

        $validationErrorMessages = array_map(fn($message) => $this->translate($message, $renderable), $validationErrorMessages);
        $renderable->setProperty('validationErrorMessages', $validationErrorMessages);
    }

    private function translate(array $message, AbstractFormElement $renderable): array
    {
        $form = $renderable->getRootForm();
        $id = str_replace([
            '<form-identifier>',
            '<element-identifier>',
            '<error-code>',
        ], [
            $form->getRenderingOptions()['_originalIdentifier'],
            $renderable->getIdentifier(),
            $message['code'],
        ], '<form-identifier>.validation.error.<element-identifier>.<error-code>');

        $translationFiles = $form->getRenderingOptions()['translation']['translationFiles'] ?? [];
        $translationServie = $this->getLanguageService();
        foreach ($translationFiles as $translationFile) {
            $translationServie->includeLLFile($translationFile);
            $input = 'LLL:' . $translationFile . ':' . $id;
            $label = $translationServie->sL($input);
            if ($label && $label !== $input) {
                $message['message'] = $label;
                break;
            }
        }

        return $message;
    }

    private function getLanguageService(): LanguageService
    {
        $siteLanguage = $this->getRequest()->getAttribute('language');
        return $this->languageServiceFactory->createFromSiteLanguage($siteLanguage);
    }

    private function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }
}
