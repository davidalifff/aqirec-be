<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aqi extends Model
{
    use HasFactory;

    protected $table = 'aqi';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id_aqi_stations',
        'index_1',
        'index_2',
        't',
        'w',
        'ts',
        'created_at',
    ];

    public function getAvg() {
        $data = $this->query()
            ->selectRaw('aqi_stations.id AS ID, aqi_stations.nama AS Nama, aqi.index_1 AS "Index 1", aqi.index_2 AS "Index 2", (IFNULL(aqi.index_1, 0)+IFNULL(aqi.index_2, 0))/2 AS "Rata-rata"');
        $data->join('aqi_stations', 'aqi_stations.id', '=', 'aqi.id_aqi_stations');
        $data->orderBy('aqi_stations.id');

        return $data->get()->toArray();
    }

    public function getDataAqi(int $aqi_stations_id) {
        $data = $this->query()
            ->selectRaw('aqi.created_at date, index_1 pm25, index_2 pm10, t, w');
        $data->join('aqi_stations', 'aqi_stations.id', '=', 'aqi.id_aqi_stations');
        $data->where('aqi_stations.id', $aqi_stations_id);
        $data->orderBy('aqi.created_at');

        return $data->get()->toArray();
    }
}
