<?php

namespace App\Console\Commands;

use App\Attendance;
use App\AttendanceLog;
use Illuminate\Console\Command;

class wtcc_store_attendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        ini_set('memory_limit', '-1');

        info("START Get Attendance Store in WTCC");
        $attendance = Attendance::orderBy('last_id','desc')->first();
        // $location = env('LOCATION');
        if($attendance == null)
        {
            // $attendances = AttendanceLog::orderBy('id','asc')->where('location','!=','System')->get()->take(100);
            $attendances = AttendanceLog::orderBy('id','asc')->where('location','WTCC')->take(100)->get();
        }
        else
        {
            // $attendances = AttendanceLog::where('id','>',$attendance->last_id)->where('location','!=','System')->orderBy('id','asc')->get()->take(100);
            $attendances = AttendanceLog::where('id','>',$attendance->last_id)->where('location','WTCC')->orderBy('id','asc')->take(100)->get();
        }
        // info($attendances->toArray());
        // dd($attendances);
        foreach($attendances as $att)
        {
                if($att->type == 0)
                {
                    $time_in_after = date('Y-m-d H:i:s',strtotime($att->datetime));
                    $time_in_before = date('Y-m-d H:i:s', strtotime ( '+16 hour' , strtotime ( $time_in_after ) )) ;
                    $update = [
                        'time_in' =>  date('Y-m-d H:i:s', strtotime($att->datetime)),
                        'device_in' => $att->location ." - ".$att->ip_address,
                        'last_id' =>$att->id,
                    ];
                    Attendance::where('employee_code',$att->emp_code)
                    ->whereBetween('time_out',[$time_in_after,$time_in_before])
                    ->where(function ($query) use ($time_in_after) {
                        $query->where('time_in', '>=', $time_in_after)
                              ->orWhereNull('time_in');
                    })
                    ->update($update);
                    
                    $attend = Attendance::where('employee_code',$att->emp_code)->where('time_in',date('Y-m-d H:i:s', strtotime($att->datetime)))->first();
                   
                    $attendance = new Attendance;
                    $attendance->employee_code  = $att->emp_code;   
                    $attendance->time_in = date('Y-m-d H:i:s',strtotime($att->datetime));
                    $attendance->device_in = $att->location ." - ".$att->ip_address;
                    $attendance->last_id = $att->id;
                    $attendance->save();
                    


                }
                else
                {
                    $time_in_after = date('Y-m-d H:i:s',strtotime($att->datetime));
                    $time_in_before = date('Y-m-d H:i:s', strtotime ( '-16 hour' , strtotime ( $time_in_after ) )) ;
                    $update = [
                        'time_out' =>  date('Y-m-d H:i:s', strtotime($att->datetime)),
                        'device_out' => $att->location ." - ".$att->ip_address,
                        'last_id' =>$att->id,
                    ];
    
                    $attendance_in = Attendance::where('employee_code',$att->emp_code)
                    ->whereBetween('time_in',[$time_in_before,$time_in_after])->first();
    
                    Attendance::where('employee_code',$att->emp_code)
                    ->whereBetween('time_in',[$time_in_before,$time_in_after])
                    ->where(function ($query) use ($time_in_after) {
                        $query->where('time_out', '<=', $time_in_after)
                              ->orWhereNull('time_out');
                    })
                    ->update($update);
    
                    if($attendance_in ==  null)
                    {
                        $attendance = new Attendance;
                        $attendance->employee_code  = $att->emp_code;   
                        $attendance->time_out = date('Y-m-d H:i:s', strtotime($att->datetime));
                        $attendance->device_out = $att->location ." - ".$att->ip_address;
                        $attendance->last_id = $att->id;
                        $attendance->save(); 
                    }
    
                }
          
        }
        info("End Get Attendance Store in WTCC");
    }
}
