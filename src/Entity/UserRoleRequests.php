<?php

namespace App\Entity;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * UserRoleRequests
 *
 * @ORM\Table(name="user_role_requests", indexes={
 *     @ORM\Index(name="user_role_requests_user_fk", columns={"user_fk"}),
 *     @ORM\Index(name="user_role_requests_documentary_structure_fk", columns={"documentary_structure_fk"}),
 *     @ORM\Index(name="user_role_requests_role_fk", columns={"role_fk"})})
 * @ORM\Entity(repositoryClass="App\Repository\UserRoleRequestsRepository")
 */
class UserRoleRequests
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="smallint", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="creation", type="datetime", nullable=false)
     * @JMS\Type("DateTime<'Y-m-d H:i:s T'>")
     */
    private $creation;

    /**
     * @var DocumentaryStructures
     *
     * @ORM\ManyToOne(targetEntity="DocumentaryStructures")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="documentary_structure_fk", referencedColumnName="id")
     * })
     */
    private $documentaryStructure;

    /**
     * @var Roles
     *
     * @ORM\ManyToOne(targetEntity="Roles")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="role_fk", referencedColumnName="id")
     * })
     */
    private $role;

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
     * UserRoleRequests constructor.
     * @param DocumentaryStructures|null $documentaryStructure
     * @param Roles $role
     * @param Users $user
     */
    public function __construct(?DocumentaryStructures $documentaryStructure, Roles $role,
                                Users $user)
    {
        $creation = new DateTime();
        $creation = $creation->setTimezone(new DateTimeZone("UTC"));

        $this->creation = $creation;
        $this->documentaryStructure = $documentaryStructure;
        $this->role = $role;
        $this->user = $user;
    }


    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
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
     * @return Roles
     */
    public function getRole(): Roles
    {
        return $this->role;
    }

    /**
     * @param Roles $role
     */
    public function setRole(Roles $role): void
    {
        $this->role = $role;
    }

    /**
     * @return Users
     */
    public function getUser(): Users
    {
        return $this->user;
    }

    /**
     * @param Users $user
     */
    public function setUser(Users $user): void
    {
        $this->user = $user;
    }

    /**
     * @return DateTime
     */
    public function getCreation(): DateTime
    {
        return $this->creation;
    }

    /**
     * @param DateTime $creation
     */
    public function setCreation(DateTime $creation): void
    {
        $this->creation = $creation;
    }

}
