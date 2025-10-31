<?php

namespace App\Cart\Application\Service;

final class CartSerializer
{
    public function serializeItems(array $items): array
    {
        return array_map(function ($i) {
            return [
                'product_id' => (string) $i->productId(),
                'name' => $i->name(),
                'price' => $i->price()->toFloat(),
                'quantity' => $i->quantity(),
                'total' => $i->total()->toFloat(),
            ];
        }, $items);
    }

    public function serializeCart(?\App\Cart\Domain\Cart $cart): array
    {
        if (!$cart) {
            return ['items' => [], 'total' => 0];
        }

        $items = $this->serializeItems($cart->items());
        $total = $cart->total()->toFloat();

        return ['items' => $items, 'total' => $total];
    }
}
