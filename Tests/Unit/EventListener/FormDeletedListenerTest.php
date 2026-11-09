<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use R3H6\FormTranslator\EventListener\RemoveFormTranslationsOnFormDeleted;
use R3H6\FormTranslator\Hooks\RemoveFormTranslations;
use TYPO3\CMS\Form\Event\BeforeFormIsDeletedEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FormDeletedListenerTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists(BeforeFormIsDeletedEvent::class)) {
            self::markTestSkipped('Requires TYPO3 v14 form events');
        }
    }

    #[Test]
    public function removesTranslationsOfDeletedForm(): void
    {
        $hook = $this->createMock(RemoveFormTranslations::class);
        $hook->expects($this->once())->method('remove')->with('42');

        $event = new BeforeFormIsDeletedEvent('42', $this->createMock(ServerRequestInterface::class));
        (new RemoveFormTranslationsOnFormDeleted($hook))($event);
    }
}
