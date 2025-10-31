<?php

namespace App\MessageHandler;

use App\Message\Order\OrderProcessCartMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class OrderProcessMessageHandler
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function __invoke(OrderProcessCartMessage $message): void
    {
        $orderId = $message->orderId();

        // Simulate processing: log and mark job processed if exists
        $this->logger->info('Processing order', ['order_id' => $orderId]);

        // Optionally update order_jobs table if exists
        $qb = $this->em->createQueryBuilder();
        $res = $qb->select('j')
            ->from('App\\Order\\Infrastructure\\Entity\\OrderJob', 'j')
            ->where('j.orderId = :orderId')
            ->setParameter('orderId', $orderId)
            ->getQuery()
            ->getResult();

        foreach ($res as $job) {
            if (method_exists($job, 'setStatus')) {
                $job->setStatus('done');
                $this->em->persist($job);
            }
        }

        $this->em->flush();
    }
}
