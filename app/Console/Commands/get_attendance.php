<?php

namespace App\Console\Commands;
use App\AttPunch;
use Rats\Zkteco\Lib\ZKTeco;
use Illuminate\Console\Command;

class get_attendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:get_attendance';

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
        //
        info("START Get Attendance");
        $location = config('app.location');
        $address = explode(',',config('app.address'));
        $name = config('app.name');
        foreach($address as $add)
        {
            $zk = new ZKTeco($add);
            $zk->connect();   
            $system = config('app.system');
            $attendances = collect($zk->getAttendance());
            $client = new \GuzzleHttp\Client();
            $request = $client->get($system."/get-last-id/".$add);
            
            $response = json_decode($request->getBody());
            info($response->toArray());
            if($response->id)
            {

                $attendances = $attendances->where('timestamp','>=',$response->id)->take(200);
            }
            else
            {
                $attendances = $attendances->where('timestamp','>=',date('Y-m-d 00:00:00',strtotime('2024-07-01')))->take(200);
            }
            info($attendances->toArray());
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
    
            $response = json_decode($apiRequest->getBody());
            $zk->disconnect();   
        }

        info("End Get Attendance");
        return "success";
    }
}
