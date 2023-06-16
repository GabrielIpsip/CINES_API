<?php

namespace App\Entity;

use App\Entity\AbstractEntity\AdministrationRelations;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * DocumentaryStructureRelations
 *
 * @ORM\Table(name="documentary_structure_relations",
 *     indexes={
 *     @ORM\Index(name="origin_documentary_structure_fk", columns={"origin_documentary_structure_fk"}),
 *     @ORM\Index(name="type_fk", columns={"type_fk"}),
 *     @ORM\Index(name="result_documentary_structure_fk", columns={"result_documentary_structure_fk"})})
 * @ORM\Entity
 */
class DocumentaryStructureRelations extends AdministrationRelations
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
     * @var DocumentaryStructures
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="DocumentaryStructures")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="origin_documentary_structure_fk", referencedColumnName="id")
     * })
     */
    private $originDocumentaryStructure;

    /**
     * @var DocumentaryStructures
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="DocumentaryStructures")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="result_documentary_structure_fk", referencedColumnName="id")
     * })
     */
    private $resultDocumentaryStructure;

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

    /**
     * @return DocumentaryStructures
     */
    public function getOriginDocumentaryStructure(): DocumentaryStructures
    {
        return $this->originDocumentaryStructure;
    }

    /**
     * @param DocumentaryStructures $originDocumentaryStructure
     */
    public function setOriginDocumentaryStructure(DocumentaryStructures $originDocumentaryStructure): void
    {
        $this->originDocumentaryStructure = $originDocumentaryStructure;
    }

    /**
     * @return DocumentaryStructures
     */
    public function getResultDocumentaryStructure(): DocumentaryStructures
    {
        return $this->resultDocumentaryStructure;
    }

    /**
     * @param DocumentaryStructures $resultDocumentaryStructure
     */
    public function setResultDocumentaryStructure(DocumentaryStructures $resultDocumentaryStructure): void
    {
        $this->resultDocumentaryStructure = $resultDocumentaryStructure;
    }

    /**
     * @return RelationTypes
     */
    public function getType(): RelationTypes
    {
        return $this->type;
    }

    /**
     * @param RelationTypes $type
     */
    public function setType(RelationTypes $type): void
    {
        $this->type = $type;
    }


    public function setOriginAdministration($administration)
    {
        $this->setOriginDocumentaryStructure($administration);
    }

    public function setResultAdministration($administration)
    {
        $this->setResultDocumentaryStructure($administration);
    }
}
