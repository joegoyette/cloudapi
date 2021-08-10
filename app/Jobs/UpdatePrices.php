<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Log;
use AWS;
use App\Models\Price;

class UpdatePrices implements ShouldQueue

{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $regions = [
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

        $instance_types = array(
            "m5.large",
            "m5.xlarge",
            "m5.2xlarge",
            "m5.4xlarge",
            "m5.8xlarge",
            "m5.12xlarge",
            "m5.16xlarge",
            "m5.24xlarge"
        );


        // Create AWS Pricing API Client
        $client = AWS::createClient('pricing');

        foreach ($regions as $key => $value) {
            foreach ($instance_types as $instance_type) {

                Log::info("Getting pricing for $instance_type in $key");

                $result = $client->getProducts([
                    'Filters' => [
                        [
                            'Field' => 'instanceType',
                            'Type' => 'TERM_MATCH',
                            'Value' => $instance_type
                        ],[
                            'Field' => 'location',
                            'Type' => 'TERM_MATCH',
                            'Value' => $value
                        ],
                        [
                            'Field' => 'operatingSystem',
                            'Type' => 'TERM_MATCH',
                            'Value' => 'Linux'
                        ],
                        [
                            'Field' => 'marketoption',
                            'Type' => 'TERM_MATCH',
                            'Value' => 'OnDemand'
                        ],
                        [
                            'Field' => 'preInstalledSw',
                            'Type' => 'TERM_MATCH',
                            'Value' => 'NA'
                        ],
                        [
                            'Field' => 'capacitystatus',
                            'Type' => 'TERM_MATCH',
                            'Value' => 'Used'
                        ],
                        [
                            'Field' => 'tenancy',
                            'Type' => 'TERM_MATCH',
                            'Value' => 'Shared'
                        ]
                    ],
                    'FormatVersion' => 'aws_v1',
                    'MaxResults' => 100,
                    'ServiceCode' => 'AmazonEC2'
                    ]);

                $price = json_decode($result["PriceList"][0],true);
                $terms = $price["terms"]["OnDemand"];
                $price_per_unit = (reset(reset(reset($terms))))["pricePerUnit"]["USD"];

                Log::info("Price per unit for instance $instance_type in $key is $price_per_unit");

                // Update Database
                
                // Drop existing price record if it exists
                if (Price::where('instanceType', $instance_type)->where('region', $key)->exists()) {
                    $oldprice = Price::where('instanceType', $instance_type)->where('region', $key)->first();
                    $oldprice->delete();
                }

                $price = new Price;
                $price->service = 'ec2';
                $price->description = $instance_type . " instance in " . $key;
                $price->instanceType = $instance_type;
                $price->region = $key;
                $price->termType = "OnDemand";
                $price->convertible = false;
                $price->term = 0;
                $price->currency = "USD";
                $price->pricePerUnit = $price_per_unit;

                $price->save();

            }
        }

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
    }
}
