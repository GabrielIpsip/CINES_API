<?php

namespace App\Entity;

use App\Entity\AbstractEntity\Administrations;
use App\Entity\AbstractEntity\AdministrationActiveHistory;
use Doctrine\ORM\Mapping as ORM;

/**
 * DocumentaryStructureActiveHistory
 *
 * @ORM\Table(name="documentary_structure_active_history", indexes={
 *     @ORM\Index(name="documentary_structure_link_history_survey_fk", columns={"survey_fk"}),
 *     @ORM\Index(name="documentary_structure_link_history_documentary_structure_fk", columns={"documentary_structure_fk"})})
 * @ORM\Entity
 */
class DocumentaryStructureActiveHistory extends AdministrationActiveHistory
{
    /**
     * @var DocumentaryStructures
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="DocumentaryStructures")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="documentary_structure_fk", referencedColumnName="id")
     * })
     */
    private $documentaryStructure;

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
     * DocumentaryStructureActiveHistory constructor.
     * @param DocumentaryStructures $documentaryStructure
     * @param Surveys $survey
     * @param bool $active
     */
    public function __construct(DocumentaryStructures $documentaryStructure, Surveys $survey, bool $active)
    {
        parent::__construct($survey, $active);
        $this->documentaryStructure = $documentaryStructure;
    }


    public function getAdministration(): Administrations
    {
        return $this->documentaryStructure;
    }

}
