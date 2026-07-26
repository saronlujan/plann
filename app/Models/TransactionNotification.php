<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ledger of due/upcoming reminders already delivered, used to dedupe the daily
 * dispatch command. Not tenant-scoped: it is only touched from the console.
 */
class TransactionNotification extends Model
{
    protected $fillable = [
        'user_id',
        'entry_key',
        'kind',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];
}
