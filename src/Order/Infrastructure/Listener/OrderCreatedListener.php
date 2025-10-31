<?php

namespace App\Order\Infrastructure\Listener;

use App\Order\Domain\Event\OrderCreated;
use App\Order\Infrastructure\Entity\OrderJob;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class OrderCreatedListener
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function __invoke(OrderCreated $event): void
    {
        // Create a lightweight job record for later processing (fictitious processing)
        $job = new OrderJob((string) $event->orderId());
        $this->em->persist($job);
        $this->em->flush();

        $this->logger->info('Order job created', ['order_id' => (string) $event->orderId(), 'job_id' => $job->getId()]);
    }
}
