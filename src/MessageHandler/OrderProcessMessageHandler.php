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

        // Simular procesamiento: registrar y marcar trabajo como procesado si existe
        $this->logger->info('Processing order', ['order_id' => $orderId]);

        // Opcionalmente actualizar tabla order_jobs si existe
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
