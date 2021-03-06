<?php

namespace R3H6\FormTranslator\Event;

final class AfterParseFormEvent
{
    /**
     * @var array
     */
    private $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function addItem(string $identifier, string $value): void
    {
        if (array_key_exists($identifier, $this->items)) {
            throw new \InvalidArgumentException('Item already exists', 1641731370528);
        }
        $this->items[$identifier] = $value;
    }
}
