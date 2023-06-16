<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Indicators
 *
 *
 *  pour afficher dans l'onglet indicateur
 *
 * @ORM\Table(name="indicators", indexes={@ORM\Index(name="indicators_name_fk", columns={"name_fk"})})
 * @ORM\Entity
 */
class Indicators
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
     * @var string|null
     *
     * Requete ElasticSearch
     *
     * @ORM\Column(name="query", type="text", length=16777215, nullable=true)
     * @JMS\Accessor(setter="setQuery")
     * @JMS\Type("array")
     *
     */
    private $query;

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
     * @var Contents
     *
     * @ORM\ManyToOne(targetEntity="Contents")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="description_fk", referencedColumnName="id")
     * })
     * @JMS\Exclude
     */
    private $description;

    /**
     * @var bool
     *
     * @ORM\Column(name="by_establishment", type="boolean", nullable=false)
     */
    private $byEstablishment;

    /**
     * @var bool
     *
     * @ORM\Column(name="by_doc_struct", type="boolean", nullable=false)
     */
    private $byDocStruct;

    /**
     * @var bool
     *
     * @ORM\Column(name="by_region", type="boolean", nullable=false)
     */
    private $byRegion;

    /**
     * @var bool
     *
     * @ORM\Column(name="global", type="boolean", nullable=false)
     */
    private $global;

    /**
     * @var bool
     *
     * @ORM\Column(name="key_figure", type="boolean", nullable=false)
     */
    private $keyFigure;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active;

    /**
     * @var int
     *
     * @ORM\Column(name="display_order", type="smallint", nullable=false, options={"unsigned"=true})
     */
    private $displayOrder;

    /**
     * @var bool
     *
     * @ORM\Column(name="administrator", type="boolean", nullable=false)
     */
    private $administrator;

    /**
     * @var string|null
     *
     * @ORM\Column(name="prefix", type="string", length=10, nullable=true)
     */
    private $prefix;

    /**
     * @var string|null
     *
     * @ORM\Column(name="suffix", type="string", length=10, nullable=true)
     */
    private $suffix;

    public function update(Indicators $indicator)
    {
        $this->setQuery($indicator->getQuery());
        $this->byEstablishment = $indicator->getByEstablishment();
        $this->byDocStruct = $indicator->getByDocStruct();
        $this->byRegion = $indicator->getByRegion();
        $this->byRegion = $indicator->getByRegion();
        $this->global = $indicator->getGlobal();
        $this->keyFigure = $indicator->getKeyFigure();
        $this->active = $indicator->getActive();
        $this->displayOrder = $indicator->getDisplayOrder();
        $this->administrator = $indicator->getAdministrator();
        $this->prefix = $indicator->getPrefix();
        $this->suffix = $indicator->getSuffix();
    }

    /**
     * @param Contents $name
     */
    public function setName(Contents $name): void
    {
        $this->name = $name;
    }

    /**
     * @param Contents|null $description
     */
    public function setDescription(?Contents $description): void
    {
        $this->description = $description;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array|null
     */
    public function getQuery(): ?array
    {
        return json_decode($this->query, true);
    }

    /**
     * @param array|null $query
     */
    public function setQuery(?array $query)
    {
        $this->query = json_encode($query);
    }

    /**
     * @return Contents
     */
    public function getName(): Contents
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function getByEstablishment(): bool
    {
        return $this->byEstablishment;
    }

    /**
     * @return Contents
     */
    public function getDescription(): ?Contents
    {
        return $this->description;
    }

    /**
     * @return bool
     */
    public function getByDocStruct(): bool
    {
        return $this->byDocStruct;
    }

    /**
     * @return bool
     */
    public function getByRegion(): bool
    {
        return $this->byRegion;
    }

    /**
     * @return bool
     */
    public function getKeyFigure(): bool
    {
        return $this->keyFigure;
    }

    /**
     * @return bool
     */
    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * @return int
     */
    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    /**
     * @param int $displayOrder
     */
    public function setDisplayOrder(int $displayOrder): void
    {
        $this->displayOrder = $displayOrder;
    }

    /**
     * @return bool
     */
    public function getGlobal(): bool
    {
        return $this->global;
    }

    /**
     * @param bool $global
     */
    public function setGlobal(bool $global): void
    {
        $this->global = $global;
    }

    /**
     * @return bool
     */
    public function getAdministrator(): bool
    {
        return $this->administrator;
    }

    /**
     * @param bool $administrator
     */
    public function setAdministrator(bool $administrator): void
    {
        $this->administrator = $administrator;
    }

    /**
     * @return string
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix(?string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * @return string
     */
    public function getSuffix(): ?string
    {
        return $this->suffix;
    }

    /**
     * @param string|null $suffix
     */
    public function setSuffix(?string $suffix): void
    {
        $this->suffix = $suffix;
    }

}
