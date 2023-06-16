<?php

namespace App\Controller;

use App\Common\Classes\DatabaseExportDate;
use App\Common\Classes\DatabaseExportLocker;
use App\Common\Enum\Role;
use App\Script\DatabaseExport\DatabaseExport;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class DatabaseExportController
 * @package App\Controller
 * @SWG\Tag(name="Database export")
 */
class DatabaseExportController extends ESGBUController
{
    private const DATABASE_EXPORT_COMMAND = 'php ../bin/console app:database-export';
    private const WITE_LOG_COMMAND = '> /tmp/database-export-log.txt 2> /tmp/database-export-error-log.txt &';

    public const EXPORT_KEY = 'http://esgbu.esr.gouv.fr/database-export';

    /**
     * Return datetime of last export database.
     * @SWG\Response(
     *     response="200",
     *     description="Return datetime of last export database.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="date", type="string"),
     *     @SWG\Property(property="time_zone", type="integer"),
     *     @SWG\Property(property="timezone", type="string"))
     * )
     * @Rest\Get(
     *      path = "/public/database-export/date",
     *      name = "app_database_export_date"
     * )
     * @Rest\View
     * @return View
     */
    public function publicDatabaseExportDate(): View
    {
        return $this->createView(DatabaseExportDate::getDatabaseExportDate(), Response::HTTP_OK);
    }

    /**
     * Check if database export is running.
     * @SWG\Response(
     *     response="200",
     *     description="Return false if you can run export, else true.",
     *     @SWG\Property(property="response", type="boolean")
     * )
     * @SWG\Response(response="403", description="Not authorized.")
     * @Rest\Get(
     *      path = "/database-export/locked",
     *      name = "app_database_export_locked"
     * )
     * @Rest\View
     * @return View
     */
    public function databaseExportLocked(): View
    {
        try
        {
            $this->checkRights([Role::ADMIN, Role::ADMIN_RO]);
            return $this->createView(DatabaseExportLocker::isLocked(), Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Launch database export.
     * @SWG\Response(
     *     response="200",
     *     description="Return true if the export has been launched, else false is export can't be launched.",
     *     @SWG\Property(property="response", type="boolean")
     * )
     * @Rest\QueryParam(name="justLastActiveSurvey", requirements="institution|documentaryStructure|physicalLibrary",
     *     nullable=true)
     * @SWG\Response(response="403", description="Not authorized.")
     * @Rest\Get(
     *      path = "/database-export/run",
     *      name = "app_database_export_run"
     * )
     * @Rest\View
     * @param string|null $justLastActiveSurvey
     * @return View
     */
    public function databaseExportRun(?string $justLastActiveSurvey): View
    {
        try
        {
            if ($justLastActiveSurvey)
            {
                $this->checkRights([Role::ADMIN, Role::ADMIN_RO]);
            }
            else
            {
                $this->checkRights([Role::ADMIN]);
            }

            if (DatabaseExportLocker::isLocked())
            {
                return $this->createView(false, Response::HTTP_OK);
            }
            else
            {
                $command = self::DATABASE_EXPORT_COMMAND;
                if ($justLastActiveSurvey)
                {
                    $command .= ' --' . DatabaseExport::JUST_LAST_SURVEY_COMMAND . '=' . $justLastActiveSurvey;
                }
                $command .= ' ' . self::WITE_LOG_COMMAND;

                $process = Process::fromShellCommandline($command);
                $process->run(); // Async

                return $this->createView(true, Response::HTTP_OK);
            }
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }
}
