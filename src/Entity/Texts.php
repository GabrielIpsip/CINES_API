<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Texts
 * Regle supplémentaire (specifique CINES ! ) sur le type text.
 *
 * @ORM\Table(name="texts")
 * @ORM\Entity
 */
class Texts
{
    /**
     * @var bool|null
     *
     * @ORM\Column(name="max_length", type="smallint", nullable=true, options={"unsigned"=true})
     * @Assert\Range(min=0, max=65535)
     */
    private $maxLength;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="min_length", type="smallint", nullable=true, options={"unsigned"=true})
     * @Assert\Range(min=0, max=65535)
     */
    private $minLength;

    /**
     * @var string|null
     *
     * @ORM\Column(name="regex", type="text", length=65535, nullable=true)
     * @Assert\Length(max=65535)
     */
    private $regex;

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

    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    public function setMaxLength(?int $maxLength): self
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    public function getMinLength(): ?int
    {
        return $this->minLength;
    }

    public function setMinLength(?int $minLength): self
    {
        $this->minLength = $minLength;

        return $this;
    }

    public function getRegex(): ?string
    {
        return $this->regex;
    }

    public function setRegex(?string $regex): self
    {
        $this->regex = $regex;

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

    public function update(Texts $text, DataTypes $dataType)
    {
        $this->setMinLength($text->getMinLength());
        $this->setMaxLength($text->getMaxLength());
        $this->setRegex($text->getRegex());
        $this->setDataType($dataType);
    }
}
