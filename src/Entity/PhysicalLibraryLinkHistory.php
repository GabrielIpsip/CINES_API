<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PhysicalLibraryLinkHistory
 *
 * @ORM\Table(name="physical_library_link_history", indexes={
 *     @ORM\Index(name="physical_library_link_history_survey_fk", columns={"survey_fk"}),
 *     @ORM\Index(name="physical_library_link_history_documentary_structure_fk", columns={"documentary_structure_fk"}),
 *     @ORM\Index(name="physical_library_link_history_physical_library_fk", columns={"physical_library_fk"})})
 * @ORM\Entity
 */
class PhysicalLibraryLinkHistory
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
    private $survey;

    /**
     * PhysicalLibraryLinkHistory constructor.
     * @param DocumentaryStructures $documentaryStructure
     * @param PhysicalLibraries $physicalLibrary
     * @param Surveys $survey
     */
    public function __construct(PhysicalLibraries $physicalLibrary, DocumentaryStructures $documentaryStructure, Surveys $survey)
    {
        $this->physicalLibrary = $physicalLibrary;
        $this->documentaryStructure = $documentaryStructure;
        $this->survey = $survey;
    }

    /**
     * @param DocumentaryStructures $documentaryStructure
     */
    public function setDocumentaryStructure(DocumentaryStructures $documentaryStructure): void
    {
        $this->documentaryStructure = $documentaryStructure;
    }

    /**
     * @return DocumentaryStructures
     */
    public function getDocumentaryStructure(): DocumentaryStructures
    {
        return $this->documentaryStructure;
    }

    /**
     * @return PhysicalLibraries
     */
    public function getPhysicalLibrary(): PhysicalLibraries
    {
        return $this->physicalLibrary;
    }

    /**
     * @return Surveys
     */
    public function getSurvey(): Surveys
    {
        return $this->survey;
    }

}
