<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public pages the app stores link to.
$legal = ['updated' => '30 September 2026', 'contact' => config('app.contact_email'), 'developer' => config('app.developer_name')];
Route::view('/privacy', 'legal.privacy', $legal);
Route::view('/delete-account', 'legal.delete-account', $legal);
