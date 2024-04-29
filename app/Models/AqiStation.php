<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AqiStation extends Model
{
    use HasFactory;

    protected $table = 'aqi_stations';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'nama',
        'lat',
        'long',
        'quality'
    ];
}
