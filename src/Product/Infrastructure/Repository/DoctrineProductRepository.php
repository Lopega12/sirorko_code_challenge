<?php

namespace App\Product\Infrastructure\Repository;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;

final class DoctrineProductRepository implements ProductRepositoryInterface
{
    private EntityManagerInterface $em;
    private ObjectRepository $repo;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->repo = $em->getRepository(Product::class);
    }

    public function find(string $id): ?Product
    {
        return $this->repo->find($id);
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->repo->findOneBy(['sku' => $sku]);
    }

    public function save(Product $product): void
    {
        $this->em->persist($product);
        $this->em->flush();
    }

    public function remove(Product $product): void
    {
        $this->em->remove($product);
        $this->em->flush();
    }

    public function list(int $limit = 50, int $offset = 0): array
    {
        return $this->repo->createQueryBuilder('p')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
