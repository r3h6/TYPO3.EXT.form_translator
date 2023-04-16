<?php

namespace R3H6\FormTranslator\Controller;

use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Page\PageRenderer;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use R3H6\FormTranslator\Service\FormService;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use R3H6\FormTranslator\Translation\ItemCollection;
use R3H6\FormTranslator\Service\LocalizationService;
use R3H6\FormTranslator\Service\SiteLanguageService;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FormController extends ActionController
{
    protected SiteLanguageService $siteLanguageService;
    protected FormService $formService;
    protected LocalizationService $localizationService;
    protected FrontendInterface $l10nCache;
    protected ModuleTemplateFactory $moduleTemplateFactory;
    protected IconFactory $iconFactory;
    protected PageRenderer $pageRenderer;

    public function __construct(
        SiteLanguageService $siteLanguageService,
        FormService $formService,
        LocalizationService $localizationService,
        FrontendInterface $l10nCache,
        ModuleTemplateFactory $moduleTemplateFactory,
        IconFactory $iconFactory,
        PageRenderer $pageRenderer
    ) {
        $this->siteLanguageService = $siteLanguageService;
        $this->formService = $formService;
        $this->localizationService = $localizationService;
        $this->l10nCache = $l10nCache;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->iconFactory = $iconFactory;
        $this->pageRenderer = $pageRenderer;
    }

    public function indexAction(): ResponseInterface
    {
        $this->view->assign('siteLanguages', $this->siteLanguageService->findAll());
        $this->view->assign('forms', $this->formService->listForms());

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setContent($this->view->render());
        $this->addReloadButtonToButtonBar($moduleTemplate);
        $this->addShortcutButtonToButtonBar($moduleTemplate, LocalizationUtility::translate('mod.title.index', 'FormTranslator'));
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    public function localizeAction(string $persistenceIdentifier, SiteLanguage $siteLanguage): ResponseInterface
    {
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/FormTranslator/Mod', null, true); // @todo Migrate to loadJavaScriptModule
        $this->pageRenderer->addCssFile('EXT:form_translator/Resources/Public/StyleSheets/Mod.css');

        $docTitle = LocalizationUtility::translate('mod.title.localize', 'FormTranslator', [
            $this->formService->getTitle($persistenceIdentifier),
            $siteLanguage->getTitle(),
        ]);

        $this->view->assign('docTitle', $docTitle);
        $this->view->assign('items', $this->formService->getItems($persistenceIdentifier, $siteLanguage));
        $this->view->assign('siteLanguage', $siteLanguage);
        $this->view->assign('persistenceIdentifier', $persistenceIdentifier);

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setContent($this->view->render());

        $this->addReloadButtonToButtonBar($moduleTemplate);
        $this->addShortcutButtonToButtonBar($moduleTemplate, $docTitle);

        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $buttonBar->addButton(
            $buttonBar->makeLinkButton()
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:close'))
                ->setShowLabelText(true)
                ->setHref($this->uriBuilder->reset()->uriFor('index'))
                ->setIcon($this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL)),
            ButtonBar::BUTTON_POSITION_LEFT,
            1
        );
        $buttonBar->addButton(
            $buttonBar->makeLinkButton()
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:save'))
                ->setShowLabelText(true)
                ->setHref('#')
                ->setDataAttributes(['save' => '#LocalizationForm'])
                ->setIcon($this->iconFactory->getIcon('actions-save', Icon::SIZE_SMALL)),
            ButtonBar::BUTTON_POSITION_LEFT,
            2
        );

        return $this->htmlResponse($moduleTemplate->renderContent());
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

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function addReloadButtonToButtonBar(ModuleTemplate $moduleTemplate): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        /** @var string $requestUri */
        $requestUri = GeneralUtility::getIndpEnv('REQUEST_URI');
        $buttonBar->addButton(
            $buttonBar->makeLinkButton()
                ->setHref($requestUri)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
                ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL)),
            ButtonBar::BUTTON_POSITION_RIGHT,
            1
        );
    }

    protected function addShortcutButtonToButtonBar(ModuleTemplate $moduleTemplate, string $title): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
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
    }
}
