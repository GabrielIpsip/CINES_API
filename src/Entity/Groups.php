<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Groups
 *
 * @ORM\Table(name="groups", indexes={
 *     @ORM\Index(name="parent_group_fk", columns={"parent_group_fk"}),
 *     @ORM\Index(name="groups_title_fk", columns={"title_fk"})})
 * @ORM\Entity
 */
class Groups
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
     * @var Groups
     *
     * @ORM\ManyToOne(targetEntity="Groups")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_group_fk", referencedColumnName="id")
     * })
     * @JMS\Exclude
     */
    private $parentGroup;

    /**
     * @var Contents
     *
     * @ORM\ManyToOne(targetEntity="Contents")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="title_fk", referencedColumnName="id")
     * })
     * @JMS\Exclude
     */
    private $title;

    /**
     * @var AdministrationTypes
     *
     * @ORM\ManyToOne(targetEntity="AdministrationTypes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="administration_type_fk", referencedColumnName="id")
     * })
     */
    private $administrationType;

    
    public function __construct(?Groups $parentGroup, Contents $title, AdministrationTypes $administrationType) {
        $this->parentGroup = $parentGroup;
        $this->title = $title;
        $this->administrationType = $administrationType;
    }

        public function getId(): ?int
    {
        return $this->id;
    }

    public function getParentGroup(): ?self
    {
        return $this->parentGroup;
    }

    public function setParentGroup(?self $parentGroup): self
    {
        $this->parentGroup = $parentGroup;

        return $this;
    }

    public function getTitle(): ?Contents
    {
        return $this->title;
    }

    public function setTitle(?Contents $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return AdministrationTypes
     */
    public function getAdministrationType(): AdministrationTypes
    {
        return $this->administrationType;
    }

    /**
     * @param AdministrationTypes $administrationType
     */
    public function setAdministrationType(AdministrationTypes $administrationType): void
    {
        $this->administrationType = $administrationType;
    }



}
