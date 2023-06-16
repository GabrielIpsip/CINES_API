<?php


namespace App\Repository;

use App\Common\Enum\Type;
use App\Entity\Surveys;
use Exception;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

abstract class ProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, $entityClass) {
        parent::__construct($registry, $entityClass);
    }

    /**
     * Get number of response for an administration.
     * @param array $administrationArray List of physical libraries you want get progress.
     * @param Surveys $survey Number of response for this survey.
     * @return array Return array of physical libraries (represented in array) with progress.
     * @throws Exception Error to get last active survey or number of active data type.
     */
    public abstract function getNbrResponse(array $administrationArray, Surveys $survey): ?array;


    /**
     * Get number of active data type for administration type and survey.
     * @param Surveys $survey Survey doctrine entity.
     * @param int $administrationType Must be in administrationType table.
     * @return int Number of active data type for this survey and this administration type.
     */
    protected function getNbrActiveDataType(Surveys $survey, int $administrationType): int
    {
        $em = $this->getEntityManager();
        $query = $em->createQuery(
            "SELECT COUNT(sdt.id) AS nbrActive
            FROM App\Entity\SurveyDataTypes sdt 
            JOIN App\Entity\DataTypes dt
            JOIN App\Entity\Groups gp
            JOIN App\Entity\AdministrationTypes at
            WHERE sdt.type = dt
            AND dt.group = gp
            AND gp.administrationType = at
            AND at.id = ?2
            AND sdt.active = 1
            AND IDENTITY(dt.type) <> ?3
            AND sdt.survey = ?1
        ");

        $query->setParameter(1, $survey);
        $query->setParameter(2, $administrationType);
        $query->setParameter(3, Type::operation);

        return $query->getResult()[0]['nbrActive'];
    }

    /**
     * Get number of documentary structure for each establishment in array.
     * @param array $establishmentArray Array with 'establishmentId' key in array.
     * @return array Array with establishment id like key and number of documentary structure like value.
     */
    protected function getNbrDocStructByEstablishment(array $establishmentArray): array
    {
        if (count($establishmentArray) === 0)
        {
            return [];
        }

        $queryStr = "SELECT IDENTITY(ds.establishment) AS establishmentId, COUNT(ds.id) AS nbrDocStruct
            FROM App\Entity\DocumentaryStructures ds
            WHERE ds.active = 1 
            AND (IDENTITY(ds.establishment) = ?1";

        return $this->getNbrByEstablishment($establishmentArray, $queryStr);
    }


    /**
     * Get number of physical libraries for each establishment in array.
     * @param array $establishmentArray Array with 'establishmentId' key in array.
     * @return array Array with establishment id like key and number of physical library like value.
     */
    protected function getNbrPhysicLibByEstablishment(array $establishmentArray): array
    {
        if (count($establishmentArray) === 0)
        {
            return [];
        }

        $queryStr = "SELECT IDENTITY(ds.establishment) AS establishmentId, COUNT(pl.id) AS nbrPhysicLib
            FROM App\Entity\DocumentaryStructures ds
            JOIN App\Entity\PhysicalLibraries pl
            WHERE pl.documentaryStructure = ds
            AND pl.active = 1 
            AND (IDENTITY(ds.establishment) = ?1";

        return $this->getNbrByEstablishment($establishmentArray, $queryStr);
    }

    /**
     * Finish to construct SQL request for get number of administration for an establishment.
     * @param array $establishmentArray Array with 'establishmentId' key in array.
     * @param string $queryStr Beginning of SQL query for get number of administration by establishment.
     * @return array|int|string Array with establishment id like key and number of adminsitration like value.
     */
    private function getNbrByEstablishment(array $establishmentArray, string $queryStr)
    {
        if (count($establishmentArray) > 1)
        {
            for ($i = 1; $i < count($establishmentArray); $i++)
            {
                $queryStr .= ' OR IDENTITY(ds.establishment) = ?' . ($i + 1);
            }
        }
        $queryStr .= ') GROUP BY ds.establishment';

        $em = $this->getEntityManager();
        $query = $em->createQuery($queryStr);
        $query->setParameter(1, $establishmentArray[0]['establishmentId']);

        if (count($establishmentArray) > 1)
        {
            for ($i = 1; $i < count($establishmentArray); $i++)
            {
                $query->setParameter($i + 1, $establishmentArray[$i]['establishmentId']);
            }
        }
        return $query->getArrayResult();
    }

    /**
     * Get number of physical libraries by documentary structure.
     * @param array $docStructArray Array with 'docStructId' key in array.
     * @return array Array with documentary structure id like key and number of physical library like value.
     */
    protected function getNbrPhysicLibByDocStruct(array $docStructArray): array
    {
        if (count($docStructArray) === 0)
        {
            return [];
        }

        $queryStr = "SELECT IDENTITY(pl.documentaryStructure) AS docStructId, COUNT(pl.id) AS nbrPhysicLib
            FROM App\Entity\PhysicalLibraries pl
            WHERE pl.active = 1 
            AND (IDENTITY(pl.documentaryStructure) = ?1";

        if (count($docStructArray) > 1)
        {
            for ($i = 1; $i < count($docStructArray); $i++)
            {
                $queryStr .= ' OR IDENTITY(pl.documentaryStructure) = ?' . ($i + 1);
            }
        }
        $queryStr .= ') GROUP BY pl.documentaryStructure';

        $em = $this->getEntityManager();
        $query = $em->createQuery($queryStr);
        $query->setParameter(1, $docStructArray[0]['docStructId']);

        if (count($docStructArray) > 1)
        {
            for ($i = 1; $i < count($docStructArray); $i++)
            {
                $query->setParameter($i + 1, $docStructArray[$i]['docStructId']);
            }
        }
        return $query->getArrayResult();
    }
}