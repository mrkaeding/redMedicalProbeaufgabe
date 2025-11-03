<?php

namespace App\Enums;

enum OrderType: string
{
    case CONNECTOR = 'connector';
    case VPN_CONNECTION = 'vpn_connection';
}
