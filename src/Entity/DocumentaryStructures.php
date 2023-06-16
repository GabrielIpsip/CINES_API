<?php

namespace App\Entity;

use App\Entity\AbstractEntity\Administrations;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * DocumentaryStructures
 *
 * @ORM\Table(name="documentary_structures", indexes={
 *     @ORM\Index(name="documentary_structures_establishment_fk", columns={"establishment_fk"})})
 * @ORM\Entity(repositoryClass="App\Repository\DocumentaryStructuresRepository")
 */
class DocumentaryStructures extends Administrations
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
     * @ORM\Column(name="address", type="string", length=255, nullable=false)
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     * @Assert\Type("string")
     */
    protected $address;

    /**
     * @var string
     *
     * @ORM\Column(name="postal_code", type="string", length=5, nullable=false, options={"fixed"=true})
     * @Assert\NotBlank
     * @Assert\Regex("/\d{5}/")
     * @Assert\Length(min="5", max="5")
     */
    protected $postalCode;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=150, nullable=false)
     * @Assert\NotBlank
     * @Assert\Length(max=150)
     * @Assert\Type("string")
     */
    protected $city;

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
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     * @Assert\Type("bool")
     * @Assert\NotNull
     */
    protected $active;

    /**
     * @var string|null
     *
     * @ORM\Column(name="instruction", type="text", length=65535, nullable=true)
     * @Assert\Length(max=65535)
     * @Assert\Type("string")
     */
    protected $instruction;

    /**
     * @var Establishments
     *
     * @ORM\ManyToOne(targetEntity="Establishments")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="establishment_fk", referencedColumnName="id")
     * })
     * @JMS\Exclude
     */
    private $establishment;

    /**
     * @var Departments
     * @ORM\ManyToOne(targetEntity="Departments")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="department_fk", referencedColumnName="id")
     * })
     */
    private $department;

    /**
     * @return string
     */
    public function getAcronym(): ?string
    {
        return $this->acronym;
    }

    /**
     * @param string|null $acronym
     */
    public function setAcronym(?string $acronym): void
    {
        $this->acronym = $acronym;
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

    public function getEstablishment(): ?Establishments
    {
        return $this->establishment;
    }

    public function setEstablishment(?Establishments $establishment): self
    {
        $this->establishment = $establishment;

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

    public function update(DocumentaryStructures $newDocStruct, Establishments $newEstablishment,
                           Departments $department)
    {
        $this->setOfficialName($newDocStruct->getOfficialName());
        $this->setUseName($newDocStruct->getUseName());
        $this->setAcronym($newDocStruct->getAcronym());
        $this->setActive($newDocStruct->getActive());
        $this->setAddress($newDocStruct->getAddress());
        $this->setCity($newDocStruct->getCity());
        $this->setPostalCode($newDocStruct->getPostalCode());
        $this->setWebsite($newDocStruct->getWebsite());
        $this->setInstruction($newDocStruct->getInstruction());
        $this->setEstablishment($newEstablishment);
        $this->setDepartment($department);
    }

}
