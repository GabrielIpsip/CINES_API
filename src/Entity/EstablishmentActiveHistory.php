<?php

namespace App\Entity;

use App\Entity\AbstractEntity\Administrations;
use App\Entity\AbstractEntity\AdministrationActiveHistory;
use Doctrine\ORM\Mapping as ORM;

/**
 * EstablishmentActiveHistory
 *
 * @ORM\Table(name="establishment_active_history", indexes={
 *     @ORM\Index(name="establishment_link_history_survey_fk", columns={"survey_fk"}),
 *     @ORM\Index(name="establishment_link_history_establishment_fk", columns={"establishment_fk"})})
 * @ORM\Entity
 */
class EstablishmentActiveHistory extends AdministrationActiveHistory
{
    /**
     * @var Establishments
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="Establishments")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="establishment_fk", referencedColumnName="id")
     * })
     */
    private $establishment;

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
    protected $survey;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    protected $active;

    /**
     * EstablishmentActiveHistory constructor.
     * @param Establishments $establishment
     * @param Surveys $survey
     * @param bool $active
     */
    public function __construct(Establishments $establishment, Surveys $survey, bool $active)
    {
        parent::__construct($survey, $active);
        $this->establishment = $establishment;
    }

    public function getAdministration(): Administrations
    {
        return $this->establishment;
    }

}
