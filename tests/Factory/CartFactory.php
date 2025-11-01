<?php

namespace App\Tests\Factory;

use App\Cart\Domain\Cart;
use App\Cart\Domain\CartItem;
use App\Cart\Domain\CartId;
use App\Cart\Domain\Money as CartMoney;
use App\Cart\Domain\ProductId as CartProductId;
use App\Cart\Infrastructure\DoctrineCartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use function Zenstruck\Foundry\faker;

final class CartFactory extends PersistentObjectFactory
{
    /**
     * Instantiate a Cart aggregate using domain constructor (if exists) or by building manually.
     * We accept attributes: userId (string|null), items (array of ['product' => Product|id, 'quantity' => int])
     */
    protected function instantiate(array $attributes): object
    {
        $userId = $attributes['userId'] ?? null;
        $idAttr = $attributes['id'] ?? null;

        // Normalize CartId
        if ($idAttr instanceof CartId) {
            $cartId = $idAttr;
        } elseif (is_string($idAttr) && $idAttr !== '') {
            $cartId = CartId::fromString($idAttr);
        } else {
            $cartId = CartId::generate();
        }

        // Create cart via domain factory if available
        if ($userId) {
            $cart = Cart::createForUser($userId);
        } else {
            $cart = new Cart($cartId, null);
        }

        // Consume itemsSpec (NOT 'items') and add CartItem instances
        $itemsSpec = $attributes['itemsSpec'] ?? [];
        foreach ($itemsSpec as $it) {
            $productParam = $it['product'] ?? null;

            // Resolve product id and price/name
            if (is_object($productParam) && method_exists($productParam, 'getId')) {
                $productIdStr = $productParam->getId();
                $productId = CartProductId::fromString((string) $productIdStr);

                // try to read name and price from Product object
                $name = method_exists($productParam, 'getName') ? $productParam->getName() : ($it['name'] ?? 'product');

                if (method_exists($productParam, 'getPrice')) {
                    $pPrice = $productParam->getPrice();
                    // product value object exposes amount() in Product module
                    $priceFloat = is_object($pPrice) && method_exists($pPrice, 'amount') ? $pPrice->amount() : (float) ($it['price'] ?? 1.0);
                    $price = CartMoney::fromFloat((float) $priceFloat);
                } else {
                    $price = isset($it['price']) ? CartMoney::fromFloat((float) $it['price']) : CartMoney::fromFloat(1.0);
                }
            } else {
                // allow passing id/name/price directly
                $productIdStr = $it['product_id'] ?? ($productParam ?? faker()->uuid());
                $productId = CartProductId::fromString((string) $productIdStr);
                $name = $it['name'] ?? 'product';
                $price = isset($it['price']) ? CartMoney::fromFloat((float) $it['price']) : CartMoney::fromFloat(1.0);
            }

            $quantity = (int) ($it['quantity'] ?? 1);

            $cart->addItem(new CartItem($productId, $name, $price, $quantity));
        }

        return $cart;
    }

    protected function defaults(): array|callable
    {
        return [
            'id' => CartId::generate(),
            'userId' => null,
            // do not expose 'items' to Foundry; use 'itemsSpec' for item creation
        ];
    }

    public static function class(): string
    {
        return Cart::class;
    }

    protected function initialize(): static
    {
        return $this
            // Decirle a Foundry que no intente hidratar estos atributos
            ->beforeInstantiate(function(array $attributes): array {
                unset($attributes['itemsSpec']);
                return $attributes;
            });
    }

    /**
     * Create a Cart via Foundry and then add the provided items and persist using the provided EntityManager.
     * This avoids Foundry attempting to set raw arrays into the Cart->items property.
     *
     * @param array<int,array> $itemsSpec
     */
    public static function createOneWithItems(array $itemsSpec, ?string $userId, EntityManagerInterface $em)
    {
        // Build cart aggregate directly
        if ($userId) {
            $cart = \App\Cart\Domain\Cart::createForUser($userId);
        } else {
            $cart = new \App\Cart\Domain\Cart(\App\Cart\Domain\CartId::generate(), null);
        }

        foreach ($itemsSpec as $it) {
            $productParam = $it['product'] ?? null;

            if (is_object($productParam) && method_exists($productParam, 'getId')) {
                $productIdStr = $productParam->getId();
                $productId = CartProductId::fromString((string) $productIdStr);

                $name = method_exists($productParam, 'getName') ? $productParam->getName() : ($it['name'] ?? 'product');

                if (method_exists($productParam, 'getPrice')) {
                    $pPrice = $productParam->getPrice();
                    $priceFloat = is_object($pPrice) && method_exists($pPrice, 'amount') ? $pPrice->amount() : (float) ($it['price'] ?? 1.0);
                    $price = CartMoney::fromFloat((float) $priceFloat);
                } else {
                    $price = isset($it['price']) ? CartMoney::fromFloat((float) $it['price']) : CartMoney::fromFloat(1.0);
                }
            } else {
                $productIdStr = $it['product_id'] ?? ($productParam ?? faker()->uuid());
                $productId = CartProductId::fromString((string) $productIdStr);
                $name = $it['name'] ?? 'product';
                $price = isset($it['price']) ? CartMoney::fromFloat((float) $it['price']) : CartMoney::fromFloat(1.0);
            }

            $quantity = (int) ($it['quantity'] ?? 1);
            $cart->addItem(new CartItem($productId, $name, $price, $quantity));
        }

        $repo = new DoctrineCartRepository($em);
        $repo->save($cart);

        return $cart;
    }
}
