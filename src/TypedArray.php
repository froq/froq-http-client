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

namespace Froq\Http\Client;

use Froq\Util\Interfaces\Loopable;

/**
 * @package    Froq
 * @subpackage Froq\Http\Client
 * @object     Froq\Http\Client\TypedArray
 * @author     Kerem Güneş <k-gun@mail.com>
 * @since      3.0
 */
class TypedArray implements Loopable
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
     * @param  array  $items
     * @param  string $itemsType
     * @throws Froq\Http\Client\TypedArrayException
     */
    public function __construct(array $items, string $itemsType)
    {
        foreach ($items as $item) {
            if (!is_a($item, $itemsType)) {
                $itemType = get_class($item);
                throw new TypedArrayException("Each item must be type of {$itemsType}, {$itemType} given");
            }
        }

        $this->items = $items;
        $this->itemsType = $itemsType;
    }

    /**
     * Item.
     * @param  int $index
     * @return object
     * @throws Froq\Http\Client\TypedArrayException
     */
    public final function item(int $index): object
    {
        if (!isset($this->items[$index])) {
            throw new TypedArrayException("No item exists with index {$index}");
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
     * @inheritDoc Froq\Util\Interfaces\Sizable
     */
    public final function size(): int
    {
        return count($this->items);
    }

    /**
     * @inheritDoc Froq\Util\Interfaces\Arrayable
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
}
