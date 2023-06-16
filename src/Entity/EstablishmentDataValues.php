<?php

namespace App\Entity;

use App\Entity\AbstractEntity\AdministrationDataValues;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use App\Entity\AbstractEntity\Administrations;

/**
 * EstablishmentDataValues
 *
 * @ORM\Table(name="establishment_data_values", indexes={@ORM\Index(name="survey_fk", columns={"survey_fk"}),
 *     @ORM\Index(name="establishment_fk", columns={"establishment_fk"}),
 *     @ORM\Index(name="data_type_fk", columns={"data_type_fk"})})
 * @ORM\Entity(repositoryClass="App\Repository\EstablishmentDataValuesRepository")
 */
class EstablishmentDataValues extends AdministrationDataValues
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
     * @JMS\Exclude
     */
    private $dataType;

    /**
     * @var Establishments
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="Establishments")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="establishment_fk", referencedColumnName="id")
     * })
     * @JMS\Exclude
     */
    private $establishment;

    /**
     * @var Surveys
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="Surveys")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="survey_fk", referencedColumnName="id")
     * })
     * @JMS\Exclude
     */
    private $survey;

    /**
     * EstablishmentDataValues constructor.
     * @param string $value
     * @param DataTypes $dataType
     * @param Establishments $establishment
     * @param Surveys $survey
     */
    public function __construct(string $value, DataTypes $dataType, Establishments $establishment, Surveys $survey)
    {
        $this->value = $value;
        $this->dataType = $dataType;
        $this->establishment = $establishment;
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

    public function getEstablishment(): ?Establishments
    {
        return $this->establishment;
    }

    public function setEstablishment(?Establishments $establishment): self
    {
        $this->establishment = $establishment;

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
        return $this->establishment;
    }
}
