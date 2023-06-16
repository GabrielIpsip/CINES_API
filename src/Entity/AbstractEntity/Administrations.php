<?php


namespace App\Entity\AbstractEntity;

abstract class Administrations
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $officialName;

    /**
     * @var string
     */
    protected $useName;

    /**
     * @var string
     */
    protected $address;

    /**
     * @var string
     */
    protected $postalCode;

    /**
     * @var string
     */
    protected $city;

    /**
     * @var bool
     */
    protected $active;

    /**
     * @var string
     */
    protected $instruction;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOfficialName(): ?string
    {
        return $this->officialName;
    }

    public function setOfficialName(string $officialName): self
    {
        $this->officialName = $officialName;

        return $this;
    }

    public function getUseName(): ?string
    {
        return $this->useName;
    }

    public function setUseName(string $useName): self
    {
        $this->useName = $useName;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getInstruction(): ?string
    {
        return $this->instruction;
    }

    /**
     * @param string|null $instruction
     */
    public function setInstruction(?string $instruction): void
    {
        $this->instruction = $instruction;
    }
}