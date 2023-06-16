<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DataTypes
 *
 * Descripton des différentes variables saisies
 *
 * @ORM\Table(name="data_types",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="code", columns={"code"})},
 *     indexes={@ORM\Index(name="group_fk", columns={"group_fk"}),
 *              @ORM\Index(name="data_types_measure_unit_fk", columns={"measure_unit_fk"}),
 *              @ORM\Index(name="data_types_type_fk", columns={"type_fk"}),
 *              @ORM\Index(name="data_types_explanation", columns={"explanation_fk"}),
 *              @ORM\Index(name="data_types_name_fk", columns={"name_fk"}),
 *              @ORM\Index(name="data_types_instruction_fk", columns={"instruction_fk"}),
 *              @ORM\Index(name="data_types_instruction_fk", columns={"instruction_fk"}),
 *              @ORM\Index(name="data_types_date_fk", columns={"date_fk"})})
 * @ORM\Entity(repositoryClass="App\Repository\DataTypesRepository")
 */
class DataTypes
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
     * @ORM\Column(name="code", type="string", length=20, nullable=false)
     * @Assert\NotNull
     * @Assert\Type("string")
     * @Assert\Length(max=20)
     * @Assert\Regex("/[a-zA-Z0-9\_]+/")
     */
    private $code;

    /**
     * @var string|null
     *
     * @ORM\Column(name="code_eu", type="string", length=20, nullable=true)
     * @Assert\Type("string")
     * @Assert\Length(max=20)
     */
    private $codeEu;

    /**
     * @var Contents
     *
     * @ORM\ManyToOne(targetEntity="Contents")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="date_fk", referencedColumnName="id")
     * })
     * @JMS\Exclude
     */
    private $date;

    /**
     * @var int
     *
     * @ORM\Column(name="group_order", type="smallint", nullable=false)
     * @Assert\Type("integer")
     * @Assert\NotNull
     */
    private $groupOrder;

    /**
     * @var Contents
     *
     * @ORM\ManyToOne(targetEntity="Contents")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="definition_fk", referencedColumnName="id")
     * })
     * @JMS\Exclude
     */
    private $definition;

    /**
     * @var Groups
     *
     * @ORM\ManyToOne(targetEntity="Groups")
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
     * @var Contents
     *
     * @ORM\ManyToOne(targetEntity="Contents")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="measure_unit_fk", referencedColumnName="id")
     * })
     * @JMS\Exclude
     */
    private $measureUnit;

    /**
     * @var Contents
     *
     * @ORM\ManyToOne(targetEntity="Contents")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="name_fk", referencedColumnName="id")
     * })
     * @JMS\Exclude
     */
    private $name;

    /**
     * @var Types
     *
     * @ORM\ManyToOne(targetEntity="Types")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="type_fk", referencedColumnName="id")
     * })
     * @JMS\Exclude
     */
    private $type;

    /**
     * @var bool
     *
     * @ORM\Column(name="administrator", type="boolean", nullable=false)
     */
    private $administrator = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="private", type="boolean", nullable=false)
     */
    private $private = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="facet", type="boolean", nullable=false)
     */
    private $facet = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="simplified_facet", type="boolean", nullable=false)
     */
    private $simplifiedFacet = false;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCodeEu(): ?string
    {
        return $this->codeEu;
    }

    public function setCodeEu(?string $codeEu): self
    {
        $this->codeEu = $codeEu;

        return $this;
    }

    public function getDefinition(): ?Contents
    {
        return $this->definition;
    }

    public function setDefinition(?Contents $definition): self
    {
        $this->definition = $definition;

        return $this;
    }

    public function getGroup(): ?Groups
    {
        return $this->group;
    }

    public function setGroup(?Groups $group): self
    {
        $this->group = $group;

        return $this;
    }

    public function getInstruction(): ?Contents
    {
        return $this->instruction;
    }

    public function setInstruction(?Contents $instruction): self
    {
        $this->instruction = $instruction;

        return $this;
    }

    public function getMeasureUnit(): ?Contents
    {
        return $this->measureUnit;
    }

    public function setMeasureUnit(?Contents $measureUnit): self
    {
        $this->measureUnit = $measureUnit;

        return $this;
    }

    public function getName(): ?Contents
    {
        return $this->name;
    }

    public function setName(?Contents $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): ?Types
    {
        return $this->type;
    }

    public function setType(?Types $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Contents|null
     */
    public function getDate(): ?Contents
    {
        return $this->date;
    }

    /**
     * @param Contents|null $date
     */
    public function setDate(?Contents $date): void
    {
        $this->date = $date;
    }

    /**
     * @return int
     */
    public function getGroupOrder(): int
    {
        return $this->groupOrder;
    }

    /**
     * @param int $groupOrder
     */
    public function setGroupOrder(int $groupOrder): void
    {
        $this->groupOrder = $groupOrder;
    }

    /**
     * @return bool
     */
    public function getAdministrator(): bool
    {
        return $this->administrator;
    }

    /**
     * @return bool
     */
    public function getPrivate(): bool
    {
        return $this->private;
    }

    /**
     * @param bool $private
     */
    public function setPrivate(bool $private): void
    {
        $this->private = $private;
    }

    /**
     * @return bool
     */
    public function getFacet(): bool
    {
        return $this->facet;
    }

    /**
     * @param bool $facet
     */
    public function setFacet(bool $facet): void
    {
        $this->facet = $facet;
    }

    /**
     * @return bool
     */
    public function getSimplifiedFacet(): bool
    {
        return $this->simplifiedFacet;
    }

    /**
     * @param bool $simplifiedFacet
     */
    public function setSimplifiedFacet(bool $simplifiedFacet): void
    {
        $this->simplifiedFacet = $simplifiedFacet;
    }

    /**
     * @param bool $administrator
     */
    public function setAdministrator(bool $administrator): void
    {
        $this->administrator = $administrator;
    }

    public function update(DataTypes $dataTypes, Groups $group, Types $types): void
    {
        $this->setCode($dataTypes->getCode());
        $this->setCodeEu($dataTypes->getCodeEu());
        $this->setGroupOrder($dataTypes->getGroupOrder());
        $this->setAdministrator($dataTypes->getAdministrator());
        $this->setPrivate($dataTypes->getPrivate());
        $this->setFacet($dataTypes->getFacet());
        $this->setSimplifiedFacet($dataTypes->getSimplifiedFacet());
        $this->setGroup($group);
        $this->setType($types);
    }

}
