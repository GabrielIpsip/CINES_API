<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * GroupInstructions
 *
 * @ORM\Table(name="group_instructions", indexes={
 *     @ORM\Index(name="group_instructions_instruction_fk", columns={"instruction_fk"}),
 *     @ORM\Index(name="group_instructions_survey_fk", columns={"survey_fk"}),
 *     @ORM\Index(name="IDX_753BCB12E973D819", columns={"group_fk"})})
 * @ORM\Entity
 */
class GroupInstructions
{
    /**
     * @var Groups
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="Groups")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="group_fk", referencedColumnName="id")
     * })
     * @JMS\Exclude
     */
    private $group;

    /**
     * @var Contents
     *
     * @ORM\ManyToOne(targetEntity="Contents")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="instruction_fk", referencedColumnName="id")
     * })
     * @JMS\Exclude
     */
    private $instruction;

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
     * GroupInstructions constructor.
     * @param Groups $group
     * @param Contents $instruction
     * @param Surveys $survey
     */
    public function __construct(Groups $group, Contents $instruction, Surveys $survey)
    {
        $this->group = $group;
        $this->instruction = $instruction;
        $this->survey = $survey;
    }

    /**
     * @return Groups
     */
    public function getGroup(): Groups
    {
        return $this->group;
    }

    /**
     * @param Groups $group
     */
    public function setGroup(Groups $group): void
    {
        $this->group = $group;
    }

    /**
     * @return Contents
     */
    public function getInstruction(): Contents
    {
        return $this->instruction;
    }

    /**
     * @param Contents $instruction
     */
    public function setInstruction(Contents $instruction): void
    {
        $this->instruction = $instruction;
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

}
