<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Contents
 *
 * @ORM\Table(name="contents", indexes={@ORM\Index(name="contents_type_fk", columns={"type_fk"})})
 * @ORM\Entity
 */
class Contents
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
     * @var ContentTypes
     *
     * @ORM\ManyToOne(targetEntity="ContentTypes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="type_fk", referencedColumnName="id")
     * })
     */
    private $type;

    public function __construct(ContentTypes $type) {
        $this->type = $type;
    }

    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?ContentTypes
    {
        return $this->type;
    }

    public function setType(?ContentTypes $type): self
    {
        $this->type = $type;

        return $this;
    }


}
