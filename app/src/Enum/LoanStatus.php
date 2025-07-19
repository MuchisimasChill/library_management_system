<?php

namespace App\Enum;

enum LoanStatus: string
{
    case LENT = 'lent';
    case RETURNED = 'returned';
    case OVERDUE = 'overdue';  
    case LOST = 'lost';        
}
