<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Transaction extends Model
{
    protected $fillable = [
        'transaction_id',
        'account_number',
        'transaction_date',
        'amount',
        'currency',
    ];
}
