<?php

namespace App\Entity;

use App\Entity\AbstractEntity\AdministrationRelations;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * EstablishmentRelations
 *
 * @ORM\Table(name="establishment_relations",
 *     indexes={@ORM\Index(name="establishment_relations_origin_establishment_fk", columns={"origin_establishment_fk"}),
 *              @ORM\Index(name="type_fk", columns={"type_fk"}),
 *              @ORM\Index(name="establishment_relations_result_establishment_fk", columns={"result_establishment_fk"})})
 * @ORM\Entity
 */
class EstablishmentRelations extends AdministrationRelations
{

    /**
     * @var DateTime
     *
     * @ORM\Column(name="start_date", type="date", nullable=false)
     */
    private $startDate;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="end_date", type="date", nullable=true)
     */
    private $endDate;

    /**
     * @var Establishments
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="Establishments")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="origin_establishment_fk", referencedColumnName="id")
     * })
     */
    private $originEstablishment;

    /**
     * @var Establishments
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="Establishments")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="result_establishment_fk", referencedColumnName="id")
     * })
     */
    private $resultEstablishment;

    /**
     * @var RelationTypes
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="RelationTypes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="type_fk", referencedColumnName="id")
     * })
     */
    private $type;

    /**
     * @return DateTime
     */
    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    /**
     * @param DateTime $startDate
     */
    public function setStartDate(DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    /**
     * @return DateTime|null
     */
    public function getEndDate(): ?DateTime
    {
        return $this->endDate;
    }

    /**
     * @param DateTime|null $endDate
     */
    public function setEndDate(?DateTime $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function getOriginEstablishment(): ?Establishments
    {
        return $this->originEstablishment;
    }

    public function setOriginEstablishment(?Establishments $originEstablishment): self
    {
        $this->originEstablishment = $originEstablishment;

        return $this;
    }

    public function getResultEstablishment(): ?Establishments
    {
        return $this->resultEstablishment;
    }

    public function setResultEstablishment(?Establishments $resultEstablishment): self
    {
        $this->resultEstablishment = $resultEstablishment;

        return $this;
    }

    public function getType(): ?RelationTypes
    {
        return $this->type;
    }

    public function setType(?RelationTypes $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setOriginAdministration($originAdministration)
    {
        $this->setOriginEstablishment($originAdministration);
    }

    public function setResultAdministration($resultAdministration)
    {
        $this->setResultEstablishment($resultAdministration);
    }

}
