<?php


namespace App\Script;

use App\Common\Enum\Type;
use App\Entity\DocumentaryStructureActiveHistory;
use App\Entity\DocumentaryStructures;
use App\Entity\PhysicalLibraries;
use App\Entity\PhysicalLibraryActiveHistory;
use App\Entity\PhysicalLibraryDataValues;
use App\Entity\Surveys;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FillActiveHistory extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = "app:fill-active-history";

    /**
     * @var ManagerRegistry Doctrine entity manager
     */
    private $managerRegistry;

    /**
     * @var Surveys
     */
    private $lastSurvey;

    public function __construct(ManagerRegistry $managerRegistry, string $name = null)
    {
        parent::__construct($name);
        $this->managerRegistry = $managerRegistry;
    }

     protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->managerRegistry->getConnection()->getConfiguration()->setSQLLogger();
        ini_set('memory_limit', '-1');

        $docStructs = $this->managerRegistry->getRepository(DocumentaryStructures::class)->findAll();
        $surveys = $this->managerRegistry->getRepository(Surveys::class)->findBy([], ['id' => 'DESC']);
        $this->lastSurvey = $surveys[0];

        foreach ($docStructs as $docStruct)
        {
            $physicLibs = $this->managerRegistry->getRepository(PhysicalLibraries::class)
                ->findBy(['documentaryStructure' => $docStruct]);

            foreach ($surveys as $survey)
            {
                $docStructActiveLine = $this->managerRegistry->getRepository(DocumentaryStructureActiveHistory::class)
                    ->findOneBy(['documentaryStructure' => $docStruct, 'survey' => $survey]);

                if ($docStructActiveLine)
                {
                    $isActiveDocStruct = $docStructActiveLine->getActive();
                }
                else
                {
                    $isActiveDocStruct = $docStruct->getActive();
                }

                $this->fillPhysicLib($physicLibs, $docStruct, $survey, $isActiveDocStruct);
            }
        }

        $this->managerRegistry->flush();
        return 0;
    }


    /**
     * @param PhysicalLibraries[] $physicLibs
     * @param DocumentaryStructures $docStruct
     * @param Surveys $survey
     * @param bool $isActiveDocStruct
     */
    private function fillPhysicLib(array $physicLibs, DocumentaryStructures $docStruct, Surveys $survey,
                                   bool $isActiveDocStruct)
    {
        foreach ($physicLibs as $physicLib)
        {
            $physicLibActiveLine = $this->managerRegistry
                ->getRepository(PhysicalLibraryActiveHistory::class)
                ->findOneBy(['physicalLibrary' => $physicLib, 'survey' => $survey]);

            $physicLibActiveStatus = false;

            if ($isActiveDocStruct)
            {
                $physicLibActiveStatus = $this->getActiveStatusPhysicLib($physicLib, $survey);
            }

            if (!$physicLibActiveStatus)
            {
                print("({$physicLib->getId()}) {$physicLib->getUseName()} pour l'enquête '({$survey->getId()}) {$survey->getName()}' doit être inactive\n");
            }

            if ($physicLibActiveLine)
            {
                $physicLibActiveLine->setActive($physicLibActiveStatus);
            }
            else
            {
                $activeLine = new PhysicalLibraryActiveHistory($physicLib, $survey, $physicLibActiveStatus);
                $this->managerRegistry->persist($activeLine);
            }

            if ($survey->getId() === $this->lastSurvey->getId())
            {
                $physicLib->setActive($physicLibActiveStatus);
            }
        }
    }

    private function getActiveStatusPhysicLib(PhysicalLibraries $physicLib, Surveys $survey): bool
    {
        $values = $this->managerRegistry->getRepository(PhysicalLibraryDataValues::class)
            ->findBy(['physicalLibrary' => $physicLib, 'survey' => $survey]);

        if (count($values) === 0)
        {
            return false;
        }

        foreach ($values as $value)
        {
            $typeId = $value->getDataType()->getType()->getId();
            $contentValue = trim($value->getValue());

            if ($typeId === Type::operation || strlen($contentValue) === 0)
            {
                continue;
            }

            if ($typeId !== Type::boolean)
            {
                return true;
            }

            if ($contentValue === '1' || $contentValue === 'true')
            {
                return true;
            }
        }

        return false;
    }
}
