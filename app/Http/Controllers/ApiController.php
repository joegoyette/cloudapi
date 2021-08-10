<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Log;
use AWS;
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
    *     Return recommended instance type and pricing detail    
    **/
    public function ebscost(Request $request, $region)
    {
        $awsregions = [
            "us-east-2"         => "US East (Ohio)",
            "us-east-1"         => "US East (N. Virginia)",
            "us-west-1"         => "US West (N. California)",
            "us-west-2"         => "US West (Oregon)",
            "af-south-1"        => "Africa (Cape Town)",
            "ap-east-1"         => "Asia Pacific (Hong Kong)",
            "ap-south-1"        => "Asia Pacific (Mumbai)",
            "ap-northeast-3"    => "Asia Pacific (Osaka)",
            "ap-northeast-2"    => "Asia Pacific (Seoul)",
            "ap-southeast-1"    => "Asia Pacific (Singapore)",
            "ap-southeast-2"    => "Asia Pacific (Sydney)",
            "ap-northeast-1"    => "Asia Pacific (Tokyo)",
            "ca-central-1"      => "Canada (Central)",
            //"eu-central-1"      => "Europe (Frankfurt)",
            //"eu-west-1"         => "Europe (Ireland)",
            //"eu-west-2"         => "Europe (London)",
            //"eu-south-1"        => "Europe (Milan)",
            //"eu-west-3"         => "Europe (Paris)",
            //"eu-north-1"        => "Europe (Stockholm)",
            "me-south-1"        => "Middle East (Bahrain)",
            "sa-east-1"         => "South America (Sao Paulo)",
            "us-gov-east-1"     => "AWS GovCloud (US-East)",
            "us-gov-west-1"     => "AWS GovCloud (US-West)",
        ];

        $awsregion = $awsregions[$region];

        $client = AWS::createClient('pricing');
        $result = $client->getProducts([
                    'Filters' => [
                        [
                            'Field' => 'volumeType',
                            'Type' => 'TERM_MATCH',
                            'Value' => 'General Purpose'
                        ],[
                            'Field' => 'location',
                            'Type' => 'TERM_MATCH',
                            'Value' => $awsregion
                        ]
                    ],
                    'FormatVersion' => 'aws_v1',
                    'MaxResults' => 100,
                    'ServiceCode' => 'AmazonEC2'
                    ]);

        $ebsprice = json_decode($result["PriceList"][0], true);
        $ebsterms = $ebsprice["terms"]["OnDemand"];
        $firstebsterm = reset($ebsterms);
        $ebspricedimensions = $firstebsterm["priceDimensions"];
        $firstebsterm = reset($ebspricedimensions);
        $ebs_price = $firstebsterm["pricePerUnit"]["USD"];

        Log::Info("EBS GP2 Pricing for $region is $ebs_price $/GB-month");

        $results = [
            "region" => $region,
            "ebs_cost" => $ebs_price,
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
            "egress_cost" => 0.092,  // Sydney to Internet
        ];

        return json_encode($results);
    }

}
