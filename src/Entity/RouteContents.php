<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Routes;
use App\Entity\Languages;

/**
 * Routes
 *
 * @ORM\Table(name="route_contents")
 * @ORM\Entity(repositoryClass="App\Repository\RoutesRepository")
 */
class RouteContents
{
    /**
     * @var Routes
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Routes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="route_fk", referencedColumnName="id")
     * })
     */
    private $route;

    /**
     * @var Languages
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Languages")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="language_fk", referencedColumnName="id")
     * })
     */
    private $language;

    /**
     * @var string|null
     *
     * @ORM\Column(name="content", type="text", length=0, nullable=true)
     */
    private $content;

    /**
     * @return Routes
     */
    public function getRoute(): Routes
    {
        return $this->route;
    }

    /**
     * @param Routes $route
     */
    public function setRoute(Routes $route): void
    {
        $this->route = $route;
    }

    /**
     * @return Languages
     */
    public function getLanguage(): Languages
    {
        return $this->language;
    }

    /**
     * @param Languages $language
     */
    public function setLanguage(Languages $language): void
    {
        $this->language = $language;
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

}
