<?php

namespace App\Entity;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;

/**
 * DocumentaryStructureGroupLocks
 *
 * Lors d'une saisie, il y a un lock afin que si un utilisateur autre arrive, il sera bloqué.
 * @ORM\Table(name="documentary_structure_group_locks", indexes={@ORM\Index(name="documentary_structure_group_locks_survey_fk", columns={"survey_fk"}), @ORM\Index(name="documentary_structure_group_locks_documentary_structure_fk", columns={"documentary_structure_fk"}), @ORM\Index(name="documentary_structure_group_locks_group_fk", columns={"group_fk"}), @ORM\Index(name="documentary_structure_group_locks_user_fk", columns={"user_fk"}), @ORM\Index(name="documentary_structure_fk_user_fk", columns={"documentary_structure_fk"})})
 * @ORM\Entity(repositoryClass="App\Repository\DocumentaryStructureGroupLocksRepository")
 */
class DocumentaryStructureGroupLocks
{
    /**
     * @var DateTime
     *
     * @ORM\Column(name="lock_date", type="datetime", nullable=false)
     */
    private $lockDate;

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
     * @var Groups
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="Groups")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="group_fk", referencedColumnName="id")
     * })
     */
    private $group;

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
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_fk", referencedColumnName="id")
     * })
     */
    private $user;

    /**
     * DocumentaryStructureGroupLocks constructor.
     * @param DocumentaryStructures $documentaryStructure
     * @param Groups $group
     * @param Surveys $survey
     * @param Users $user
     */
    public function __construct(DocumentaryStructures $documentaryStructure, Groups $group, Surveys $survey, Users $user)
    {
        $this->documentaryStructure = $documentaryStructure;
        $this->group = $group;
        $this->survey = $survey;
        $this->user = $user;

        $this->updateLockDate();
    }

    /**
     * @return Users
     */
    public function getUser(): Users
    {
        return $this->user;
    }

    public function updateLockDate(): void
    {
        $this->lockDate = new DateTime();
        $this->lockDate = $this->lockDate->setTimezone(new DateTimeZone('UTC'));
    }

}
