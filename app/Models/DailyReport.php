<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyReport extends Model
{
    protected $fillable = [
        'report_date',
        'total_orders',
        'total_sales',
    ];

    protected $casts = [
        'report_date' => 'date',
        'total_sales' => 'decimal:2',
    ];
}
