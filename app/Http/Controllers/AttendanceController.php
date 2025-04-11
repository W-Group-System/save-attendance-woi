<?php

namespace App\Http\Controllers;
use App\AttPunch;
use App\AttendanceLog;
use App\Attendance;
use Illuminate\Http\Request;
use App\Vms;

use Rats\Zkteco\Lib\ZKTeco;
class AttendanceController extends Controller
{
    //
    public function index()
    {
        $location = config('app.location');
        $address = explode(',',config('app.address'));
        $name = config('app.name');
        foreach($address as $add)
        {
            $system = config('app.system');
            $client = new \GuzzleHttp\Client();
            $request = $client->get($system."/get-last-id-hk/".$add);
            
            $response = json_decode($request->getBody());
            if($response->id)
            {

                $attendances = Vms::where('time_input','!=',"00:00:00")->where('id','>=',$response->id)->orderBy('id','asc')->get();
            }
            else
            {
                $attendances = Vms::where('time_input','!=',"00:00:00")->where('date_time','>=',date('Y-m-d 00:00:00',strtotime('2024-02-15')))->orderBy('id','asc')->get();
            }
            // dd($response->id);
            $requestContent = [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'json' => [ 
                    'data' => $attendances->toArray(),
                    'location' => $name,
                    'ip_address' => $add
                ]
            ];
            $client = new \GuzzleHttp\Client();
    
            $apiRequest = $client->request('POST', $system."/save-attendance-hk", $requestContent);
    
            $response = json_decode($apiRequest->getBody());
        }

    }
    public function dept()
    {
        info("START Get Attendance");
        $location = config('app.location');
        $address = explode(',',config('app.address'));
        $name = config('app.name');
        foreach($address as $add)
        {
            $zk = new ZKTeco($add);
            $zk->connect();   
            $system = config('app.system');
            $client = new \GuzzleHttp\Client();
            $request = $client->get($system."/get-last-id/".$add);
            
            $response = json_decode($request->getBody());
            // dd($response);
            if($response->id != 0)
            {

                $attendances = collect($zk->getAttendance())->where('timestamp','>=',$response->id)->take(500);
            }
            else
            {
                $attendances = collect($zk->getAttendance())->where('timestamp','>=',date('Y-m-d 00:00:00',strtotime('2024-07-26')));
            }
            $requestContent = [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'json' => [ 
                    'data' => $attendances->toArray(),
                    'location' => $name,
                    'ip_address' => $add
                ]
            ];
            $client = new \GuzzleHttp\Client();
    
            $apiRequest = $client->request('POST', $system."/save-attendance", $requestContent);
            $zk->disconnect();   
        }
        
        info("End Get Attendance");

    }
    public function get_users()
    {
        $location = config('app.location');
        $address = explode(',',config('app.address'));
        $name = config('app.name');
        foreach($address as $add)
        {
            $zk = new ZKTeco($add);
            $zk->connect();
            $users =  $zk->getUser();
            return json_encode($users);
            dd( $zk->getUser());
        }

    }
    public function store_attendance(Request $request)
    {
        ini_set('memory_limit', '-1');
    //    dd($request->all());
        $attendance = Attendance::orderBy('last_id','desc')->first();
        if($request->date_from)
        {
            $attendances = AttendanceLog::whereBetween('date',[$request->date_from,$request->date_to])->orderBy('datetime','asc')->get();
            // dd($request->all());
        }
        else
        {

     
            if($attendance == null)
            {
                $attendances = AttendanceLog::orderBy('id','asc')->get()->take(300);
            }
            else
            {
                $attendances = AttendanceLog::where('id','>',$attendance->last_id)->orderBy('id','asc')->get();
            }
        }   
        // dd($attendances->count());
        foreach($attendances as $att)
        {
                if($att->type == 0)
                {
                    $time_in_after = date('Y-m-d H:i:s',strtotime($att->datetime));
                    $time_in_before = date('Y-m-d H:i:s', strtotime ( '+16 hour' , strtotime ( $time_in_after ) )) ;
                    $update = [
                        'time_in' =>  date('Y-m-d H:i:s', strtotime($att->datetime)),
                        'device_in' => $att->location ." - ".$att->ip_address,
                        // 'last_id' =>$att->id,
                    ];
                    Attendance::where('employee_code',$att->emp_code)
                    ->whereBetween('time_out',[$time_in_after,$time_in_before])
                    ->where(function ($query) use ($time_in_after) {
                        $query->where('time_in', '>=', $time_in_after)
                              ->orWhereNull('time_in');
                    })
                    ->update($update);
                    $attend = Attendance::where('employee_code',$att->emp_code)->where('time_in',date('Y-m-d H:i:s', strtotime($att->datetime)))->first();
                    if($attend == null)
                    {
                        $attendance = new Attendance;
                        $attendance->employee_code  = $att->emp_code;   
                        $attendance->time_in = date('Y-m-d H:i:s',strtotime($att->datetime));
                        $attendance->device_in = $att->location ." - ".$att->ip_address;
                        // $attendance->last_id = $att->id;
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
                        // 'last_id' =>$att->id,
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
                        // $attendance->last_id = $att->id;
                        $attendance->save(); 
                    }
    
                }
          
        }
    }
    public function lastId()
    {
        $visitor = Visitor::orderBy('id','desc')->first();

        return $visitor->id;
    }
}
