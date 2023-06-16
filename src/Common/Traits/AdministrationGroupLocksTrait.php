<?php


namespace App\Common\Traits;


use App\Entity\AbstractEntity\Administrations;
use App\Entity\DataTypes;
use App\Entity\Surveys;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait AdministrationGroupLocksTrait
{
    /**
     * Lock resource if possible by adding lock in database.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class
     * @param DataTypes $dataTypes DataType entity of data value modified.
     * @param Surveys $survey Survey entity of data value modified.
     * @param Administrations $administration Administration of data value modified.
     * @throws Exception 409 : Conflict when modify data.
     */
    private function lockResource(string $entityClass, DataTypes $dataTypes, Surveys $survey,
                                  Administrations $administration)
    {
        $doctrine = $this->managerRegistry;
        $em = $doctrine->getManager();

        $administrationGroupLock = self::ADMINISTRATION_GROUP_LOCK_CLASS[$entityClass];
        $group = $dataTypes->getGroup();
        $user = $this->getCurrentUserDoctrineEntity();

        $repo = $doctrine->getRepository($administrationGroupLock);
        $repo->deleteOlder();

        $lock = $repo->findOneBy(array(
            self::ADMINISTRATION_CAMEL_CASE[$entityClass] => $administration,
            'group' => $group,
            'survey' => $survey,
        ));

        if ($lock)
        {
            if ($lock->getUser()->getId() === $user->getId())
            {
                $lock->updateLockDate();
            }
            else
            {
                throw new Exception('Group busy. Another user is editing this group.',
                    Response::HTTP_CONFLICT);
            }
        }
        else
        {
            $repo->cleanForThisUser($user);
            $lock = new $administrationGroupLock($administration, $group, $survey, $user);
            $em->persist($lock);
        }

        $em->flush();
    }
}