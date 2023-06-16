<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Translations
 *
 * @ORM\Table(name="translations", indexes={@ORM\Index(name="translations_content_fk", columns={"content_fk"}), @ORM\Index(name="translations_language_fk", columns={"language_fk"})})
 * @ORM\Entity
 */
class Translations
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
     * @var string
     *
     * @ORM\Column(name="value", type="text", length=65535, nullable=false)
     */
    private $value;

    /**
     * @var Contents
     *
     * @ORM\ManyToOne(targetEntity="Contents")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="content_fk", referencedColumnName="id")
     * })
     */
    private $content;

    /**
     * @var Languages
     *
     * @ORM\ManyToOne(targetEntity="Languages")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="language_fk", referencedColumnName="id")
     * })
     */
    private $language;

    public function __construct(string $value, Contents $content, Languages $language) {
        $this->value = $value;
        $this->content = $content;
        $this->language = $language;
    }

    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getContent(): ?Contents
    {
        return $this->content;
    }

    public function setContent(?Contents $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getLanguage(): ?Languages
    {
        return $this->language;
    }

    public function setLanguage(?Languages $language): self
    {
        $this->language = $language;

        return $this;
    }


}
