<?php

namespace App\Repository;

use App\Entity\Job;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Job> */
class JobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Job::class);
    }

    /** @return Job[] */
    public function findOpenJobs(): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.status = :status')
            ->setParameter('status', Job::STATUS_OPEN)
            ->orderBy('j.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Job[] */
    public function findByEmployer(User $employer): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.employer = :employer')
            ->setParameter('employer', $employer)
            ->orderBy('j.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Job[] */
    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('j');

        if (!empty($filters['status'])) {
            $qb->andWhere('j.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['minSalary'])) {
            $qb->andWhere('j.salary >= :minSalary')
                ->setParameter('minSalary', (int) $filters['minSalary']);
        }

        if (!empty($filters['maxSalary'])) {
            $qb->andWhere('j.salary <= :maxSalary')
                ->setParameter('maxSalary', (int) $filters['maxSalary']);
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('j.title LIKE :search OR j.description LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        $qb->orderBy('j.createdAt', 'DESC');

        if (!empty($filters['limit'])) {
            $qb->setMaxResults((int) $filters['limit']);
        }

        if (!empty($filters['offset'])) {
            $qb->setFirstResult((int) $filters['offset']);
        }

        return $qb->getQuery()->getResult();
    }

    /** @return Job[] */
    public function findPaginated(int $page = 1, int $limit = 10): array
    {
        return $this->createQueryBuilder('j')
            ->orderBy('j.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
