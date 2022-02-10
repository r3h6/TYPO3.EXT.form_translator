<?php

namespace R3H6\FormTranslator\Controller;

use R3H6\FormTranslator\Service\FormService;
use R3H6\FormTranslator\Service\LocalizationService;
use R3H6\FormTranslator\Service\SiteLanguageService;
use R3H6\FormTranslator\Translation\ItemCollection;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FormController extends ActionController
{
    /**
     * @var BackendTemplateView
     */
    protected $view;

    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @var SiteLanguageService
     */
    protected $siteLanguageService;

    /**
     * @var FormService
     */
    protected $formService;

    /**
     * @var LocalizationService
     */
    protected $localizationService;

    /**
     * @var FrontendInterface
     */
    protected $l10nCache;

    public function indexAction(): void
    {
        $this->view->assign('siteLanguages', $this->siteLanguageService->findAll());
        $this->view->assign('forms', $this->formService->listForms());

        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $this->addReloadButtonToButtonBar($buttonBar);
        $this->addShortcutButtonToButtonBar($buttonBar, LocalizationUtility::translate('mod.title.index', 'FormTranslator'));
    }

    public function localizeAction(string $persistenceIdentifier, SiteLanguage $siteLanguage): void
    {
        $docTitle = LocalizationUtility::translate('mod.title.localize', 'FormTranslator', [
            $this->formService->getTitle($persistenceIdentifier),
            $siteLanguage->getTitle(),
        ]);

        $this->view->assign('docTitle', $docTitle);
        $this->view->assign('items', $this->formService->getItems($persistenceIdentifier, $siteLanguage));
        $this->view->assign('siteLanguage', $siteLanguage);
        $this->view->assign('persistenceIdentifier', $persistenceIdentifier);

        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $this->addReloadButtonToButtonBar($buttonBar);
        $this->addShortcutButtonToButtonBar($buttonBar, $docTitle);

        $buttonBar->addButton(
            $buttonBar->makeLinkButton()
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:close'))
                ->setShowLabelText(true)
                ->setHref($this->uriBuilder->reset()->uriFor('index'))
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-close', Icon::SIZE_SMALL)),
            ButtonBar::BUTTON_POSITION_LEFT,
            1
        );
        $buttonBar->addButton(
            $buttonBar->makeLinkButton()
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:save'))
                ->setShowLabelText(true)
                ->setHref('#')
                ->setDataAttributes(['save' => '#LocalizationForm'])
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-save', Icon::SIZE_SMALL)),
            ButtonBar::BUTTON_POSITION_LEFT,
            2
        );
    }

    public function saveAction(string $persistenceIdentifier, SiteLanguage $siteLanguage, ItemCollection $items): void
    {
        $locallangFile = $this->formService->getLocallangFileFromPersistenceIdentifier($persistenceIdentifier);
        $this->localizationService->saveXliff($locallangFile, $siteLanguage, $items);
        $this->addFlashMessage('Saved translation to ' . $locallangFile);
        if ($this->formService->isWritable($persistenceIdentifier)) {
            $this->formService->addTranslationFile($persistenceIdentifier, $locallangFile);
        }
        $this->l10nCache->flush();
        $this->redirect('localize', null, null, ['persistenceIdentifier' => $persistenceIdentifier, 'siteLanguage' => $siteLanguage->getLanguageId()]);
    }

    public function injectSiteLanguageService(SiteLanguageService $siteLanguageService): void
    {
        $this->siteLanguageService = $siteLanguageService;
    }

    public function injectFormService(FormService $formService): void
    {
        $this->formService = $formService;
    }

    public function injectLocalizationService(LocalizationService $localizationService): void
    {
        $this->localizationService = $localizationService;
    }

    public function injectL10nCache(FrontendInterface $l10nCache): void
    {
        $this->l10nCache = $l10nCache;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function addReloadButtonToButtonBar(ButtonBar $buttonBar): void
    {
        $buttonBar->addButton(
            $buttonBar->makeLinkButton()
                ->setHref(GeneralUtility::getIndpEnv('REQUEST_URI'))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-refresh', Icon::SIZE_SMALL)),
            ButtonBar::BUTTON_POSITION_RIGHT,
            1
        );
    }

    protected function addShortcutButtonToButtonBar(ButtonBar $buttonBar, string $title): void
    {
        $mayMakeShortcut = $this->getBackendUser()->mayMakeShortcut();
        if ($mayMakeShortcut) {
            $uri = new Uri(GeneralUtility::getIndpEnv('REQUEST_URI'));
            $queryParams = [];
            parse_str($uri->getQuery(), $queryParams);
            $getVars = array_diff(array_keys($queryParams), ['token']);

            $moduleName = $this->controllerContext->getRequest()->getPluginName();
            $shortcutButton = $buttonBar->makeShortcutButton()
                ->setModuleName($moduleName)
                ->setDisplayName($title)
                ->setGetVariables($getVars);
            $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
        }
    }
}
