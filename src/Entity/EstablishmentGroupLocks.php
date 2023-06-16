<?php

namespace App\Entity;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;

/**
 * EstablishmentGroupLocks
 *
 * @ORM\Table(name="establishment_group_locks", indexes={
 *     @ORM\Index(name="establishment_group_locks_survey_fk", columns={"survey_fk"}),
 *     @ORM\Index(name="establishment_group_locks_establishment_fk", columns={"establishment_fk"}), @ORM\Index(name="establishment_group_locks_group_fk", columns={"group_fk"}), @ORM\Index(name="establishment_group_locks_user_fk", columns={"user_fk"}), @ORM\Index(name="establishment_fk_user_fk", columns={"establishment_fk"})})
 * @ORM\Entity(repositoryClass="App\Repository\EstablishmentGroupLocksRepository")
 */
class EstablishmentGroupLocks
{
    /**
     * @var DateTime
     *
     * @ORM\Column(name="lock_date", type="datetime", nullable=false)
     */
    private $lockDate;

    /**
     * @var Establishments
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="Establishments")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="establishment_fk", referencedColumnName="id")
     * })
     */
    private $establishment;

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
     * EstablishmentGroupLocks constructor.
     * @param Establishments $establishment
     * @param Groups $group
     * @param Surveys $survey
     * @param Users $user
     */
    public function __construct(Establishments $establishment, Groups $group, Surveys $survey, Users $user)
    {
        $this->establishment = $establishment;
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
        $this->lockDate = $this->lockDate->setTimezone(new DateTimeZone("UTC"));
    }
}
