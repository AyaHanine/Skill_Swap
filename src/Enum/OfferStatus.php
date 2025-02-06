<?php

namespace App\Enum;

enum OfferStatus: string
{
    case Disponible = 'disponible';
    case Reserve = 'reservé';
    case Banni = 'banni';
    case Termine = 'terminé';
    case Annule = 'annulé';
}