<?php

namespace App\Console\Commands;
use App\Attendance;
use App\AttendanceLog;
use Illuminate\Console\Command;

class send_attendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:send_attendance';

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
        //
        info("START Get Attendance Store");
        $attendance = Attendance::orderBy('last_id','desc')->first();

        if($attendance == null)
        {
            $attendances = AttendanceLog::orderBy('id','asc')->get()->take(50);
        }
        else
        {
            $attendances = AttendanceLog::where('id','>',$attendance->last_id)->orderBy('id','asc')->get()->take(50);
        }
        foreach($attendances as $att)
        {
                if($att->type == 0)
                {
                    $attend = Attendance::where('employee_code',$att->emp_code)->where('time_in',date('Y-m-d H:i:s', strtotime($att->datetime)))->first();
                    if($attend == null)
                    {
                        $attendance = new Attendance;
                        $attendance->employee_code  = $att->emp_code;   
                        $attendance->time_in = date('Y-m-d H:i:s',strtotime($att->datetime));
                        $attendance->device_in = $att->location ." - ".$att->ip_address;
                        $attendance->last_id = $att->id;
                        $attendance->save();
                    }
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
                    ->where('time_out','<=',$time_in_after)
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
        info("End Get Attendance Store");
    }
}
