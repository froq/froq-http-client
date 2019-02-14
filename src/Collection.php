<?php
/**
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Copyright (c) 2015 Kerem Güneş
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace froq\http\client;

use froq\util\interfaces\Loopable;

/**
 * Collection.
 * @package froq\http\client
 * @object  froq\http\client\Collection
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0
 */
class Collection implements Loopable
{
    /**
     * Items.
     * @var array
     */
    protected $items = [];

    /**
     * Items type.
     * @var string
     */
    protected $itemsType;

    /**
     * Constructor.
     * @param  array|null  $items
     * @param  string|null $itemsType
     * @throws froq\http\client\CollectionException
     */
    public function __construct(array $items = null, string $itemsType = null)
    {
        foreach ((array) $items as $item) {
            $this->checkItemType($item, $itemsType);
        }

        $this->items = $items ?? [];
        $this->itemsType = $itemsType ?? 'any';
    }

    /**
     * Item.
     * @param  int $index
     * @return object
     * @throws froq\http\client\CollectionException
     */
    public final function item(int $index): object
    {
        if (!isset($this->items[$index])) {
            throw new CollectionException("No item exists with index {$index}");
        }

        return $this->items[$index];
    }

    /**
     * Items.
     * @return array
     */
    public final function items(): array
    {
        return $this->items;
    }

    /**
     * Items type.
     * @return string
     */
    public final function itemsType(): string
    {
        return $this->itemsType;
    }

    /**
     * Add.
     * @param  any $item
     * @return void
     * @throws froq\http\client\CollectionException
     */
    public final function add($item): void
    {
        $this->checkItemType($item, $this->itemsType);

        $this->items[] = $item;
    }

    /**
     * Remove.
     * @param  int $index
     * @return void
     */
    public final function remove(int $index): void
    {
        unset($this->items[$index]);
    }

    /**
     * @inheritDoc froq\util\interfaces\Sizable
     */
    public final function size(): int
    {
        return count($this->items);
    }

    /**
     * @inheritDoc froq\util\interfaces\Arrayable
     */
    public final function toArray(): array
    {
        return $this->items;
    }

    /**
     * @inheritDoc \IteratorAggregate
     */
    public final function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * Check item type.
     * @param  any     $item
     * @param  ?string $itemType
     * @return void
     * @throws froq\http\client\CollectionException
     */
    protected final function checkItemType($item, ?string $itemType): void
    {
        if ($item && $itemType && $itemType != 'any' && !is_a($item, $itemType)) {
            throw new CollectionException(sprintf('Each item must be type of %s, %s given',
                $itemType, get_class($item)));
        }
    }
}
