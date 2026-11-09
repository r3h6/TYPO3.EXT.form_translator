<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Tests\Functional\EventListener;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Event\AfterFormIsBuiltEvent;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RegisterTranslationFileOnFormBuiltTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/form_translator',
    ];

    protected array $coreExtensionsToLoad = [
        'form',
    ];

    private string $translationDirectory = '';

    protected function setUp(): void
    {
        parent::setUp();
        if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '14.0.0', '<')) {
            self::markTestSkipped('Requires the TYPO3 v14 form events');
        }

        $this->translationDirectory = Environment::getPublicPath() . '/fileadmin/form_translations';
        GeneralUtility::mkdir_deep($this->translationDirectory);
        GeneralUtility::writeFile(
            $this->translationDirectory . '/locallang.xlf',
            '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>'
            . '<xliff version="1.0"><file source-language="en" datatype="plaintext" original="">'
            . '<header/><body/></file></xliff>'
        );
    }

    protected function tearDown(): void
    {
        if ($this->translationDirectory !== '') {
            GeneralUtility::rmdir($this->translationDirectory, true);
        }
        parent::tearDown();
    }

    #[Test]
    public function registersSharedTranslationFileWhenFormIsBuilt(): void
    {
        $form = new FormDefinition('example');
        $form->setRenderingOption('translation', [
            'translationFiles' => [10 => 'EXT:form/Resources/Private/Language/locallang.xlf'],
        ]);

        $this->get(EventDispatcherInterface::class)->dispatch(new AfterFormIsBuiltEvent($form));

        self::assertSame(
            'fileadmin/form_translations/locallang.xlf',
            $form->getRenderingOptions()['translation']['translationFiles'][99]
        );
    }
}
