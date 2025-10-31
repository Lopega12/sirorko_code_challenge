<?php

namespace App\Order\Infrastructure\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_items')]
final class OrderItemEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: OrderEntity::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private OrderEntity $order;

    #[ORM\Column(type: 'string', length: 36)]
    private string $productId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $price;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    public function __construct(OrderEntity $order, string $productId, string $name, float $price, int $quantity)
    {
        $this->order = $order;
        $this->productId = $productId;
        $this->name = $name;
        $this->price = (string) round($price, 2);
        $this->quantity = $quantity;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): float
    {
        return (float) $this->price;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }
}
