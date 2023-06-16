<?php

namespace App\Entity;

use App\Entity\AbstractEntity\AdministrationDataValues;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\AbstractEntity\Administrations;

/**
 * PhysicalLibraryDataValues
 *
 * @ORM\Table(name="physical_library_data_values", indexes={
 *     @ORM\Index(name="survey_fk", columns={"survey_fk"}),
 *     @ORM\Index(name="data_type_fk", columns={"data_type_fk"}),
 *     @ORM\Index(name="physical_library_fk", columns={"physical_library_fk"})})
 * @ORM\Entity(repositoryClass="App\Repository\PhysicalLibraryDataValuesRepository")
 */
class PhysicalLibraryDataValues extends AdministrationDataValues
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
     * PhysicalLibraryDataValues constructor.
     * @param string $value
     * @param DataTypes $dataType
     * @param PhysicalLibraries $physicalLibrary
     * @param Surveys $survey
     */
    public function __construct(string $value, DataTypes $dataType, PhysicalLibraries $physicalLibrary, Surveys $survey)
    {
        $this->value = $value;
        $this->dataType = $dataType;
        $this->physicalLibrary = $physicalLibrary;
        $this->survey = $survey;
    }


    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * @return DataTypes
     */
    public function getDataType(): DataTypes
    {
        return $this->dataType;
    }

    /**
     * @param DataTypes $dataType
     */
    public function setDataType(DataTypes $dataType): void
    {
        $this->dataType = $dataType;
    }

    /**
     * @return PhysicalLibraries
     */
    public function getPhysicalLibrary(): PhysicalLibraries
    {
        return $this->physicalLibrary;
    }

    /**
     * @param PhysicalLibraries $physicalLibrary
     */
    public function setPhysicalLibrary(PhysicalLibraries $physicalLibrary): void
    {
        $this->physicalLibrary = $physicalLibrary;
    }

    /**
     * @return Surveys
     */
    public function getSurvey(): Surveys
    {
        return $this->survey;
    }

    /**
     * @param Surveys $survey
     */
    public function setSurvey(Surveys $survey): void
    {
        $this->survey = $survey;
    }

    public function getAdministration(): Administrations
    {
        return $this->physicalLibrary;
    }

}
