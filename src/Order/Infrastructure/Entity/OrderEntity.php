<?php

namespace App\Order\Infrastructure\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
final class OrderEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $cartId;

    /**
     * @var Collection<int, OrderItemEntity>
     */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItemEntity::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $total;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $id, string $cartId, float $total)
    {
        $this->id = $id;
        $this->cartId = $cartId;
        $this->items = new ArrayCollection();
        $this->total = (string) round($total, 2);
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCartId(): string
    {
        return $this->cartId;
    }

    public function addItem(OrderItemEntity $item): void
    {
        $this->items->add($item);
    }

    /** @return OrderItemEntity[] */
    public function getItems(): array
    {
        return $this->items->toArray();
    }

    public function getTotal(): float
    {
        return (float) $this->total;
    }
}
