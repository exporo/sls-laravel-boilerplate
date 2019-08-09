<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CountDb extends Model
{
    protected $table = 'count';

    protected $fillable = [
        'counter'
    ];
}
