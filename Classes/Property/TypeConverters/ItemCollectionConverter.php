<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Property\TypeConverters;

use R3H6\FormTranslator\Translation\Item;
use R3H6\FormTranslator\Translation\ItemCollection;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;

class ItemCollectionConverter extends AbstractTypeConverter
{
    /** @var array<string> */
    protected $sourceTypes = ['array'];
    /** @var string */
    protected $targetType = ItemCollection::class;
    /** @var int */
    protected $priority = 2;

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function convertFrom($source, string $targetType, array $convertedChildProperties = [], ?PropertyMappingConfigurationInterface $configuration = null): ItemCollection
    {
        $items = new ItemCollection();
        foreach ($source as $sourceItem) {
            $item = new Item($sourceItem['identifier']);
            $item->setSource($sourceItem['source']);
            $item->setTarget($sourceItem['target']);
            $items->addItem($item);
        }
        return $items;
    }
}
