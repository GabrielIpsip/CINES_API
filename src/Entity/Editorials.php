<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Editorials
 *
 * @ORM\Table(name="editorials")
 * @ORM\Entity(repositoryClass="App\Repository\EditorialsRepository")
 */
class Editorials
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Column(name="content", type="text", length=0, nullable=true)
     */
    private $content;

    /**
     * @var Surveys
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="Surveys")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="survey_fk", referencedColumnName="id")
     * })
     */
    private $survey;

    /**
     * Editorials constructor.
     * @param string|null $title
     * @param string|null $content
     * @param Surveys $survey
     */
    public function __construct(?string $title, ?string $content, Surveys $survey)
    {
        $this->title = $title;
        $this->content = $content;
        $this->survey = $survey;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string|null $content
     */
    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    /**
     * @return Surveys
     */
    public function getSurvey(): Surveys
    {
        return $this->survey;
    }

    /**
     * @param Surveys $survey
     */
    public function setSurvey(Surveys $survey): void
    {
        $this->survey = $survey;
    }

}
