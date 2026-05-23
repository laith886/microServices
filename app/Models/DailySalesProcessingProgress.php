<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailySalesProcessingProgress extends Model
{
    protected $table = 'daily_sales_processing_progresses';

    protected $fillable = [
        'report_date',
        'status',
        'last_processed_id',
        'total_orders',
        'total_sales',
        'processed_chunks',
        'chunk_size',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'report_date' => 'date',
        'total_sales' => 'decimal:2',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
