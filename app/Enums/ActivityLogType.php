<?php

namespace App\Enums;

enum ActivityLogType: string
{
    case Info = 'info';
    case Error = 'error';
    case Warning = 'warning';
    case Success = 'success';
}
