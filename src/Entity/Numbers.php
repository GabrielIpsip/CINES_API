<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Numbers
 *
 * Sert à préciser des contraintes sur les types de données "nombre"
 *
 * @ORM\Table(name="numbers")
 * @ORM\Entity
 */
class Numbers
{
    /**
     *
     * La valeur du nombre de doit pas etre inférieur à ce qui est indiqué dans min
     * @var float|null
     *
     * @ORM\Column(name="min", type="float", precision=10, scale=0, nullable=true)
     */
    private $min;

    /**
     * @var float|null
     *
     * similaire, cf min
     *
     * @ORM\Column(name="max", type="float", precision=10, scale=0, nullable=true)
     */
    private $max;

    /**
     * @var bool
     *
     * par defaut c'est des entiers, si c'est à 1, il tu peux avoir une virgule.
     *
     * @ORM\Column(name="is_decimal", type="boolean", nullable=false)
     */
    private $isDecimal = '0';

    /**
     *
     * lors de la saisie dans enquete, si on saisie une valeur inférieur alors on a une alerte
     * @var float|null
     *
     * @ORM\Column(name="min_alert", type="float", precision=10, scale=0, nullable=true)
     */
    private $minAlert;

    /**
     * cf champ precedent
     * @var float|null
     *
     * @ORM\Column(name="max_alert", type="float", precision=10, scale=0, nullable=true)
     */
    private $maxAlert;

    /**
     *
     * Si par exemple, on a 10 000 etudiant une année et l'autre 10000000 alors on met une alerte.
     *
     * @var float|null
     *
     * @ORM\Column(name="evolution_min", type="float", precision=10, scale=0, nullable=true)
     * @Assert\Range(min=0)
     */
    private $evolutionMin;

    /**
     * @var float|null
     *
     * @ORM\Column(name="evolution_max", type="float", precision=10, scale=0, nullable=true)
     * @Assert\Range(min=0)
     */
    private $evolutionMax;

    /**
     * pour faire la relation avec le type de données
     *
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
     * @return float|null
     */
    public function getMin(): ?float
    {
        return $this->min;
    }

    /**
     * @param float|null $min
     */
    public function setMin(?float $min): void
    {
        $this->min = $min;
    }

    /**
     * @return float|null
     */
    public function getMax(): ?float
    {
        return $this->max;
    }

    /**
     * @param float|null $max
     */
    public function setMax(?float $max): void
    {
        $this->max = $max;
    }

    /**
     * @return bool
     */
    public function getIsDecimal(): bool
    {
        return $this->isDecimal;
    }

    /**
     * @param bool $isDecimal
     */
    public function setIsDecimal(bool $isDecimal): void
    {
        $this->isDecimal = $isDecimal;
    }

    /**
     * @return float|null
     */
    public function getMinAlert(): ?float
    {
        return $this->minAlert;
    }

    /**
     * @param float|null $minAlert
     */
    public function setMinAlert(?float $minAlert): void
    {
        $this->minAlert = $minAlert;
    }

    /**
     * @return float|null
     */
    public function getMaxAlert(): ?float
    {
        return $this->maxAlert;
    }

    /**
     * @param float|null $maxAlert
     */
    public function setMaxAlert(?float $maxAlert): void
    {
        $this->maxAlert = $maxAlert;
    }

    /**
     * @return float|null
     */
    public function getEvolutionMin(): ?float
    {
        return $this->evolutionMin;
    }

    /**
     * @param float|null $evolutionMin
     */
    public function setEvolutionMin(?float $evolutionMin): void
    {
        $this->evolutionMin = $evolutionMin;
    }

    /**
     * @return float|null
     */
    public function getEvolutionMax(): ?float
    {
        return $this->evolutionMax;
    }

    /**
     * @param float|null $evolutionMax
     */
    public function setEvolutionMax(?float $evolutionMax): void
    {
        $this->evolutionMax = $evolutionMax;
    }

    /**
     * @return DataTypes
     */
    public function getDataType(): DataTypes
    {
        return $this->dataType;
    }

    /**
     * @param DataTypes $dataType
     */
    public function setDataType(DataTypes $dataType): void
    {
        $this->dataType = $dataType;
    }

    public function update(Numbers $number, DataTypes $dataType): void
    {
        $this->setMin($number->getMin());
        $this->setMax($number->getMax());
        $this->setMinAlert($number->getMinAlert());
        $this->setMaxAlert($number->getMaxAlert());
        $this->setEvolutionMin($number->getEvolutionMin());
        $this->setEvolutionMax($number->getEvolutionMax());
        $this->setIsDecimal($number->getIsDecimal());
        $this->setDataType($dataType);
    }
}
