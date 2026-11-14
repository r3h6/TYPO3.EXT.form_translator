<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Controller;

use Psr\Http\Message\ResponseInterface;
use R3H6\FormTranslator\Service\FormService;
use R3H6\FormTranslator\Service\Typo3LanguageService;
use R3H6\FormTranslator\Translation\Dto\Typo3Language;
use R3H6\FormTranslator\Translation\ItemCollection;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class FormController extends ActionController
{
    public function __construct(
        protected Typo3LanguageService $languageService,
        protected FormService $formService,
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
            $this->createLinkButton($buttonBar)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:close'))
                ->setShowLabelText(true)
                ->setHref($this->uriBuilder->reset()->uriFor('index'))
                ->setIcon($this->iconFactory->getIcon('actions-close', IconSize::SMALL)),
            ButtonBar::BUTTON_POSITION_LEFT,
            1
        );
        $buttonBar->addButton(
            $this->createLinkButton($buttonBar)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:save'))
                ->setShowLabelText(true)
                ->setHref('#')
                ->setDataAttributes(['save' => '#LocalizationForm'])
                ->setIcon($this->iconFactory->getIcon('actions-save', IconSize::SMALL)),
            ButtonBar::BUTTON_POSITION_LEFT,
            2
        );

        return $moduleTemplate->renderResponse('Form/Localize');
    }

    public function saveAction(string $persistenceIdentifier, Typo3Language $language, ItemCollection $items): ResponseInterface
    {
        $this->formService->saveTranslation($persistenceIdentifier, $language, $items);
        $this->addFlashMessage('Saved translation to ' . $this->formService->getTranslationFileReference());
        $this->l10nCache->flush();
        return $this->redirect('localize', null, null, ['persistenceIdentifier' => $persistenceIdentifier, 'language' => $language->getTypo3Language()]);
    }

    protected function initializeModuleTemplate(string $title): ModuleTemplate
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setTitle($title);

        $docHeader = $moduleTemplate->getDocHeaderComponent();
        $mayMakeShortcut = $this->getBackendUser()->mayMakeShortcut();

        $queryParams = [];
        parse_str($this->request->getUri()->getQuery(), $queryParams);
        $arguments = array_diff_key($queryParams, ['token' => true]);

        if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '14.0.0', '>=')) {
            // TYPO3 v14 adds the reload and shortcut buttons automatically.
            if ($mayMakeShortcut) {
                $docHeader->setShortcutContext('web_FormTranslator', $title, $arguments);
            }
            return $moduleTemplate;
        }

        $buttonBar = $docHeader->getButtonBar();
        $buttonBar->addButton(
            $this->createLinkButton($buttonBar)
                ->setHref((string)$this->request->getUri())
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
                ->setIcon($this->iconFactory->getIcon('actions-refresh', IconSize::SMALL)),
            ButtonBar::BUTTON_POSITION_RIGHT,
            1
        );

        if ($mayMakeShortcut) {
            $shortcutButton = $buttonBar->makeShortcutButton()
                ->setRouteIdentifier('web_FormTranslator')
                ->setDisplayName($title)
                ->setArguments($arguments);
            $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
        }

        return $moduleTemplate;
    }

    private function createLinkButton(ButtonBar $buttonBar): LinkButton
    {
        if (class_exists(ComponentFactory::class)) {
            return GeneralUtility::makeInstance(ComponentFactory::class)->createLinkButton();
        }
        return $buttonBar->makeLinkButton();
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
