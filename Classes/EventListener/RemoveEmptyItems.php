<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\EventListener;

use R3H6\FormTranslator\Event\AfterParseFormEvent;

class RemoveEmptyItems
{
    public function __invoke(AfterParseFormEvent $event): void
    {
        $event->setItems(array_filter($event->getItems(), fn($value): bool => $value !== ''));
    }
}
