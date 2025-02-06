<?php

namespace App\Enum;

enum RequestStatus: string
{
    case EnAttente = 'en attente';
    case Acceptee = 'acceptée';
    case Refusee = 'refusée';
}