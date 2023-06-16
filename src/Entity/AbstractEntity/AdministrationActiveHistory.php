<?php


namespace App\Entity\AbstractEntity;


use App\Entity\Surveys;

abstract class AdministrationActiveHistory
{
    /**
     * @var Surveys
     */
    protected $survey;

    /**
     * @var bool
     */
    protected $active;

    /**
     * AdministrationActiveHistory constructor.
     * @param Surveys $survey
     * @param bool $active
     */
    public function __construct(Surveys $survey, bool $active)
    {
        $this->survey = $survey;
        $this->active = $active;
    }

    abstract public function getAdministration(): Administrations;

    /**
     * @return Surveys
     */
    public function getSurvey(): Surveys
    {
        return $this->survey;
    }

    /**
     * @return bool
     */
    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

}