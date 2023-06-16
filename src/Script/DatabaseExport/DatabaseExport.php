<?php

namespace App\Script\DatabaseExport;

use App\Common\Classes\DatabaseExportDate;
use App\Common\Classes\DatabaseExportLocker;
use App\Common\Enum\State;
use App\Common\Traits\AdministrationActiveHistoryTrait;
use App\Common\Traits\DataTypesTrait;
use App\Common\Traits\DocumentaryStructuresTrait;
use App\Common\Traits\EstablishmentsTrait;
use App\Common\Traits\GroupsTrait;
use App\Common\Traits\IndicatorsTrait;
use App\Common\Traits\PhysicalLibrariesTrait;
use App\Common\Traits\SurveysTrait;
use App\Common\Traits\TextsTrait;
use App\Common\Traits\TranslationsTrait;
use App\Controller\DatabaseExportController;
use App\Controller\AbstractController\ESGBUController;
use App\Entity\DataTypes;
use App\Entity\Surveys;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;
use function PHPUnit\Framework\throwException;

/**
 * Use bin/console app:database-export to send this script. Called in DatabaseExportController.
 * Class DatabaseExport
 * @package App
 */
class DatabaseExport extends Command
{
    use DataTypesTrait,
        DocumentaryStructuresTrait,
        PhysicalLibrariesTrait,
        TranslationsTrait,
        DatabaseExportCSVTrait,
        DatabaseExportElasticsearchTrait,
        AdministrationActiveHistoryTrait,
        SurveysTrait,
        EstablishmentsTrait,
        TextsTrait,
        IndicatorsTrait,
        GroupsTrait;

    /**
     * @var string
     */
    protected static $defaultName = "app:database-export";

    /**
     * @var ManagerRegistry Doctrine entity manager
     */
    private $em;

    /**
     * @var ManagerRegistry Doctrine entity manager
     */
    private $managerRegistry;

    /**
     * @var Surveys[] All existing published surveys in database.
     */
    private $surveys;

    /**
     * @var DataTypes[] All existing dataType in database indexed by dataType id.
     */
    private $indexedDataTypes;

    /**
     * @var array  Indexed by surveyId
     */
    private $indexedEstablishments;

    /**
     * @var array Indexed by surveyId and establishmentId
     */
    private $indexedDocStruct;

    /**
     * @var array Indexed by surveyId and docStructId
     */
    private $indexedPhysicLib;

    /**
     * @var string Export for just last survey.
     */
    private $justLastSurvey;

    /**
     * @var PublisherInterface To send Mercure message.
     */
    private $publisher;

    /**
     * @var bool Ignore Mercure.
     */
    private $forceMode;

    const DIRECTORY = 'database_export';
    const DEFAULT_LANG = ESGBUController::DEFAULT_LANG;

    const JUST_LAST_SURVEY_COMMAND = 'last-survey';
    const FORCE_COMMAND = 'force';

    protected function configure()
    {
        $this->setDescription("To fill elasticsearch index and build CSV file of ESGBU database")
        ->addOption(
            self::JUST_LAST_SURVEY_COMMAND,
            'l',
            InputOption::VALUE_REQUIRED,
            'Just create CSV for last survey. Value must be '.
            '\'insitution\', \'documentaryStructure\', \'physicalLibrary\''
        )
        ->addOption(
            self::FORCE_COMMAND,
            'f',
            InputOption::VALUE_NONE,
            'Force export (ignore Mercure)'
        );
    }

    public function __construct(ManagerRegistry $managerRegistry, PublisherInterface $publisher, string $name = null)
    {
        try
        {
            parent::__construct($name);
            $this->managerRegistry = $managerRegistry;
            $this->publisher = $publisher;
         }
        catch (Exception $e)
        {
            print_r('ERROR 1 : ' . $e->getMessage() . "\n");
            exit(1);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->managerRegistry->getConnection()->getConfiguration()->setSQLLogger();

        try
        {
            $lastSurveyArg = $input->getOption(self::JUST_LAST_SURVEY_COMMAND);
            $this->forceMode = $input->getOption(self::FORCE_COMMAND);

            $this->sendStartDatabaseExportMessage();

            if (!file_exists(self::DIRECTORY))
            {
                mkdir(self::DIRECTORY, 0755);
            }

            if ($lastSurveyArg)
            {
                $this->justLastSurvey = $lastSurveyArg;
                $this->surveys = [$this->getLastActiveSurvey()];
            }
            else
            {
                $this->justLastSurvey = null;
                $this->surveys = $this->getAllSurveyByState(State::PUBLISHED);
            }

            $this->indexedDataTypes = $this->getAllDataTypesIndexed(true);

            $this->indexedEstablishments = $this->getAllActiveEstablishmentByYear($this->surveys);
            $this->indexedDocStruct = $this->getAllDocStructIndexedByYearAndEstablishment($this->surveys);
            $this->indexedPhysicLib =$this->getAllPhysicLibIndexedByYearAndDocStruct($this->surveys);

            if (!$this->justLastSurvey)
            {
                $this->executeElasticsearch(self::DIRECTORY);
            }
            $this->executeCSV(self::DIRECTORY);

            try
            {
                if (!$this->justLastSurvey)
                {
                    print("Updating indicators..\n");
                    $this->updateIndicatorCache();
                }
            }
            catch (Exception $e)
            {
                print('Can\'t update indicator.. : ' . $e->getMessage() . "\n");
            }
        }
        catch (Exception $e)
        {
            print('ERROR 2 : ' . $e->getTraceAsString() . "\n");
            $this->sendEndDatabaseExportMessage(false);
            throwException($e);
            return 2;
        }

        if ($this->justLastSurvey)
        {
            $this->sendEndDatabaseExportMessage(false);
        }
        else
        {
            $this->sendEndDatabaseExportMessage(true);
        }

        return 0;
    }

    private function sendMercureMessage(string $message)
    {
        $update = new Update(DatabaseExportController::EXPORT_KEY, $message);
        $this->publisher->__invoke($update);
    }

    private function sendEndDatabaseExportMessage(bool $updateDate)
    {
        if ($updateDate)
        {
            DatabaseExportDate::saveDatabaseExportDate();
        }

        if ($this->forceMode)
        {
            return;
        }

        DatabaseExportLocker::unlock();
        $this->sendMercureMessage('database-export-end');
    }

    private function sendStartDatabaseExportMessage()
    {
        if ($this->forceMode)
        {
            return;
        }

        $this->sendMercureMessage('database-export-start');
        DatabaseExportLocker::lock();
    }

}
