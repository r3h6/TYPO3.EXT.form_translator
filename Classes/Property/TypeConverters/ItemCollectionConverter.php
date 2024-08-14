<?php

namespace R3H6\FormTranslator\Property\TypeConverters;

use R3H6\FormTranslator\Translation\Item;
use R3H6\FormTranslator\Translation\ItemCollection;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;

class ItemCollectionConverter extends AbstractTypeConverter
{
    protected array $sourceTypes = ['array'];
    protected string $targetType = ItemCollection::class;
    protected int $priority = 2;

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
