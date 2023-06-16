<?php

namespace App\Entity;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Surveys
 *
 *  * @ORM\Table(name="surveys",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="name", columns={"name"})},
 *     indexes={@ORM\Index(name="state_fk", columns={"state_fk"})})

 * @ORM\Entity(repositoryClass="App\Repository\SurveysRepository")
 */
class Surveys
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     * @Assert\NotBlank
     * @Assert\Length(max="100")
     */
    private $name;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="calendar_year", type="date", nullable=false)
     * @Assert\NotBlank
     * @JMS\Type("DateTime<'Y'>")
     */
    private $calendarYear;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="data_calendar_year", type="date", nullable=false)
     * @Assert\NotBlank
     * @JMS\Type("DateTime<'Y'>")
     */
    private $dataCalendarYear;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="creation", type="datetime", nullable=false)
     * @JMS\Type("DateTime<'Y-m-d H:i:s T'>")
     */
    private $creation;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="start", type="datetime", nullable=false)
     * @Assert\NotBlank
     * @JMS\Type("DateTime<'Y-m-d H:i:s T'>")
     * @JMS\Accessor(setter="setUTCStart")
     */
    private $start;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="end", type="datetime", nullable=false)
     * @Assert\NotBlank
     * @JMS\Type("DateTime<'Y-m-d H:i:s T'>")
     * @JMS\Accessor(setter="setUTCEnd")
     */
    private $end;


    /**
     * @var string|null
     *
     * @ORM\Column(name="instruction", type="text", length=65535, nullable=true)
     * @Assert\Type("string")
     * @Assert\Length(max="65535")
     */
    private $instruction;

    /**
     * @var States
     *
     * @ORM\ManyToOne(targetEntity="States")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="state_fk", referencedColumnName="id")
     * })
     */
    private $state;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCalendarYear(): ?DateTime
    {
        return $this->calendarYear;
    }

    public function setCalendarYear(DateTime $calendarYear): self
    {
        $this->calendarYear = $calendarYear;

        return $this;
    }

    public function getDataCalendarYear(): ?DateTime
    {
        return $this->dataCalendarYear;
    }

    public function setDataCalendarYear(DateTime $dataCalendarYear): self
    {
        $this->dataCalendarYear = $dataCalendarYear;

        return $this;
    }

    public function getCreation(): ?DateTime
    {
        return $this->creation;
    }

    public function setCreation(DateTime $creation): self
    {
        $this->creation = $creation;

        return $this;
    }

    public function getStart(): ?DateTime
    {
        return $this->start;
    }

    /**
     * Set start date. If start date aren't in UTC, value is converted in UTC.
     * @param DateTime $start Date with timezone info.
     * @return $this This survey
     */
    public function setUTCStart(DateTime $start): self
    {
        try {
            $utcStart = new DateTime($start->format('Y-m-d H:i:s T'));
            $utcStart->setTimezone(new DateTimeZone("UTC"));
            $this->start = $utcStart;
        } catch (Exception $e) {
            echo "Error to convert date start to UTC : ", $e->getMessage();
        }
        return $this;
    }

    public function getEnd(): ?DateTime
    {
        return $this->end;
    }

    /**
     * Set end date. If end date aren't in UTC, value is converted in UTC.
     * @param DateTime $end Date with timezone info.
     * @return $this This survey
     */
    public function setUTCEnd(DateTime $end): self
    {
        try {
            $utcEnd = new DateTime($end->format('Y-m-d H:i:s T'));
            $utcEnd->setTimezone(new DateTimeZone("UTC"));
            $this->end = $utcEnd;
        } catch (Exception $e) {
            echo "Error to convert date end to UTC : ", $e->getMessage();
        }
        return $this;
    }

    public function getState(): ?States
    {
        return $this->state;
    }

    public function setState(?States $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getInstruction(): ?string
    {
        return $this->instruction;
    }

    public function setInstruction(?string $instruction): void
    {
        $this->instruction = $instruction;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }



    public function update(Surveys $newSurvey, States $newState)
    {
        $this->setName($newSurvey->getName());
        $this->setCalendarYear($newSurvey->getCalendarYear());
        $this->setDataCalendarYear($newSurvey->getDataCalendarYear());
        $this->setUTCStart($newSurvey->getStart());
        $this->setUTCEnd($newSurvey->getEnd());
        $this->setState($newState);

        if ($newSurvey->getInstruction()) {
            $this->setInstruction($newSurvey->getInstruction());
        }
    }

}
