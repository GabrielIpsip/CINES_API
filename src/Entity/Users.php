<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Users
 *
 * @ORM\Table(name="users", uniqueConstraints={@ORM\UniqueConstraint(name="eppn", columns={"eppn"})})
 * @ORM\Entity
 */
class Users implements UserInterface
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
     * @var string
     *
     * @ORM\Column(name="eppn", type="string", length=150, nullable=false)
     * @Assert\NotBlank
     * @Assert\Length(max=150)
     * @Assert\Type("string")
     */
    private $eppn;

    /**
     * @var string|null
     *
     * @ORM\Column(name="mail", type="string", length=150, nullable=true)
     * @Assert\Length(max=150)
     * @Assert\Type("string")
     * @Assert\Email
     */
    private $mail;

    /**
     * @var string|null
     *
     * @ORM\Column(name="phone", type="string", length=16, nullable=true)
     * @Assert\Type("string")
     * @Assert\Length(max=16)
     */
    private $phone;

    /**
     * @var string|null
     *
     * @ORM\Column(name="firstname", type="string", length=50, nullable=true)
     * @Assert\Type("string")
     * @Assert\Length(max=50)
     */
    private $firstname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="lastname", type="string", length=50, nullable=true)
     * @Assert\Type("string")
     * @Assert\Length(max=50)
     */
    private $lastname;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active = '0';

    /**
     * @var bool
     *
     * @ORM\Column(name="valid", type="boolean", nullable=false)
     */
    private $valid = '0';

    /**
     * @var CsrfToken
     */
    public $csrfToken;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEppn(): string
    {
        return $this->eppn;
    }

    /**
     * @param string $eppn
     */
    public function setEppn(string $eppn): void
    {
        $this->eppn = $eppn;
    }

    /**
     * @return string|null
     */
    public function getMail(): ?string
    {
        return $this->mail;
    }

    /**
     * @param string|null $mail
     */
    public function setMail(?string $mail): void
    {
        $this->mail = $mail;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string|null $phone
     */
    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @return string|null
     */
    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    /**
     * @param string|null $firstname
     */
    public function setFirstname(?string $firstname): void
    {
        $this->firstname = $firstname;
    }

    /**
     * @return string|null
     */
    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    /**
     * @param string|null $lastname
     */
    public function setLastname(?string $lastname): void
    {
        $this->lastname = $lastname;
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
        $this->valid = $valid;
    }

    public function update(Users $user): void
    {
        $this->setFirstname($user->getFirstname());
        $this->setLastname($user->getLastname());
        $this->setPhone($user->getPhone());
        $this->setActive($user->getActive());
    }

    public function toArray(): array
    {
        $userArray['id'] = $this->getId();
        $userArray['eppn'] = $this->getEppn();
        $userArray['mail'] = $this->getMail();
        $userArray['firstname'] = $this->getFirstname();
        $userArray['lastName'] = $this->getLastname();
        $userArray['phone'] = $this->getPhone();
        $userArray['active'] = $this->getActive();
        $userArray['valid'] = $this->getValid();

        return $userArray;
    }

    /**
     * @inheritDoc
     */
    public function getRoles(): array
    {
        return array('ROLE_USER');
    }

    /**
     * @inheritDoc
     */
    public function getPassword(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getUsername(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    /**
     * @inheritDoc
     */
    public function getUserIdentifier(): string
    {
        return $this->getId();
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials()
    {
    }
}
