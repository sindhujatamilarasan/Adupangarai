<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;

abstract class Controller
{
    /** 404 (not 403) so other households' ids are not revealed. */
    protected function ensureOwned(Model $model): void
    {
        abort_unless((int) $model->household_id === (int) request()->user()->household_id, 404);
    }
}
