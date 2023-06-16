<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DocumentaryStructureComments
 *
 * @ORM\Table(name="documentary_structure_comments", indexes={
 *     @ORM\Index(name="documentary_structure_comments_survey_fk", columns={"survey_fk"}),
 *     @ORM\Index(name="documentary_structure_comments_data_type_fk", columns={"data_type_fk"}),
 *     @ORM\Index(name="IDX_2E228E2825AB3B7", columns={"documentary_structure_fk"})})
 * @ORM\Entity(repositoryClass="App\Repository\DocumentaryStructureCommentsRepository")
 */
class DocumentaryStructureComments
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="comment", type="text", length=65535, nullable=true)
     */
    private $comment;

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
     * @var DataTypes
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="DataTypes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="data_type_fk", referencedColumnName="id")
     * })
     */
    private $dataType;

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
     * DocumentaryStructureComments constructor.
     * @param string|null $comment
     * @param Surveys $survey
     * @param DataTypes $dataType
     * @param DocumentaryStructures $documentaryStructure
     */
    public function __construct(?string $comment, Surveys $survey, DocumentaryStructures $documentaryStructure,
                                DataTypes $dataType)
    {
        $this->comment = $comment;
        $this->survey = $survey;
        $this->documentaryStructure = $documentaryStructure;
        $this->dataType = $dataType;
    }

    /**
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @param string|null $comment
     */
    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    /**
     * @return Surveys
     */
    public function getSurvey(): Surveys
    {
        return $this->survey;
    }

    /**
     * @return DataTypes
     */
    public function getDataType(): DataTypes
    {
        return $this->dataType;
    }

    /**
     * @return DocumentaryStructures
     */
    public function getDocumentaryStructure(): DocumentaryStructures
    {
        return $this->documentaryStructure;
    }


}
