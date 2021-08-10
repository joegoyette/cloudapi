<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Log;
use App\Models\Price;

class ApiController extends Controller
{
    
/**
 *     Return recommended instance type and pricing details    
**/
    public function instancebestfit(Request $request, $region,$cpu, $memory)
    {

        $instances = '{
            "m5.large": {
                "cpu": 2,
                "memory": 8
            },
            "m5.xlarge": {
                "cpu": 4,
                "memory": 16
            },
            "m5.2xlarge": {
                "cpu": 8,
                "memory": 32
            },
            "m5.4xlarge": {
                "cpu": 16,
                "memory": 64
            },
            "m5.8xlarge": {
                "cpu": 32,
                "memory": 128
            },
            "m5.12xlarge": {
                "cpu": 48,
                "memory": 192
            },
            "m5.16xlarge": {
                "cpu": 64,
                "memory": 256
            },
            "m5.24xlarge": {
                "cpu": 96,
                "memory": 384
            }
        }';

        $memory_sizes = [8,16,32,64,128,192,256,384];

        if (in_array($memory,$memory_sizes)) {
            $bestfitmemory = $memory;
        } else {
            // Round up to next highest value in array
            foreach ($memory_sizes as $size) {
                if ($memory < min($memory_sizes) )
                {
                    $bestfitmemory = 8;
                    break;
                }
                if ($memory > max($memory_sizes) )
                {
                    $bestfitmemory = 384;
                    break;
                }

                if ($memory > $size ) {    
                    continue;
                } else {
                    $bestfitmemory = $size;
                    break;
                }
            }

        }

        foreach (json_decode($instances,true) as $key => $value) {
            if ($bestfitmemory == $value["memory"]) {
                $recommended_instance_type = $key;
            }
         }


         // Get the current pricing from the local database
        $price = Price::where('region', $region)->where('instanceType', $recommended_instance_type)->first();


        $results = [
            "region" => $region,
            "cpu" => $cpu,
            "memory" => $memory,
            "service" => $price->service,
            "recommended_instance_type" => $price->instanceType,
            "service_description" => $price->description,
            "term_type" => $price->termType,
            "on_demand_cost" => $price->pricePerUnit,
            "currency" => "USD",
            "updated_at" => $price->updated_at,
        ];

        Log::info("API Request Receive");

        return json_encode($results);
    }


    /**
    *     Return recommended instance type and pricing details    
    **/
    public function ebscost(Request $request, $region)
    {
        $results = [
            "region" => $region,
            "ebs_cost" => 0.12,
        ];

        return json_encode($results);
    }

    /**
     *     Return recommended instance type and pricing details    
    **/
    public function egresscost(Request $request, $region)
    {
        $results = [
            "region" => $region,
            "egress_cost" => 0.042,
        ];

        return json_encode($results);
    }

}
