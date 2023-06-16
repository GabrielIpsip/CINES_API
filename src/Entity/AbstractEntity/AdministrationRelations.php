<?php


namespace App\Entity\AbstractEntity;

use App\Entity\RelationTypes;
use DateTime;

abstract class AdministrationRelations
{
    public abstract function setOriginAdministration($administration);
    public abstract function setResultAdministration($administration);
    public abstract function setStartDate(DateTime $startDate);
    public abstract function setEndDate(DateTime $endDate);
    public abstract function setType(RelationTypes $relationTypes);

}