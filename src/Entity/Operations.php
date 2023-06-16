<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Operations
 *
 * Pour préciser les types de données "Opération"
 *
 * @ORM\Table(name="operations")
 * @ORM\Entity(repositoryClass="App\Repository\OperationsRepository")
 */
class Operations
{

    /**
     * @var string
     *
     * s'exprime avec les codes, sert à calculer des champs en fonction d'autres champs.
     *
     * @ORM\Column(name="formula", type="string", length=255, nullable=false)
     */
    private $formula;

    /**
     * @var DataTypes
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="DataTypes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="data_type_fk", referencedColumnName="id")
     * })
     * @JMS\Exclude
     */
    private $dataType;

    /**
     * Operations constructor.
     * @param string $formula
     * @param DataTypes $dataType
     */

    public function __construct(string $formula, DataTypes $dataType)
    {
        $this->formula = $formula;
        $this->dataType = $dataType;
    }

    public function getFormula(): ?string
    {
        return $this->formula;
    }

    public function setFormula(string $formula): self
    {
        $this->formula = $formula;

        return $this;
    }

    public function getDataType(): ?DataTypes
    {
        return $this->dataType;
    }

    public function setDataType(?DataTypes $dataType): self
    {
        $this->dataType = $dataType;

        return $this;
    }

    public function update(string $formula, DataTypes $dataType)
    {
        $this->setFormula($formula);
        $this->setDataType($dataType);
    }


}
