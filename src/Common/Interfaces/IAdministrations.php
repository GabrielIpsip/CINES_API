<?php

namespace App\Common\Interfaces;

use App\Entity\DocumentaryStructureDataValues;
use App\Entity\DocumentaryStructureGroupLocks;
use App\Entity\DocumentaryStructureRelations;
use App\Entity\DocumentaryStructures;
use App\Entity\EstablishmentDataValues;
use App\Entity\EstablishmentGroupLocks;
use App\Entity\EstablishmentRelations;
use App\Entity\Establishments;
use App\Entity\PhysicalLibraries;
use App\Entity\PhysicalLibraryDataValues;
use App\Entity\PhysicalLibraryGroupLocks;

interface IAdministrations
{
    public const ADMINISTRATION_DATA_VALUE_CLASS = array(
        Establishments::class => EstablishmentDataValues::class,
        DocumentaryStructures::class => DocumentaryStructureDataValues::class,
        PhysicalLibraries::class => PhysicalLibraryDataValues::class
    );

    public const ADMINISTRATION_GROUP_LOCK_CLASS = array(
        Establishments::class => EstablishmentGroupLocks::class,
        DocumentaryStructures::class => DocumentaryStructureGroupLocks::class,
        PhysicalLibraries::class => PhysicalLibraryGroupLocks::class
    );

    public const ADMINISTRATION_ID_NAME = array(
        Establishments::class => 'establishmentId',
        DocumentaryStructures::class => 'docStructId',
        PhysicalLibraries::class => 'physicLibId'
    );

    public const ADMINISTRATION_NAME = array(
        Establishments::class => 'establishment',
        DocumentaryStructures::class => 'documentary structure',
        PhysicalLibraries::class => 'physical library'
    );

    public const ADMINISTRATION_CAMEL_CASE = array(
        Establishments::class => 'establishment',
        DocumentaryStructures::class => 'documentaryStructure',
        PhysicalLibraries::class => 'physicalLibrary'
    );

    public const ADMINISTRATION_RELATION_CLASS = array(
        Establishments::class => EstablishmentRelations::class,
        DocumentaryStructures::class => DocumentaryStructureRelations::class
    );
}