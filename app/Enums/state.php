<?php

namespace App\Enums;

enum state:string
{
    case PENDING='pending';
    case COMPLETED='completed';

    case CANCELLED='cancelled';


}
