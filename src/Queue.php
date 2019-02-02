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

use Froq\Http\Client\AbstractClient as Item;
use Froq\Http\Client\Agent\Agent;
use Froq\Util\Interfaces\Sizable;

/**
 * @package    Froq
 * @subpackage Froq\Http\Client
 * @object     Froq\Http\Client\Queue
 * @author     Kerem Güneş <k-gun@mail.com>
 * @since      3.0
 */
final class Queue implements Sizable, \IteratorAggregate
{
    private $items;

    public function __construct(array $items = null)
    {
        if ($items != null) {
            foreach ($items as $item) {
                $this->addItem($item);
            }
        }
    }

    public function item(int $i): Item
    {
        if (isset($this->items[$i])) {
            return $this->items[$i];
        }
        throw new QueueException("No item found with '{$i}' index");
    }

    public function items(): ?array
    {
        return $this->items;
    }

    public function addItem(Item $item): self
    {
        $this->items[] = $item;
        return $this;
    }

    public function addClient(Item $client): self
    {
        return $this->addItem($client);
    }
    public function addAsyncClient(Item $client): self
    {
        $client->async(true);
        return $this->addItem($client);
    }

    public function perform()
    {
        foreach ($this->items as $client) {
            $client->send();
        }
    }

    public function isEmpty(): bool
    {
        return $this->items == null;
    }

    /**
     * @inheritDoc Froq\Util\Interfaces\Sizable
     */
    public function size(): int
    {
        return sizeof($this->items);
    }
    /**
     * @inheritDoc \IteratorAggregate
     */
    public final function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->data);
    }
}
