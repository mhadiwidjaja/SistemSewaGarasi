<?php

namespace App\Enums;

/**
 * Verification state of a renter's registration, decided by the admin.
 */
enum RegistrationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
