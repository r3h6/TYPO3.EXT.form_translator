<?php

namespace R3H6\FormTranslator\Controller;

use R3H6\FormTranslator\Service\FormService;
use R3H6\FormTranslator\Service\LocalizationService;
use R3H6\FormTranslator\Service\SiteLanguageService;
use R3H6\FormTranslator\Translation\Items;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

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
    }

    public function localizeAction(string $persistenceIdentifier, SiteLanguage $siteLanguage): void
    {
        $this->view->assign('items', $this->formService->getItems($persistenceIdentifier, $siteLanguage));
        $this->view->assign('siteLanguage', $siteLanguage);
        $this->view->assign('persistenceIdentifier', $persistenceIdentifier);

        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
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

    public function saveAction(string $persistenceIdentifier, SiteLanguage $siteLanguage, Items $items): void
    {
        $fileName = $this->localizationService->saveXliff($persistenceIdentifier, $siteLanguage, $items);
        $this->formService->addTranslation($persistenceIdentifier, $fileName);
        $this->l10nCache->flush();
        $this->addFlashMessage('Saved translation to ' . $fileName);
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
}
