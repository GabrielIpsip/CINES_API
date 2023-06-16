<?php

namespace App\Entity;

use App\Utils\StringTools;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * UserValidations
 *
 * @ORM\Table(name="user_validations")
 * @ORM\Entity(repositoryClass="App\Repository\UserValidationsRepository")
 */
class UserValidations
{
    /**
     * @var string
     *
     * @ORM\Column(name="token", type="text", length=65535, nullable=false)
     */
    private $token;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="creation", type="datetime", nullable=false)
     */
    private $creation;

    /**
     * @var Users
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_fk", referencedColumnName="id")
     * })
     */
    private $user;

    /**
     * UserValidations constructor.
     * @param Users $user
     * @throws Exception
     */
    public function __construct(Users $user)
    {
        $this->token = StringTools::generateToken(50);

        $creation = new DateTime();
        $creation = $creation->setTimezone(new DateTimeZone("UTC"));
        $this->creation = $creation;

        $this->user = $user;
    }


    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return Users
     */
    public function getUser(): Users
    {
        return $this->user;
    }

    /**
     * @return DateTime
     */
    public function getCreation(): DateTime
    {
        return $this->creation;
    }

}
