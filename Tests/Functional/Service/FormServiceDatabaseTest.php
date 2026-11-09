<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\Test;
use R3H6\FormTranslator\Service\FormService;
use R3H6\FormTranslator\Translation\Dto\Typo3Language;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Form\Domain\DTO\FormData;
use TYPO3\CMS\Form\Domain\Repository\FormDefinitionRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FormServiceDatabaseTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/form_translator',
    ];

    protected array $coreExtensionsToLoad = [
        'form',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '14.2.0', '<')) {
            self::markTestSkipped('Requires the TYPO3 v14.2 form database storage');
        }

        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('isAdmin')->willReturn(true);
        $backendUser->method('check')->willReturn(true);
        $GLOBALS['BE_USER'] = $backendUser;
    }

    #[Test]
    public function parsesAndListsDatabaseStoredForm(): void
    {
        $formDefinition = [
            'identifier' => 'example',
            'type' => 'Form',
            'label' => 'Example',
            'prototypeName' => 'standard',
            'renderingOptions' => [
                'submitButtonLabel' => 'Submit',
            ],
            'renderables' => [
                [
                    'type' => 'Page',
                    'identifier' => 'page-1',
                    'renderables' => [
                        [
                            'type' => 'Text',
                            'identifier' => 'text-1',
                            'properties' => [
                                'label' => 'Name',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $persistenceIdentifier = (string)$this->get(FormDefinitionRepository::class)
            ->addRaw(0, FormData::fromArray($formDefinition));

        $formService = $this->get(FormService::class);

        self::assertSame('Example', $formService->getTitle($persistenceIdentifier));

        $loadedForm = $formService->parseForm($persistenceIdentifier);
        self::assertSame('example', $loadedForm['identifier']);
        self::assertSame('Example', $loadedForm['label']);
        self::assertSame('Submit', $loadedForm['renderingOptions']['submitButtonLabel']);
        self::assertSame('Name', $loadedForm['renderables'][0]['renderables'][0]['properties']['label']);

        self::assertNotEmpty($formService->getItems(
            $persistenceIdentifier,
            new Typo3Language('default', 'English', 'gb')
        ));

        $listedForms = $formService->listForms();
        $listedForm = array_values(array_filter(
            $listedForms,
            static fn(array $form): bool => $form['persistenceIdentifier'] === $persistenceIdentifier
        ));
        self::assertCount(1, $listedForm);
        self::assertSame('Example', $listedForm[0]['name']);
    }
}
