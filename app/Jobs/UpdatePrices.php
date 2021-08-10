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

                // Extract OnDemand Price
                $odprice = json_decode($result["PriceList"][0],true);
                $odterms = $odprice["terms"]["OnDemand"];
                $odterm = reset($odterms);
                $odPriceDimensions = $odterm["priceDimensions"];
                $firstodpd = reset($odPriceDimensions);
                $od_price_per_unit = $firstodpd["pricePerUnit"]["USD"];

                // Extract 1YR Convertible RI Price
                //$riprice = json_decode($result["PriceList"][0], true);
                //$reserved = $riprice["terms"]["Reserved"];
                //$rifirst = reset($reserved);
                //$ripricedimensions = $rifirst["priceDimensions"];
                //$firstripd = reset($ripricedimensions);
                //$ri_price_per_unit = $firstripd["pricePerUnit"]["USD"];
                //$riLeaseContractLength = $rifirst["termAttributes"]["LeaseContractLength"];
                //$riPurchaseOption = $rifirst["termAttributes"]["PurchaseOption"];
                //$riOfferingClass = $rifirst["termAttributes"]["OfferingClass"];


                //$price_per_unit = (reset(reset(reset($terms))))["pricePerUnit"]["USD"];

                Log::info("On Demand Price per unit for instance $instance_type in $key is $od_price_per_unit");
                //Log::info("RI Price per unit for instance $instance_type in $key is $ri_price_per_unit");

                // Update Database
                
                // Drop existing OD price record if it exists
                if (Price::where('instanceType', $instance_type)->where('region', $key)
                        ->where('termType','OnDemand')->exists()) {
                    $oldodprice = Price::where('instanceType', $instance_type)->where('region', $key)
                        ->where('termType','OnDemand')->first();
                    $oldodprice->delete();
                }

                // Drop existing RI price record if it exists
                //if (Price::where('instanceType', $instance_type)->where('region', $key)
                //        ->where('termType','RI')->exists()) {
                //   $oldodprice = Price::where('instanceType', $instance_type)->where('region', $key)
                //        ->where('termType','RI')->first();
                // $oldodprice->delete();
                //}

                $odprice = new Price;
                $odprice->service = 'ec2';
                $odprice->description = "OnDemand " . $instance_type . " instance in " . $key;
                $odprice->instanceType = $instance_type;
                $odprice->region = $key;
                $odprice->termType = "OnDemand";
                $odprice->convertible = false;
                $odprice->term = 0;
                $odprice->currency = "USD";
                $odprice->pricePerUnit = $od_price_per_unit;
                $odprice->save();

                //$riprice = new Price;
                //$riprice->service = 'ec2';
                //$riprice->description = "1YR Convertible Reserved Instance " . $instance_type . " instance in " . $key;
                //$riprice->instanceType = $instance_type;
                //$riprice->region = $key;
                //$riprice->termType = "RI";
                //$riprice->convertible = true;
                //$riprice->term = 1;
                //$riprice->currency = "USD";
                //$riprice->pricePerUnit = $ri_price_per_unit;
                //$riprice->save();


                sleep(1);

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
