<?php

use Illuminate\Support\Facades\Route;
use Iquesters\Dev\Http\Controllers\TriggerVectorController
;

Route::middleware(['web','auth'])->group(function () {
    Route::prefix('vector')->name('vectors.')->group(function () {
        Route::get('/', [TriggerVectorController::class, 'index'])->name('index');
        Route::get('/responses', [TriggerVectorController::class, 'vectorResponses'])->name('responses.index');
        Route::post('/{integrationUid}/trigger', [TriggerVectorController::class, 'trigger'])->name('trigger');
    });
});