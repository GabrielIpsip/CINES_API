<?php


namespace App\Controller\AbstractController;

use App\Common\Enum\Role;
use App\Common\Interfaces\IAdministrations;
use App\Common\Traits\AdministrationsTrait;
use App\Common\Traits\RelationsTrait;
use App\Common\Traits\RelationTypesTrait;
use App\Entity\AbstractEntity\Administrations;
use App\Entity\DocumentaryStructures;
use App\Entity\Establishments;
use App\Entity\PhysicalLibraries;
use App\Utils\StringTools;
use DateTime;
use Exception;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;

abstract class RelationsController extends ESGBUController implements IAdministrations
{
    use AdministrationsTrait,
        RelationsTrait,
        RelationTypesTrait;

    /**
     * Common function for show a relation between two administration with same type.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param int $id Id of administration in relation.
     * @param string $origin True if administration is origin of relation, else false for result. Default false.
     * @return View
     */
    protected function commonShowAction(string $entityClass, int $id, string $origin) : View
    {
        try
        {
            $administration = $this->getAdministrationById($entityClass, $id);
            $this->checkRightsListRelation($entityClass, $administration);

            $repo = $this->managerRegistry->getRepository(self::ADMINISTRATION_RELATION_CLASS[$entityClass]);

            if (StringTools::stringToBool($origin))
            {
                $originParamName = 'origin' . ucfirst(self::ADMINISTRATION_CAMEL_CASE[$entityClass]);
                $response = $repo->findBy(array($originParamName => $administration));
            }
            else
            {
                $resultParamName = 'result' . ucfirst(self::ADMINISTRATION_CAMEL_CASE[$entityClass]);
                $response = $repo->findBy(array($resultParamName => $administration));
            }

            return $this->createView($response, Response::HTTP_OK);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Create a relation between two administration with same type.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param int $originId Id of origin administration.
     * @param int $resultId Id of result administration.
     * @param int $typeId Id of type of relation.
     * @param string $startDate Start date of relation.
     * @param string|null $endDate Optional. End date of relation.
     * @return View
     */
    protected function commonCreateAction(string $entityClass, int $originId, int $resultId, int $typeId,
                                          string $startDate, ?string $endDate): View
    {
        try
        {
            $startDate = new DateTime($startDate);
            if ($endDate)
            {
                $endDate = new DateTime($endDate);
            }
        }
        catch (Exception $e)
        {
            return $this->createView('date : Error in date format, must be YYYY-MM-DD : $startDate' . ', '
                . $endDate, Response::HTTP_BAD_REQUEST, true);
        }

        try
        {
            $doctrine = $this->managerRegistry;

            $this->checkRelationDate($startDate, $endDate);

            $administrationOrigin = $this->getAdministrationById($entityClass, $originId);
            $administrationResult = $this->getAdministrationById($entityClass, $resultId);

            $this->checkRightsModifyRelation($entityClass, $administrationOrigin);
            $this->checkRightsModifyRelation($entityClass, $administrationResult);

            $relationType = $this->getRelationTypeById($typeId);

            $this->checkIfRelationAlreadyExists($entityClass, $administrationOrigin, $administrationResult, $relationType);

            $administrationRelationClass = self::ADMINISTRATION_RELATION_CLASS[$entityClass];
            $administrationRelation = new $administrationRelationClass();
            $administrationRelation->setOriginAdministration($administrationOrigin);
            $administrationRelation->setResultAdministration($administrationResult);
            $administrationRelation->setStartDate($startDate);
            $administrationRelation->setEndDate($endDate);
            $administrationRelation->setType($relationType);

            $em = $doctrine->getManager();
            $em->persist($administrationRelation);
            $em->flush();

            return $this->createView($administrationRelation, Response::HTTP_CREATED, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Delete relation between two administration with same type.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param int $originId Id of origin administration.
     * @param int $resultId Id of result administration.
     * @param int $typeId Id of type of relation.
     * @return View
     */
    protected function commonDeleteAction(string $entityClass, int $originId, int $resultId, int $typeId): View
    {
        try
        {
            $em = $this->managerRegistry->getManager();

            $administrationOrigin = $this->getAdministrationById($entityClass, $originId);
            $administrationResult = $this->getAdministrationById($entityClass, $resultId);

            $this->checkRightsModifyRelation($entityClass, $administrationOrigin);
            $this->checkRightsModifyRelation($entityClass, $administrationResult);

            $relationType = $this->getRelationTypeById($typeId);

            $administrationRelation = $this->getRelation(
                $entityClass, $administrationOrigin, $administrationResult, $relationType);
            $em->remove($administrationRelation);
            $em->flush();

            return $this->createView(ucfirst(self::ADMINISTRATION_NAME[$entityClass]) . ' relation deleted.',
                Response::HTTP_NO_CONTENT, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }


    /**
     * Check if current user has right to list relation.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param Administrations $administrations Administration which check right.
     * @throws Exception 403 : Not authorized.
     */
    private function checkRightsListRelation(string $entityClass, Administrations $administrations)
    {
        $isAdmin = $this->checkRightsBool([Role::ADMIN, Role::ADMIN_RO, Role::SURVEY_ADMIN, Role::VALID_SURVEY_RESP],
        null, null, null, false);

        if ($isAdmin)
        {
            return;
        }

        switch ($entityClass)
        {
            case  Establishments::class:
                $this->checkRights([Role::USER], null, $administrations);
                break;
            case DocumentaryStructures::class:
                $this->checkRights([Role::USER], $administrations);
                break;
            case PhysicalLibraries::class:
                $this->checkRights([Role::USER], null, null, $administrations);
                break;
        }
    }

    /**
     * Check if current user has right to modify relation.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param Administrations $administrations Administration which check right.
     * @throws Exception 403 : Not authorized.
     */
    private function checkRightsModifyRelation(string $entityClass, Administrations $administrations)
    {
        switch ($entityClass)
        {
            case  Establishments::class:
                $roles = [Role::ADMIN, Role::SURVEY_ADMIN];
                $this->checkRights($roles, null, $administrations);
                break;

            case DocumentaryStructures::class:
                $roles = [Role::ADMIN, Role::SURVEY_ADMIN, Role::VALID_SURVEY_RESP];
                $this->checkRights($roles, $administrations);
                break;

            case PhysicalLibraries::class:
                $roles = [Role::ADMIN, Role::SURVEY_ADMIN, Role::VALID_SURVEY_RESP];
                $this->checkRights($roles, null, null, $administrations);
                break;
        }
    }
}