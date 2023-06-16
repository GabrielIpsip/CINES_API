<?php

namespace App\Entity;

use App\Entity\AbstractEntity\AdministrationActiveHistory;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\AbstractEntity\Administrations;

/**
 * PhysicalLibraryActiveHistory
 *
 *  Pour savoir si une PhysicalLibrary est Active ou pas une année
 *
 * @ORM\Table(name="physical_library_active_history", indexes={
 *     @ORM\Index(name="physical_library_link_history_survey_fk", columns={"survey_fk"}),
 *     @ORM\Index(name="physical_library_link_history_physical_library_fk", columns={"physical_library_fk"})})
 * @ORM\Entity(repositoryClass="App\Repository\PhysicalLibraryActiveHistoryRepository")
 */
class PhysicalLibraryActiveHistory extends AdministrationActiveHistory
{
    /**
     * @var PhysicalLibraries
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="PhysicalLibraries")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="physical_library_fk", referencedColumnName="id")
     * })
     */
    private $physicalLibrary;

    /**
     * @var Surveys
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="Surveys")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="survey_fk", referencedColumnName="id")
     * })
     */
    protected $survey;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    protected $active;

    /**
     * PhysicalLibraryActiveHistory constructor.
     * @param PhysicalLibraries $physicalLibrary
     * @param Surveys $survey
     * @param bool $active
     */
    public function __construct(PhysicalLibraries $physicalLibrary, Surveys $survey, bool $active)
    {
        parent::__construct($survey, $active);
        $this->physicalLibrary = $physicalLibrary;
    }

    public function getAdministration(): Administrations
    {
        return $this->physicalLibrary;
    }

}
