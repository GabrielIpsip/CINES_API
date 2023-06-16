<?php

namespace App\Entity;

use App\Entity\AbstractEntity\Administrations;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * PhysicalLibraries
 *
 * @ORM\Table(name="physical_libraries", indexes={
 *     @ORM\Index(name="physical_libraries_documentaryStructure_fk", columns={"documentary_structure_fk"})})
 * @ORM\Entity(repositoryClass="App\Repository\PhysicalLibrariesRepository")
 */
class PhysicalLibraries extends Administrations
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
     * @ORM\Column(name="address", type="string", length=255, nullable=false)
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     * @Assert\Type("string")
     */
    protected $address;

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
     * @ORM\Column(name="postal_code", type="string", length=5, nullable=false, options={"fixed"=true})
     * @Assert\NotBlank
     * @Assert\Regex("/\d{5}/")
     * @Assert\Length(min="5", max="5")
     */
    protected $postalCode;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     * @Assert\Type("bool")
     * @Assert\NotNull
     */
    protected $active;

    /**
     * @var bool
     *
     * @ORM\Column(name="fictitious", type="boolean", nullable=false)
     * @Assert\Type("bool")
     * @Assert\NotNull
     */
    private $fictitious;

    /**
     * @var string|null
     *
     * @ORM\Column(name="instruction", type="text", length=65535, nullable=true)
     * @Assert\Length(max=65535)
     * @Assert\Type("string")
     */
    protected $instruction;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_order", type="smallint", nullable=false, options={"unsigned"=true})
     * @Assert\Type("integer")
     */
    private $sortOrder;

    /**
     * @var DocumentaryStructures
     *
     * @ORM\ManyToOne(targetEntity="DocumentaryStructures")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="documentary_structure_fk", referencedColumnName="id")
     * })
     * @JMS\Exclude
     */
    private $documentaryStructure;

    /**
     * @var Departments
     * @ORM\ManyToOne(targetEntity="Departments")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="department_fk", referencedColumnName="id")
     * })
     */
    private $department;

    /**
     * @return bool
     */
    public function getFictitious(): bool
    {
        return $this->fictitious;
    }

    /**
     * @param bool $fictitious
     */
    public function setFictitious(bool $fictitious): void
    {
        $this->fictitious = $fictitious;
    }

    /**
     * @return int
     */
    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    /**
     * @param int $sortOrder
     */
    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
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

    public function update(PhysicalLibraries $physicLib, DocumentaryStructures $docStruct,
                           Departments $department)
    {
        $this->setOfficialName($physicLib->getOfficialName());
        $this->setUseName($physicLib->getUseName());
        $this->setAddress($physicLib->getAddress());
        $this->setCity($physicLib->getCity());
        $this->setPostalCode($physicLib->getPostalCode());
        $this->setInstruction($physicLib->getInstruction());
        $this->setSortOrder($physicLib->getSortOrder());
        $this->setDocumentaryStructure($docStruct);
        $this->setActive($physicLib->getActive());
        $this->setFictitious($physicLib->getFictitious());
        $this->setDepartment($department);
    }

}
