<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
            "recommended_instance_type" => 'm4-large',
            "on_demand_cost" => 0.005,
            "3yr_standard_ri_allupfront_cost" => 0.001,
            "1yr_convertible_ri_no_upfront_cost" => .003,
        ];

        return json_encode($results);
    }

    /**
 *     Return recommended instance type and pricing details    
**/
public function ebscost(Request $request, $region)
{
    $results = [
        "region" => $region,
        "ebs_cost" => 0.005,
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
        "egress_cost" => 0.005,
    ];

    return json_encode($results);
}

}
