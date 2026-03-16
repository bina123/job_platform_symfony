<?php

namespace App\Repository;

use App\Entity\Application;
use App\Entity\Job;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Application> */
class ApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Application::class);
    }

    /** @return Application[] */
    public function findByDeveloper(User $developer): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.developer = :developer')
            ->setParameter('developer', $developer)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Application[] */
    public function findByJob(Job $job): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.job = :job')
            ->setParameter('job', $job)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function hasUserApplied(User $user, Job $job): bool
    {
        $count = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.developer = :developer')
            ->andWhere('a.job = :job')
            ->setParameter('developer', $user)
            ->setParameter('job', $job)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
