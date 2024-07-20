<?php

namespace App\Http\Controllers;

use App\Models\Aqi;
use App\Models\AqiStation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AqiController extends Controller
{
	public function exportAvg() {
		$aqi = new Aqi();
		$list = $aqi->getAvg();

		$headers = [
			'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
			'Content-type'        => 'text/csv',
			'Content-Disposition' => 'attachment; filename=export-avg-all.csv',
			'Expires'             => '0',
			'Pragma'              => 'public'
		];

		# add headers for each column in the CSV download
		array_unshift($list, array_keys($list[0]));

		$callback = function() use ($list) 
		{
			$FH = fopen('php://output', 'w');
			foreach ($list as $row) { 
				fputcsv($FH, $row, ";");
			}
			fclose($FH);
		};

		return response()->stream($callback, 200, $headers);
	}

	public function exportDataAqi($id) {
		$aqi = new Aqi();
		$list = $aqi->getDataAqi($id);


        $aqiStation = AqiStation::where('id', '=', $id)->first();

		$headers = [
			'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
			'Content-type'        => 'text/csv',
			'Content-Disposition' => 'attachment; filename='.$aqiStation->nama.'-air-quality.csv',
			'Expires'             => '0',
			'Pragma'              => 'public'
		];

		# add headers for each column in the CSV download
		array_unshift($list, array_keys($list[0]));

		$callback = function() use ($list) 
		{
			$FH = fopen('php://output', 'w');
			foreach ($list as $row) { 
				fputcsv($FH, $row, ";");
			}
			fclose($FH);
		};

		return response()->stream($callback, 200, $headers);
	}

	private function _getData(int $id = null)
	{

        $aqi_stations = AqiStation::get()->toArray();
        $additionalWhere = '';
        if ($id) {
        	$additionalWhere = 'AND id_aqi_stations = '.$id;
        }
        $aqi = DB::select(
        	'SELECT *, IFNULL(`index_1`, 0) AS index_1, IFNULL(`index_2`, 0) AS index_2
			FROM aqi WHERE (`id_aqi_stations`, `id`) IN 
			( SELECT id_aqi_stations, MAX(id)
			  FROM aqi
			  WHERE ((`index_1` IS NOT NULL) OR (`index_2` IS NOT NULL)) '.$additionalWhere.'
			  GROUP BY id_aqi_stations
			)'
		);

        $arrIndex = [];
        foreach ($aqi as $key => $value) {
            $arrIndex[$value->id_aqi_stations] = [
                'index_1' => $value->index_1,
                'index_2' => is_null($value->index_2) || $value->index_2 == '-'? 0 : $value->index_2,
                'index' => 0,
                'ts' => $value->ts,
                'id_aqi_stations' => $value->id_aqi_stations,
                't' => $value->t,
                'w' => $value->w,
                'aqicn' => $value->aqicn
            ];
        }
 		
 		/*
 		Perhitungan aqi
 		*/
        foreach ($aqi_stations as $key => $value) {
            if (isset($arrIndex[$value['id']])) {
                $aqi_stations[$key]['index_1'] = $arrIndex[$value['id']]['index_1'];
                $aqi_stations[$key]['index_2'] = $arrIndex[$value['id']]['index_2'];

                if ($aqi_stations[$key]['index_1'] > 0) {
                	$aqi_stations[$key]['aqi'] = round(0.516 * $aqi_stations[$key]['index_1'] + 151, 0);
                	$aqi_stations[$key]['dominentpol'] = 'pm25';
                } else {
                	$aqi_stations[$key]['aqi'] = round(0.9259 * $aqi_stations[$key]['index_2'], 0);
                	$aqi_stations[$key]['dominentpol'] = 'pm10';
                }
            	if ($aqi_stations[$key]['aqi'] < 51) {
            		$aqi_stations[$key]['group'] = 'healty';
            	} elseif ($aqi_stations[$key]['aqi'] > 50 && $aqi_stations[$key]['aqi'] < 151) {
            		$aqi_stations[$key]['group'] = 'moderate';
            	} else {
            		$aqi_stations[$key]['group'] = 'unhealthy';
            	}
                $aqi_stations[$key]['ts'] = $arrIndex[$value['id']]['ts'];
                if ($id) {
                	$aqi_stations[$key]['id_aqi_stations'] = $arrIndex[$value['id']]['id_aqi_stations'];
                	$aqi_stations[$key]['t'] = $arrIndex[$value['id']]['t'];
                	$aqi_stations[$key]['w'] = $arrIndex[$value['id']]['w'];
                	$aqi_stations[$key]['aqicn'] = $arrIndex[$value['id']]['aqicn'];
                	$aqi_stations[$key]['index'] = $aqi_stations[$key]['aqi'];
                }
            } else {
                unset($aqi_stations[$key]);
            }
        }

        usort($aqi_stations, function($a, $b) {
            return $a['aqi'] < $b['aqi'];
        });

        return $aqi_stations;
	}

	public function getOverall()
	{
		$data = $this->_getData();

		$return = [
			'healty' => 0,
			'moderate' => 0,
			'unhealthy' => 0,
		];

		foreach ($data as $key => $value) {
			if ($value['group'] == 'healty') {
				$return['healty']++;
			}
			if ($value['group'] == 'moderate') {
				$return['moderate']++;
			}
			if ($value['group'] == 'unhealthy') {
				$return['unhealthy']++;
			}
		}

        return response()->json(['message' => 'success', 'data' => $return], 200);
	}

	public function getOverallGroup($group)
	{
		$data = $this->_getData();

		$return = [];

		foreach ($data as $key => $value) {
			if ($value['group'] == $group) {
				$return[] = $value;
			}
		}

        return response()->json(['message' => 'success', 'data' => $return], 200);
	}

	public function getById($id)
	{
		$return = $this->_getData($id);
		return response()->json(['message' => 'success', 'data' => $return], 200);
	}

	public function getDetailDaily(int $id_aqi_stations, $date)
	{
		$aqi = DB::select('
			SELECT *
			FROM aqi
			WHERE ((`index_1` IS NOT NULL) OR (`index_2` IS NOT NULL)) AND id_aqi_stations = ?
			AND date(ts) = ?
		', [$id_aqi_stations, $date]);

		$return = [
			'healty' => 0,
			'moderate' => 0,
			'unhealty' => 0,
			'aqi' => [],
			'pm25' => [],
		];

		foreach ($aqi as $value) {
			if ($value->index_1 > 0) {
				$aqi = round(0.516 * $value->index_1 + 151, 0);
				$return['pm25'][] = [
					'ts' => $value->ts,
					'pm25' => $value->index_1
				];
			} else {
				$aqi = round(0.9259 * $value->index_2, 0);
			}
			if ($aqi < 51) {
				$return['healty']++;
			} elseif ($aqi > 50 && $aqi < 151) {
				$return['moderate']++;
			} else {
				$return['unhealty']++;
			}
			$return['aqi'][] = [
				'ts' => $value->ts,
				'aqi' => $aqi
			];
		}
		return response()->json(['message' => 'success', 'data' => $return], 200);
	}

	public function getDetailWeekly(int $id_aqi_stations)
	{
		#SELECT date(ts) ts, SUM(index_1)/COUNT(index_1) index_1, SUM(index_2)/COUNT(index_2) index_2
		$aqi = DB::select('
			SELECT *
			FROM aqi
			WHERE ((`index_1` IS NOT NULL) OR (`index_2` IS NOT NULL)) AND id_aqi_stations = ?
			AND ts >= DATE(NOW()) - INTERVAL 7 DAY;
		', [$id_aqi_stations]);

		$return = [
			'healty' => 0,
			'moderate' => 0,
			'unhealty' => 0,
			'aqi' => [],
			'pm25' => [],
		];

		foreach ($aqi as $value) {
			if ($value->index_1 > 0) {
				$aqi = round(0.516 * $value->index_1 + 151, 0);
				$return['pm25'][] = [
					'ts' => $value->ts,
					'pm25' => $value->index_1
				];
			} else {
				$aqi = round(0.9259 * $value->index_2, 0);
			}
			if ($aqi < 51) {
				$return['healty']++;
			} elseif ($aqi > 50 && $aqi < 151) {
				$return['moderate']++;
			} else {
				$return['unhealty']++;
			}
			$return['aqi'][] = [
				'ts' => $value->ts,
				'aqi' => $aqi
			];
		}
		return response()->json(['message' => 'success', 'data' => $return], 200);
	}

	public function getDetailMonthly(int $id_aqi_stations)
	{
		$aqi = DB::select('
			SELECT *
			FROM aqi
			WHERE ((`index_1` IS NOT NULL) OR (`index_2` IS NOT NULL)) AND id_aqi_stations = ?
			AND ts >= DATE(NOW()) - INTERVAL 1 MONTH;
		', [$id_aqi_stations]);

		$return = [
			'healty' => 0,
			'moderate' => 0,
			'unhealty' => 0,
			'aqi' => [],
			'pm25' => [],
		];

		foreach ($aqi as $value) {
			if ($value->index_1 > 0) {
				$aqi = round(0.516 * $value->index_1 + 151, 0);
				$return['pm25'][] = [
					'ts' => $value->ts,
					'pm25' => $value->index_1
				];
			} else {
				$aqi = round(0.9259 * $value->index_2, 0);
			}
			if ($aqi < 51) {
				$return['healty']++;
			} elseif ($aqi > 50 && $aqi < 151) {
				$return['moderate']++;
			} else {
				$return['unhealty']++;
			}
			$return['aqi'][] = [
				'ts' => $value->ts,
				'aqi' => $aqi
			];
		}
		return response()->json(['message' => 'success', 'data' => $return], 200);
	}

	public function getDetailYearly(int $id_aqi_stations)
	{
		$aqi = DB::select('
			SELECT *
			FROM aqi
			WHERE ((`index_1` IS NOT NULL) OR (`index_2` IS NOT NULL)) AND id_aqi_stations = ?
			AND ts >= DATE(NOW()) - INTERVAL 1 YEAR;
		', [$id_aqi_stations]);

		$return = [
			'healty' => 0,
			'moderate' => 0,
			'unhealty' => 0,
			'aqi' => [],
			'pm25' => [],
		];

		foreach ($aqi as $value) {
			if ($value->index_1 > 0) {
				$aqi = round(0.516 * $value->index_1 + 151, 0);
				$return['pm25'][] = [
					'ts' => $value->ts,
					'pm25' => $value->index_1
				];
			} else {
				$aqi = round(0.9259 * $value->index_2, 0);
			}
			if ($aqi < 51) {
				$return['healty']++;
			} elseif ($aqi > 50 && $aqi < 151) {
				$return['moderate']++;
			} else {
				$return['unhealty']++;
			}
			$return['aqi'][] = [
				'ts' => $value->ts,
				'aqi' => $aqi
			];
		}
		return response()->json(['message' => 'success', 'data' => $return], 200);
	}

	public function getDetailPm25Daily(int $id_aqi_stations, $date)
	{
		$aqi = DB::select('
			SELECT *
			FROM aqi
			WHERE `index_1` IS NOT NULL AND id_aqi_stations = ?
			AND date(ts) = ?
		', [$id_aqi_stations, $date]);

		if (count($aqi) == 0) {
			return response()->json(['message' => 'success', 'data' => null], 200);
		}

		$return = [
			'healty' => 0,
			'moderate' => 0,
			'unhealty' => 0,
			'pm25' => [],
		];

		foreach ($aqi as $value) {
			$return['pm25'][] = [
				'ts' => $value->ts,
				'pm25' => $value->index_1
			];
			if ($value->index_1 < 35.5) {
				$return['healty']++;
			} elseif ($value->index_1 > 35.4 && $value->index_1 < 55.5) {
				$return['moderate']++;
			} else {
				$return['unhealty']++;
			}
		}
		/*PM2.5 (μg/m3): 0-35.4 = Healthy
		PM2.5 (μg/m3): 35.5-55.4 = Moderate
		PM2.5 (μg/m3): 55.5-125.4 = Unhealthy*/
		return response()->json(['message' => 'success', 'data' => $return], 200);
	}

	public function getDetailPm25Weekly(int $id_aqi_stations)
	{
		$aqi = DB::select('
			SELECT *
			FROM aqi
			WHERE `index_1` IS NOT NULL AND id_aqi_stations = ?
			AND ts >= DATE(NOW()) - INTERVAL 7 DAY;
		', [$id_aqi_stations]);

		if (count($aqi) == 0) {
			return response()->json(['message' => 'success', 'data' => null], 200);
		}

		$return = [
			'healty' => 0,
			'moderate' => 0,
			'unhealty' => 0,
			'pm25' => [],
		];

		foreach ($aqi as $value) {
			$return['pm25'][] = [
				'ts' => $value->ts,
				'pm25' => $value->index_1
			];
			if ($value->index_1 < 35.5) {
				$return['healty']++;
			} elseif ($value->index_1 > 35.4 && $value->index_1 < 55.5) {
				$return['moderate']++;
			} else {
				$return['unhealty']++;
			}
		}
		return response()->json(['message' => 'success', 'data' => $return], 200);
	}

	public function getDetailPm25Monthly(int $id_aqi_stations)
	{
		$aqi = DB::select('
			SELECT *
			FROM aqi
			WHERE `index_1` IS NOT NULL AND id_aqi_stations = ?
			AND ts >= DATE(NOW()) - INTERVAL 1 MONTH;
		', [$id_aqi_stations]);

		if (count($aqi) == 0) {
			return response()->json(['message' => 'success', 'data' => null], 200);
		}

		$return = [
			'healty' => 0,
			'moderate' => 0,
			'unhealty' => 0,
			'pm25' => [],
		];

		foreach ($aqi as $value) {
			$return['pm25'][] = [
				'ts' => $value->ts,
				'pm25' => $value->index_1
			];
			if ($value->index_1 < 35.5) {
				$return['healty']++;
			} elseif ($value->index_1 > 35.4 && $value->index_1 < 55.5) {
				$return['moderate']++;
			} else {
				$return['unhealty']++;
			}
		}
		return response()->json(['message' => 'success', 'data' => $return], 200);
	}

	public function getDetailPm25Yearly(int $id_aqi_stations)
	{
		$aqi = DB::select('
			SELECT *
			FROM aqi
			WHERE `index_1` IS NOT NULL AND id_aqi_stations = ?
			AND ts >= DATE(NOW()) - INTERVAL 1 YEAR;
		', [$id_aqi_stations]);

		if (count($aqi) == 0) {
			return response()->json(['message' => 'success', 'data' => null], 200);
		}

		$return = [
			'healty' => 0,
			'moderate' => 0,
			'unhealty' => 0,
			'pm25' => [],
		];

		foreach ($aqi as $value) {
			$return['pm25'][] = [
				'ts' => $value->ts,
				'pm25' => $value->index_1
			];
			if ($value->index_1 < 35.5) {
				$return['healty']++;
			} elseif ($value->index_1 > 35.4 && $value->index_1 < 55.5) {
				$return['moderate']++;
			} else {
				$return['unhealty']++;
			}
		}
		return response()->json(['message' => 'success', 'data' => $return], 200);
	}
}
