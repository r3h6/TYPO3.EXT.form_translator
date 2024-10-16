<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Translation;

/**
 * @implements \IteratorAggregate<Item>
 */
final class ItemCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var Item[]
     */
    protected $items = [];

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): \Traversable
    {
        ksort($this->items);
        return new \ArrayIterator($this->items);
    }

    /**
     * @return Item[]
     */
    public function toArray(): array
    {
        ksort($this->items);
        return $this->items;
    }

    public function addItem(Item $newItem): void
    {
        $item = $this->getItem($newItem->getIdentifier());
        if ($newItem === $item) {
            return; // Already added
        }
        if ($item !== null) {
            throw new \InvalidArgumentException('Item with identifier "' . $newItem->getIdentifier() . '" already exists', 1641234521495);
        }

        $this->items[$newItem->getIdentifier()] = $newItem;
    }

    public function getItem(string $identifier): ?Item
    {
        return $this->items[$identifier] ?? null;
    }
}
