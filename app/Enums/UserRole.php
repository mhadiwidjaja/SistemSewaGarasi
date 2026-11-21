<?php

namespace App\Enums;

/**
 * The two user roles of the application.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Renter = 'renter';
}
