<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Controller;

use Psr\Http\Message\ResponseInterface;
use R3H6\FormTranslator\Service\FormService;
use R3H6\FormTranslator\Service\LocalizationService;
use R3H6\FormTranslator\Service\Typo3LanguageService;
use R3H6\FormTranslator\Translation\Dto\Typo3Language;
use R3H6\FormTranslator\Translation\ItemCollection;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class FormController extends ActionController
{
    public function __construct(
        protected Typo3LanguageService $languageService,
        protected FormService $formService,
        protected LocalizationService $localizationService,
        protected FrontendInterface $l10nCache,
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected IconFactory $iconFactory,
        protected PageRenderer $pageRenderer,
    ) {}

    public function indexAction(): ResponseInterface
    {
        $title = LocalizationUtility::translate('mod.title.index', 'FormTranslator');

        $moduleTemplate = $this->initializeModuleTemplate($title);
        $moduleTemplate->assign('languages', $this->languageService->findAll());
        $moduleTemplate->assign('forms', $this->formService->listForms());
        $moduleTemplate->assign('title', $title);

        return $moduleTemplate->renderResponse('Form/Index');
    }

    public function localizeAction(string $persistenceIdentifier, Typo3Language $language): ResponseInterface
    {
        $this->pageRenderer->loadJavaScriptModule('@r3h6/form-translator/Mod.js');
        $this->pageRenderer->addCssFile('EXT:form_translator/Resources/Public/StyleSheets/Mod.css');

        $title = LocalizationUtility::translate('mod.title.localize', 'FormTranslator', [
            $this->formService->getTitle($persistenceIdentifier),
            $language->getTitle(),
        ]);

        $moduleTemplate = $this->initializeModuleTemplate($title);
        $moduleTemplate->assign('title', $title);
        $moduleTemplate->assign('items', $this->formService->getItems($persistenceIdentifier, $language));
        $moduleTemplate->assign('language', $language);
        $moduleTemplate->assign('persistenceIdentifier', $persistenceIdentifier);

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

        return $moduleTemplate->renderResponse('Form/Localize');
    }

    public function saveAction(string $persistenceIdentifier, Typo3Language $language, ItemCollection $items): ResponseInterface
    {
        $locallangFile = $this->formService->getLocallangFileFromPersistenceIdentifier($persistenceIdentifier);
        $this->localizationService->saveXliff($locallangFile, $language, $items);
        $this->addFlashMessage('Saved translation to ' . $locallangFile);
        if ($this->formService->isWritable($persistenceIdentifier)) {
            $this->formService->addTranslationFile($persistenceIdentifier, $locallangFile);
        }
        $this->l10nCache->flush();
        return $this->redirect('localize', null, null, ['persistenceIdentifier' => $persistenceIdentifier, 'language' => $language->getTypo3Language()]);
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
                ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL)),
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
