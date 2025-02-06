<?php

namespace App\Enum;

enum SkillStatus: string
{
    case validé = 'validé';
    case enAttente = 'en attente';
    case refusé = 'refusé';
}
?>