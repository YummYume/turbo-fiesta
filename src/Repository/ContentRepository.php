<?php

namespace App\Repository;

use App\Entity\Content;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Content>
 *
 * @method Content|null find($id, $lockMode = null, $lockVersion = null)
 * @method Content|null findOneBy(array $criteria, array $orderBy = null)
 * @method Content[]    findAll()
 * @method Content[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Content::class);
    }

    public function save(Content $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Content $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllPublishedContent(?User $user = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.published = :published')
            ->setParameter(':published', true)
            ->orderBy('c.createdAt', 'DESC')
        ;

        if (null !== $user) {
            $qb
                ->orWhere('c.createdBy = :user')
                ->setParameter('user', $user->getId(), 'uuid')
            ;
        }

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }

    public function queryAllCreatedBy(User $createdBy): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->where('c.createdBy = :user')
            ->setParameter('user', $createdBy->getId(), 'uuid')
            ->orderBy('c.createdAt', 'DESC')
        ;
    }
}
