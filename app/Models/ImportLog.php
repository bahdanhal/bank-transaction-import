<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ImportLog extends Model
{
    protected $fillable = [
        'import_id',
        'transaction_id',
        'error_message',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
