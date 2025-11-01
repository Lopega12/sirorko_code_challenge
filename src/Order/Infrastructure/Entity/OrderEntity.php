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

    #[ORM\Column(type: 'string', length: 50)]
    private string $status;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $paymentReference = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(string $id, string $cartId, float $total, string $status = 'pending')
    {
        $this->id = $id;
        $this->cartId = $cartId;
        $this->items = new ArrayCollection();
        $this->total = (string) round($total, 2);
        $this->status = $status;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getPaymentReference(): ?string
    {
        return $this->paymentReference;
    }

    public function setPaymentReference(?string $paymentReference): void
    {
        $this->paymentReference = $paymentReference;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function markAsProcessing(): void
    {
        $this->setStatus('processing');
    }

    public function markAsPaid(string $paymentReference): void
    {
        $this->setStatus('paid');
        $this->setPaymentReference($paymentReference);
    }

    public function markAsPaymentFailed(): void
    {
        $this->setStatus('payment_failed');
    }

    public function markAsCompleted(): void
    {
        $this->setStatus('completed');
    }

    public function markAsCancelled(): void
    {
        $this->setStatus('cancelled');
    }
}
