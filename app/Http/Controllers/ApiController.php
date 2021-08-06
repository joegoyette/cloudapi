<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Log;

class ApiController extends Controller
{
    
/**
 *     Return recommended instance type and pricing details    
**/
    public function instancebestfit(Request $request, $region,$cpu, $memory)
    {
        $results = [
            "region" => $region,
            "cpu" => $cpu,
            "memory" => $memory,
            "recommended_instance_type" => 'm5.large',
            "on_demand_cost" => 0.096,
            "one_year_standard_ri_allupfront_cost" => 0.056,
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
