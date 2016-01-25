<?php
/**
 * Created by PhpStorm.
 * User: eka
 * Date: 25/01/16
 * Time: 11:02
 */

namespace app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Kalender extends model
{
    protected $table='kalender_akademik';
    protected $primaryKey = 'id';

    protected $fillable = [
        'semester',
        'start_period',
        'end_period'
    ];

    /**
     * Not yet check for bug most likely will error due to different format between PHP & MySql
     * @return mixed
     */
    public static function getRunningSemester(){
        //SELECT * FROM `kalender_akademik` WHERE curdate() between start_period and end_period
        $cur_date=date('Y-m-d');
        /*
        $instance = DB::table('kalender_akademik')
            ->select('id', 'start_period', 'end_period')
            ->whereBetween($cur_date,['start_period','end_period'])
            ->first();
        */
        $instance = DB::select('select * from kalender_akademik where ? BETWEEN start_period and end_period', [$cur_date])[0];

        return $instance;
    }
}