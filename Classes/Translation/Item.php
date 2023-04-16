<?php

namespace R3H6\FormTranslator\Translation;

final class Item
{
    private string $source = '';

    private string $target = '';

    private string $identifier;

    /**
     * @var mixed
     */
    private string $original = '';

    public function __construct(string $identifier)
    {
        if ($identifier === '') {
            throw new \InvalidArgumentException('Identifier can not be empty', 1641246694386);
        }
        $this->identifier = $identifier;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setTarget(string $target): void
    {
        $this->target = $target;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function setOriginal(string $original): void
    {
        $this->original = $original;
    }

    public function getOriginal(): string
    {
        return $this->original;
    }
}
