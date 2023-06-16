<?php

namespace App\Entity;

use App\Entity\AbstractEntity\Administrations;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Establishments
 *
 * @ORM\Table(name="establishments", indexes={@ORM\Index(name="type_fk", columns={"type_fk"})})
 * @ORM\Entity(repositoryClass="App\Repository\EstablishmentsRepository")
 */
class Establishments extends Administrations
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="smallint", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="official_name", type="string", length=255, nullable=false)
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     * @Assert\Type("string")
     */
    protected $officialName;

    /**
     * @var string
     *
     * @ORM\Column(name="use_name", type="string", length=255, nullable=false)
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     * @Assert\Type("string")
     */
    protected $useName;

    /**
     * @var string
     *
     * @ORM\Column(name="acronym", type="string", length=50, nullable=true)
     * @Assert\Length(max=50)
     * @Assert\Type("string")
     */
    private $acronym;

    /**
     * @var string
     *
     * @ORM\Column(name="brand", type="string", length=150, nullable=true)
     * @Assert\Length(max=150)
     * @Assert\Type("string")
     */
    private $brand;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     * @Assert\Type("bool")
     * @Assert\NotNull
     */
    protected $active;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255, nullable=false)
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     * @Assert\Type("string")
     */
    protected $address;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=255, nullable=false)
     * @Assert\NotBlank
     * @Assert\Length(max=150)
     * @Assert\Type("string")
     */
    protected $city;

    /**
     * @var string
     *
     * @ORM\Column(name="postal_code", type="string", nullable=false, options={"fixed"=true})
     * @Assert\NotBlank
     * @Assert\Regex("/\d{5}/")
     * @Assert\Length(min="5", max="5")
     */
    protected $postalCode;

    /**
     * @var string
     *
     * @ORM\Column(name="website", type="string", length=255, nullable=false)
     * @Assert\NotBlank
     * @Assert\Url
     * @Assert\Length(max=255)
     */
    private $website;

    /**
     * @var string|null
     *
     * @ORM\Column(name="instruction", type="text", length=65535, nullable=true)
     * @Assert\Length(max=65535)
     * @Assert\Type("string")
     */
    protected $instruction;

    /**
     * @var EstablishmentTypes
     * @ORM\ManyToOne(targetEntity="EstablishmentTypes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="type_fk", referencedColumnName="id")
     * })
     */
    private $type;

    /**
     * @var Departments
     * @ORM\ManyToOne(targetEntity="Departments")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="department_fk", referencedColumnName="id")
     * })
     */
    private $department;

    public function getAcronym(): ?string
    {
        return $this->acronym;
    }

    public function setAcronym(?string $acronym): self
    {
        $this->acronym = $acronym;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getType(): ?EstablishmentTypes
    {
        return $this->type;
    }

    public function setType(?EstablishmentTypes $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Departments
     */
    public function getDepartment(): Departments
    {
        return $this->department;
    }

    /**
     * @param Departments $department
     */
    public function setDepartment(Departments $department): void
    {
        $this->department = $department;
    }

    public function update(Establishments $newEstablishment, EstablishmentTypes $newEstablishmentType,
                           Departments $department)
    {
        $this->setOfficialName($newEstablishment->getOfficialName());
        $this->setUseName($newEstablishment->getUseName());
        $this->setActive($newEstablishment->getActive());
        $this->setAddress($newEstablishment->getAddress());
        $this->setCity($newEstablishment->getCity());
        $this->setPostalCode($newEstablishment->getPostalCode());
        $this->setWebsite($newEstablishment->getWebsite());
        $this->setType($newEstablishmentType);
        $this->setAcronym($newEstablishment->getAcronym());
        $this->setBrand($newEstablishment->getBrand());
        $this->setInstruction($newEstablishment->getInstruction());
        $this->setDepartment($department);
    }


}
