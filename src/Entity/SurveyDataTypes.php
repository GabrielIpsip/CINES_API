<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SurveyDataTypes
 *
 * @ORM\Table(name="survey_data_types", indexes={@ORM\Index(name="type_fk", columns={"type_fk"}),
 *                                               @ORM\Index(name="survey_fk", columns={"survey_fk"})})
 * @ORM\Entity
 */
class SurveyDataTypes
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active;

    /**
     * @var Surveys
     *
     * @ORM\ManyToOne(targetEntity="Surveys")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="survey_fk", referencedColumnName="id")
     * })
     */
    private $survey;

    /**
     * @var DataTypes
     *
     * @ORM\ManyToOne(targetEntity="DataTypes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="type_fk", referencedColumnName="id")
     * })
     */
    private $type;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

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

    public function getType(): ?DataTypes
    {
        return $this->type;
    }

    public function setType(?DataTypes $type): self
    {
        $this->type = $type;

        return $this;
    }


}
