<?php

namespace R3H6\FormTranslator\Controller;

use Psr\Http\Message\ResponseInterface;
use R3H6\FormTranslator\Service\FormService;
use R3H6\FormTranslator\Service\LocalizationService;
use R3H6\FormTranslator\Service\SiteLanguageService;
use R3H6\FormTranslator\Translation\ItemCollection;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class FormController extends ActionController
{
    public function __construct(
        protected SiteLanguageService $siteLanguageService,
        protected FormService $formService,
        protected LocalizationService $localizationService,
        protected FrontendInterface $l10nCache,
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected IconFactory $iconFactory,
        protected PageRenderer $pageRenderer,
    ) {
    }

    public function indexAction(): ResponseInterface
    {
        $title = LocalizationUtility::translate('mod.title.index', 'FormTranslator');

        $moduleTemplate = $this->initializeModuleTemplate($title);
        $moduleTemplate->assign('siteLanguages', $this->siteLanguageService->findAll());
        $moduleTemplate->assign('forms', $this->formService->listForms());
        $moduleTemplate->assign('title', $title);

        return $moduleTemplate->renderResponse('Form/Index');
    }

    public function localizeAction(string $persistenceIdentifier, SiteLanguage $siteLanguage): ResponseInterface
    {
        $this->pageRenderer->loadJavaScriptModule('@r3h6/form-translator/Mod.js');
        $this->pageRenderer->addCssFile('EXT:form_translator/Resources/Public/StyleSheets/Mod.css');

        $title = LocalizationUtility::translate('mod.title.localize', 'FormTranslator', [
            $this->formService->getTitle($persistenceIdentifier),
            $siteLanguage->getTitle(),
        ]);

        $moduleTemplate = $this->initializeModuleTemplate($title);
        $moduleTemplate->assign('title', $title);
        $moduleTemplate->assign('items', $this->formService->getItems($persistenceIdentifier, $siteLanguage));
        $moduleTemplate->assign('siteLanguage', $siteLanguage);
        $moduleTemplate->assign('persistenceIdentifier', $persistenceIdentifier);

        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $buttonBar->addButton(
            $buttonBar->makeLinkButton()
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:close'))
                ->setShowLabelText(true)
                ->setHref($this->uriBuilder->reset()->uriFor('index'))
                ->setIcon($this->iconFactory->getIcon('actions-close', IconSize::SMALL->value)),
            ButtonBar::BUTTON_POSITION_LEFT,
            1
        );
        $buttonBar->addButton(
            $buttonBar->makeLinkButton()
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:save'))
                ->setShowLabelText(true)
                ->setHref('#')
                ->setDataAttributes(['save' => '#LocalizationForm'])
                ->setIcon($this->iconFactory->getIcon('actions-save', IconSize::SMALL->value)),
            ButtonBar::BUTTON_POSITION_LEFT,
            2
        );

        return $moduleTemplate->renderResponse('Form/Localize');
    }

    public function saveAction(string $persistenceIdentifier, SiteLanguage $siteLanguage, ItemCollection $items): ResponseInterface
    {
        $locallangFile = $this->formService->getLocallangFileFromPersistenceIdentifier($persistenceIdentifier);
        $this->localizationService->saveXliff($locallangFile, $siteLanguage, $items);
        $this->addFlashMessage('Saved translation to ' . $locallangFile);
        if ($this->formService->isWritable($persistenceIdentifier)) {
            $this->formService->addTranslationFile($persistenceIdentifier, $locallangFile);
        }
        $this->l10nCache->flush();
        return $this->redirect('localize', null, null, ['persistenceIdentifier' => $persistenceIdentifier, 'siteLanguage' => $siteLanguage->getLanguageId()]);
    }

    protected function initializeModuleTemplate(string $title): ModuleTemplate
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setTitle($title);

        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $requestUri = (string)$this->request->getUri();
        $buttonBar->addButton(
            $buttonBar->makeLinkButton()
                ->setHref($requestUri)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
                ->setIcon($this->iconFactory->getIcon('actions-refresh', IconSize::SMALL->value)),
            ButtonBar::BUTTON_POSITION_RIGHT,
            1
        );

        $mayMakeShortcut = $this->getBackendUser()->mayMakeShortcut();
        if ($mayMakeShortcut) {
            $queryParams = [];
            parse_str($this->request->getUri()->getQuery(), $queryParams);
            $getVars = array_diff(array_keys($queryParams), ['token']);

            $shortcutButton = $buttonBar->makeShortcutButton()
                ->setRouteIdentifier('web_FormTranslator')
                ->setDisplayName($title)
                ->setArguments($getVars);
            $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
        }

        return $moduleTemplate;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
