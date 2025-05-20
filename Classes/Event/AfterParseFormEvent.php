<?php

namespace R3H6\FormTranslator\Event;

final class AfterParseFormEvent
{
    /**
     * @var array
     */
    private $items;

    /**
     * @var array
     */
    private $form;

    public function __construct(array $items, array $form)
    {
        $this->items = $items;
        $this->form = $form;
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

    public function getForm(): array
    {
        return $this->form;
    }

    public function setForm(array $form): void
    {
        $this->form = $form;
    }
}
