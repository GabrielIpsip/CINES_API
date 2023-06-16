<?php

namespace App\Common\Interfaces;

use App\Entity\Numbers;
use App\Entity\Operations;
use App\Entity\Texts;

interface ITypes
{
    public const NUMBER = 'number';
    public const TEXT = 'text';
    public const OPERATION = 'operation';
    public const BOOLEAN = 'boolean';

    public const TYPE_CLASS = array(
        self::NUMBER => array('class' => Numbers::class, 'instance' => null),
        self::TEXT => array('class' => Texts::class, 'instance' => null),
        self::OPERATION => array('class' => Operations::class, 'instance' => null),
        self::BOOLEAN => array('class' => null, 'instance' => null));
}