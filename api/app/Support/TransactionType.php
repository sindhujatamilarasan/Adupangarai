<?php

namespace App\Support;

enum TransactionType: string
{
    case PURCHASE = 'PURCHASE';
    case ADD = 'ADD';
    case COOKED = 'COOKED';
    case ADJUSTMENT = 'ADJUSTMENT';
    case EXPIRED = 'EXPIRED';
    case DISCARDED = 'DISCARDED';
}
