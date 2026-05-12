<?php
use App\Jobs\ProcessDailySales;

Route::get('/run-report', function () {

    ProcessDailySales::dispatch();

    return "Daily Report Job Sent!";
});
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
