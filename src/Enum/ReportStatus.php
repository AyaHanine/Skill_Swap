<?php

namespace App\Enum;

enum ReportStatus: string
{
    case enCours = 'en cours d\'examination';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}