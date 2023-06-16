<?php

namespace App\Entity;

use App\Entity\AbstractEntity\AdministrationDataValues;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\AbstractEntity\Administrations;

/**
 * DocumentaryStructureDataValues
 *
 * @ORM\Table(name="documentary_structure_data_values", indexes={
 *     @ORM\Index(name="survey_fk", columns={"survey_fk"}),
 *     @ORM\Index(name="documentary_structure_fk", columns={"documentary_structure_fk"}),
 *     @ORM\Index(name="data_type_fk", columns={"data_type_fk"})})
 * @ORM\Entity(repositoryClass="App\Repository\DocumentaryStructureDataValuesRepository")
 */
class DocumentaryStructureDataValues extends AdministrationDataValues
{
    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=65535, nullable=false)
     */
    private $value;

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
     * DocumentaryStructureDataValues constructor.
     * @param string $value
     * @param DataTypes $dataType
     * @param DocumentaryStructures $documentaryStructure
     * @param Surveys $survey
     */
    public function __construct(string $value, DataTypes $dataType,
                                DocumentaryStructures $documentaryStructure, Surveys $survey)
    {
        $this->value = $value;
        $this->dataType = $dataType;
        $this->documentaryStructure = $documentaryStructure;
        $this->survey = $survey;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getDataType(): ?DataTypes
    {
        return $this->dataType;
    }

    public function setDataType(?DataTypes $dataType): self
    {
        $this->dataType = $dataType;

        return $this;
    }

    public function getDocumentaryStructure(): ?DocumentaryStructures
    {
        return $this->documentaryStructure;
    }

    public function setDocumentaryStructure(?DocumentaryStructures $documentaryStructure): self
    {
        $this->documentaryStructure = $documentaryStructure;

        return $this;
    }

    public function getSurvey(): ?Surveys
    {
        return $this->survey;
    }

    public function setSurvey(?Surveys $survey): self
    {
        $this->survey = $survey;

        return $this;
    }

    public function getAdministration(): Administrations
    {
        return $this->documentaryStructure;
    }


}
