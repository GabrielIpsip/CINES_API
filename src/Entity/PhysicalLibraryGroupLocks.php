<?php

namespace App\Entity;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;

/**
 * PhysicalLibraryGroupLocks
 *
 * pour la gestion des saisies concurentes
 *
 * @ORM\Table(name="physical_library_group_locks", indexes={@ORM\Index(name="physical_library_fk_user_fk", columns={"physical_library_fk"}), @ORM\Index(name="physical_library_group_locks_group_fk", columns={"group_fk"}), @ORM\Index(name="physical_library_group_locks_user_fk", columns={"user_fk"}), @ORM\Index(name="physical_library_group_locks_survey_fk", columns={"survey_fk"}), @ORM\Index(name="physical_library_group_locks_physical_library_fk", columns={"physical_library_fk"})})
 * @ORM\Entity(repositoryClass="App\Repository\PhysicalLibraryGroupLocksRepository")
 */
class PhysicalLibraryGroupLocks
{
    /**
     * @var DateTime
     *
     * @ORM\Column(name="lock_date", type="datetime", nullable=false)
     */
    private $lockDate;

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
     * PhysicalLibraryGroupLocks constructor.
     * @param PhysicalLibraries $physicalLibrary
     * @param Groups $group
     * @param Surveys $survey
     * @param Users $user
     */
    public function __construct(PhysicalLibraries $physicalLibrary, Groups $group, Surveys $survey, Users $user)
    {
        $this->physicalLibrary = $physicalLibrary;
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
