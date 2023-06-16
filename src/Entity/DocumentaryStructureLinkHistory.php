<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DocumentaryStructureLinkHistory
 *
 * @ORM\Table(name="documentary_structure_link_history", indexes={
 *     @ORM\Index(name="documentary_structure_link_history_survey_fk", columns={"survey_fk"}),
 *     @ORM\Index(name="documentary_structure_link_history_establishment_fk", columns={"establishment_fk"}),
 *     @ORM\Index(name="documentary_structure_link_history_documentary_structure_fk", columns={"documentary_structure_fk"})})
 * @ORM\Entity
 */
class DocumentaryStructureLinkHistory
{
    /**
     * @var Establishments
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="Establishments")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="establishment_fk", referencedColumnName="id")
     * })
     */
    private $establishment;

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
    private $survey;

    /**
     * DocumentaryStructureLinkHistory constructor.
     * @param Establishments $establishment
     * @param DocumentaryStructures $documentaryStructure
     * @param Surveys $survey
     */
    public function __construct(DocumentaryStructures $documentaryStructure, Establishments $establishment,
                                Surveys $survey)
    {
        $this->documentaryStructure = $documentaryStructure;
        $this->establishment = $establishment;
        $this->survey = $survey;
    }

    /**
     * @return Establishments
     */
    public function getEstablishment(): Establishments
    {
        return $this->establishment;
    }

    /**
     * @return DocumentaryStructures
     */
    public function getDocumentaryStructure(): DocumentaryStructures
    {
        return $this->documentaryStructure;
    }

    /**
     * @return Surveys
     */
    public function getSurvey(): Surveys
    {
        return $this->survey;
    }

    /**
     * @param Establishments $establishment
     */
    public function setEstablishment(Establishments $establishment): void
    {
        $this->establishment = $establishment;
    }


}
