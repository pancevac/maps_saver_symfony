<?php

namespace App\Repository;

use App\Entity\Trip;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @method Trip|null find($id, $lockMode = null, $lockVersion = null)
 * @method Trip|null findOneBy(array $criteria, array $orderBy = null)
 * @method Trip[]    findAll()
 * @method Trip[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TripRepository extends ServiceEntityRepository
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(ManagerRegistry $registry, TokenStorageInterface $tokenStorage)
    {
        parent::__construct($registry, Trip::class);

        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Return trip by its ID, owned by currently authenticated user.
     *
     * @param mixed $id     ID of Trip entity
     * @return Trip|null
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findOwnedByAuthUser($id): ?Trip
    {
        $authUser = $this->tokenStorage->getToken()->getUser();

        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->andWhere('t.id = :id')
            ->setParameters(['id' => $id, 'user' => $authUser])
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * Return trip by some criteria, owned by currently authenticated user.
     *
     * @param int $exceptId
     * @param array $criteria
     * @param null $orderBy
     * @return Trip|null
     * @throws NonUniqueResultException
     */
    public function findOneExcept(int $exceptId, array $criteria, $orderBy = null): ?Trip
    {
        $qb = $this->createQueryBuilder('t');

        foreach ($criteria as $criterionKey => $criterionValue) {
            $qb->andWhere("t.$criterionKey = :$criterionKey");
            $qb->setParameter($criterionKey, $criterionValue);
        }

        return $qb
            ->andwhere($qb->expr()->neq('t.id', $exceptId))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
