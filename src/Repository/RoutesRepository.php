<?php


namespace App\Repository;

use App\Entity\RouteContents;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Common\Util\Debug;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * @method RouteContents|null find($id, $lockMode = null, $lockVersion = null)
 * @method RouteContents|null findOneBy(array $criteria, array $orderBy = null)
 * @method RouteContents[]    findAll()
 * @method RouteContents[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoutesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RouteContents::class);
    }

    /**
     * Get route content by route name and content language.
     * @param string $name Route name.
     * @param string $lang Code lang.
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getRouteContentByName(string $name, string $lang): RouteContents
    {
        $em = $this->getEntityManager();

        $query = $em->createQuery("SELECT rc
                                   FROM App\Entity\RouteContents rc
                                   JOIN App\Entity\Routes r
                                   JOIN App\Entity\Languages l
                                   WHERE rc.route = r.id
                                   AND rc.language = l.id
                                   AND UPPER(r.name) = UPPER(?1)
                                   AND UPPER(l.code) = UPPER(?2)");

        $query->setParameter(1, $name);
        $query->setParameter(2, $lang);

        return $query->getSingleResult();
    }

}

