<?php

namespace App\Enums;

enum TypeModality: string
{
    case IN_PERSON = 'in_person';
    case VIRTUAL = 'virtual';
    case BOTH = 'both';
}
