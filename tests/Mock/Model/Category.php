<?php declare(strict_types=1);

namespace Danilovl\ObjectToArrayTransformBundle\Tests\Mock\Model;

use ArrayIterator;

class Category
{
    /**
     * @var ArrayIterator<int, Product>
     */
    public ArrayIterator $products;

    public function __construct(public readonly string $title)
    {
        $this->products = new ArrayIterator;
    }

    public function addProduct(Product $product): void
    {
        $this->products->append($product);
    }
}
