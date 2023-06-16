<?php

namespace App\Entity;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * SurveyValidations
 *
 * pour caractèriser si une enquete est validée ou pas
 *
 * @ORM\Table(name="survey_validations", indexes={
 *     @ORM\Index(name="survey_validation_survey_fk", columns={"survey_fk"}),
 *     @ORM\Index(name="survey_validations_documentary_structure_fk", columns={"documentary_structure_fk"})})
 * @ORM\Entity
 */
class SurveyValidations
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
     * @ORM\Column(name="valid", type="boolean", nullable=false)
     */
    private $valid;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="validation_date", type="datetime", nullable=false)
     * @JMS\Type("DateTime<'Y-m-d H:i:s T'>")
     */
    private $validationDate;

    /**
     * @var DocumentaryStructures
     *
     * @ORM\ManyToOne(targetEntity="DocumentaryStructures")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="documentary_structure_fk", referencedColumnName="id")
     * })
     * @JMS\Exclude
     */
    private $documentaryStructure;

    /**
     * @var Surveys
     *
     * @ORM\ManyToOne(targetEntity="Surveys")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="survey_fk", referencedColumnName="id")
     * })
     * @JMS\Exclude
     */
    private $survey;

    /**
     * SurveyValidations constructor.
     * @param bool $valid
     * @param DocumentaryStructures $documentaryStructure
     * @param Surveys $survey
     */
    public function __construct(bool $valid, Surveys $survey, DocumentaryStructures $documentaryStructure)
    {
        $this->setValid($valid);
        $this->documentaryStructure = $documentaryStructure;
        $this->survey = $survey;
    }


    /**
     * @return bool
     */
    public function getValid(): bool
    {
        return $this->valid;
    }

    /**
     * @param bool $valid
     */
    public function setValid(bool $valid): void
    {
        if ($this->valid === null || ($valid && $valid != $this->valid))
        {
            $date = new DateTime();
            $date = $date->setTimezone(new DateTimeZone("UTC"));
            $this->validationDate = $date;
        }
        $this->valid = $valid;

    }

    /**
     * @return DocumentaryStructures
     */
    public function getDocumentaryStructure(): DocumentaryStructures
    {
        return $this->documentaryStructure;
    }

    /**
     * @param DocumentaryStructures $documentaryStructure
     */
    public function setDocumentaryStructure(DocumentaryStructures $documentaryStructure): void
    {
        $this->documentaryStructure = $documentaryStructure;
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

    /**
     * @return DateTime
     */
    public function getValidationDate(): DateTime
    {
        return $this->validationDate;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

}
