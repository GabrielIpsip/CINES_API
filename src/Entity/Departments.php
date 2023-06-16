<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Departments
 *
 * Départements français
 *
 * @ORM\Table(name="departments", indexes={@ORM\Index(name="departements_region_fk", columns={"region_fk"})})
 * @ORM\Entity
 */
class Departments
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
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=10, nullable=false)
     */
    private $code;

    /**
     * @var Regions
     *
     * @ORM\ManyToOne(targetEntity="Regions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="region_fk", referencedColumnName="id")
     * })
     */
    private $region;

    /**
     * Departments constructor.
     * @param string $name
     * @param string $code
     * @param Regions $region
     */
    public function __construct(string $name, string $code, Regions $region)
    {
        $this->name = $name;
        $this->code = $code;
        $this->region = $region;
    }

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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return Regions
     */
    public function getRegion(): Regions
    {
        return $this->region;
    }

}
