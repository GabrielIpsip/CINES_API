<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserRoles
 *
 * @ORM\Table(name="user_roles",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="user_fk", columns={"user_fk", "role_fk", "documentary_structure_fk"})},
 *     indexes={
 *      @ORM\Index(name="user_roles_role_fk", columns={"role_fk"}),
 *      @ORM\Index(name="user_roles_user_fk", columns={"user_fk"}),
 *      @ORM\Index(name="user_roles_documentary_structure_fk", columns={"documentary_structure_fk"})})
 * @ORM\Entity
 */
class UserRoles
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
     * @var DocumentaryStructures
     *
     * @ORM\ManyToOne(targetEntity="DocumentaryStructures")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="documentary_structure_fk", referencedColumnName="id")
     * })
     */
    private $documentaryStructure;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active = true;

    /**
     * UserRoles constructor.
     * @param Roles $role
     * @param Users $user
     * @param DocumentaryStructures|null $documentaryStructure
     * @param bool $active
     */
    public function __construct(Roles $role, Users $user, ?DocumentaryStructures $documentaryStructure, ?bool $active)
    {
        $this->role = $role;
        $this->user = $user;
        $this->documentaryStructure = $documentaryStructure;
        if ($active !== null)
        {
            $this->active = $active;
        }
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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
     * @return DocumentaryStructures
     */
    public function getDocumentaryStructure(): ?DocumentaryStructures
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
