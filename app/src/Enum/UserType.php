<?php

namespace App\Enum;

enum UserType: string
{
    case LIBRARIAN = 'librarian';
    case MEMBER = 'member';
}