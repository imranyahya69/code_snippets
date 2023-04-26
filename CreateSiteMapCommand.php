<?php

namespace App\Console\Commands;

use App\Models\OpenHouse;
use App\Models\Property;
use Carbon\Carbon;
use DOMAttr;
use DOMDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\Foreach_;
use SimpleXMLElement;

class CreateSiteMapCommand extends Command
{
    public $OHPropertiesByCity, $OHPropertiesByZipCode, $OHPropertiesByHighSchool, $OHPropertiesByListingId, $CSPropertiesByCity, $CSPropertiesByZipCode, $CSPropertiesByHighSchool, $CSPropertiesByListingId;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'site:map';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $cities = config('city.cities');
        $propertyTypes = ['Residential', 'New Construction', 'Condo'];

        //queries for open house
        $this->OHPropertiesByCity = Property::whereHas('openHouses', function ($q) {
            $q->where('OpenHouseStartTime', '<=', Carbon::now());
        })->select('City', 'updated_at')->get()->unique('City');

        $this->OHPropertiesByZipCode = Property::whereHas('openHouses', function ($q) {
            $q->where('OpenHouseStartTime', '<=', Carbon::now());
        })->select('PostalCode', 'updated_at')->get()->unique('PostalCode');

        $this->OHPropertiesByHighSchool = Property::whereHas('openHouses', function ($q) {
            $q->where('OpenHouseStartTime', '<=', Carbon::now());
        })->select('HighSchool', 'updated_at')->get()->unique('HighSchool');

        $this->OHPropertiesByListingId = Property::whereHas('openHouses', function ($q) {
            $q->where('OpenHouseStartTime', '<=', Carbon::now());
        })->select('ListingId', 'updated_at')->get()->unique('ListingId');


        //queries for coming soon
        $this->CSPropertiesByCity = Property::where('StandardStatus', 'Coming Soon')->select('City', 'updated_at')->groupBy('City', 'updated_at')->orderBy('MAR_ExpectedActiveDate', 'asc')->get();

        $this->CSPropertiesByZipCode = Property::where('StandardStatus', 'Coming Soon')->select('PostalCode', 'updated_at')->groupBy('PostalCode', 'updated_at')->orderBy('MAR_ExpectedActiveDate', 'asc')->get();

        $this->CSPropertiesByHighSchool = Property::where('StandardStatus', 'Coming Soon')->select('HighSchool', 'updated_at')->groupBy('HighSchool', 'updated_at')->orderBy('MAR_ExpectedActiveDate', 'asc')->get();

        $this->CSPropertiesByListingId = Property::where('StandardStatus', 'Coming Soon')->select('ListingId', 'updated_at')->groupBy('ListingId', 'updated_at')->orderBy('MAR_ExpectedActiveDate', 'asc')->get();


        //open house properties by city xml
        function oHByCity($OHPropertiesByCity, $dom = null, $root = null, $cities)
        {
            foreach ($OHPropertiesByCity as $pC) {
                $main_node = $dom->createElement('url');
                // $child_node_url = $dom->createElement('loc', config('app.url') . '/open-houses-by-city/' . str_replace("'", "", str_replace(' ', '-', strtolower($pC->City))));
                $child_node_url = $dom->createElement('loc', config('app.url') . '/open-houses-by-city/' . $cities[$pC->City]);
                $child_node_image = $dom->createElement('images', count($pC->images()));
                $child_node_last_mod = $dom->createElement('lastmod', $pC->updated_at);
                $child_node_change_freq = $dom->createElement('changefreq', 'weekly');
                $child_node_priority = $dom->createElement('priority', '0.8');

                $main_node->appendChild($child_node_url);
                $main_node->appendChild($child_node_image);
                $main_node->appendChild($child_node_last_mod);
                $main_node->appendChild($child_node_change_freq);
                $main_node->appendChild($child_node_priority);
                $root->appendChild($main_node);
            }
            return $root;
        }

        //open house properties by postal code xml
        function oHByZip($OHPropertiesByZipCode, $dom = null, $root = null)
        {
            foreach ($OHPropertiesByZipCode as $pZC) {
                $main_node = $dom->createElement('url');
                $child_node_url = $dom->createElement('URL', config('app.url') . '/open-houses-by-zipcode/' . $pZC->PostalCode);
                $child_node_image = $dom->createElement('images', count($pZC->images()));
                $child_node_last_mod = $dom->createElement('lastmod', $pZC->updated_at);
                $child_node_change_freq = $dom->createElement('changefreq', 'weekly');
                $child_node_priority = $dom->createElement('priority', '0.8');

                $main_node->appendChild($child_node_url);
                $main_node->appendChild($child_node_image);
                $main_node->appendChild($child_node_last_mod);
                $main_node->appendChild($child_node_change_freq);
                $main_node->appendChild($child_node_priority);
                $root->appendChild($main_node);
            }
            return $root;
        }

        //open house properties by high school xml
        function oHByHighSchool($OHPropertiesByHighSchool, $dom = null, $root = null)
        {
            foreach ($OHPropertiesByHighSchool as $pHS) {
                $main_node = $dom->createElement('url');
                $child_node_url = $dom->createElement('URL', config('app.url') . '/open-houses-by-high-school/' .  str_replace(' ', '-', strtolower($pHS->HighSchool)));
                $child_node_image = $dom->createElement('images', count($pHS->images()));
                $child_node_last_mod = $dom->createElement('lastmod', $pHS->updated_at);
                $child_node_change_freq = $dom->createElement('changefreq', 'weekly');
                $child_node_priority = $dom->createElement('priority', '0.8');


                $main_node->appendChild($child_node_url);
                $main_node->appendChild($child_node_image);
                $main_node->appendChild($child_node_last_mod);
                $main_node->appendChild($child_node_change_freq);
                $main_node->appendChild($child_node_priority);
                $root->appendChild($main_node);
            }
            return $root;
        }

        //open house properties by Listing id xml
        function oHByListingId($OHPropertiesByListingId, $dom = null, $root = null)
        {
            foreach ($OHPropertiesByListingId as $pLI) {
                $main_node = $dom->createElement('url');
                $child_node_url = $dom->createElement('URL', config('app.url') . '/property/' .  $pLI->ListingId);
                $child_node_image = $dom->createElement('images', count($pLI->images()));
                $child_node_last_mod = $dom->createElement('lastmod', $pLI->updated_at);
                $child_node_change_freq = $dom->createElement('changefreq', 'weekly');
                $child_node_priority = $dom->createElement('priority', '0.8');

                $main_node->appendChild($child_node_url);
                $main_node->appendChild($child_node_image);
                $main_node->appendChild($child_node_last_mod);
                $main_node->appendChild($child_node_change_freq);
                $main_node->appendChild($child_node_priority);
                $root->appendChild($main_node);
            }
            return $root;
        }

        /**********************************************************************************************************************************************************************/

        //coming soon properties by city xml
        function cSByCity($CSPropertiesByCity, $dom = null, $root = null, $cities)
        {
            foreach ($CSPropertiesByCity as $pC) {
                $main_node = $dom->createElement('url');
                // $child_node_url = $dom->createElement('URL', config('app.url') . '/coming-soon-by-city/' . str_replace(' ', '-', strtolower($pC->City)));
                $child_node_url = $dom->createElement('URL', config('app.url') . '/coming-soon-by-city/' . $cities[$pC->City]);
                $child_node_image = $dom->createElement('images', count($pC->images()));
                $child_node_last_mod = $dom->createElement('lastmod', $pC->updated_at);
                $child_node_change_freq = $dom->createElement('changefreq', 'weekly');
                $child_node_priority = $dom->createElement('priority', '0.8');

                $main_node->appendChild($child_node_url);
                $main_node->appendChild($child_node_image);
                $main_node->appendChild($child_node_last_mod);
                $main_node->appendChild($child_node_change_freq);
                $main_node->appendChild($child_node_priority);
                $root->appendChild($main_node);
            }
            return $root;
        }

        //coming soon properties by postal code xml
        function cSByZip($CSPropertiesByZipCode, $dom = null, $root = null)
        {
            foreach ($CSPropertiesByZipCode as $pZC) {
                $main_node = $dom->createElement('url');
                $child_node_url = $dom->createElement('URL', config('app.url') . '/coming-soon-by-zipcode/' . $pZC->PostalCode);
                $child_node_image = $dom->createElement('images', count($pZC->images()));
                $child_node_last_mod = $dom->createElement('lastmod', $pZC->updated_at);
                $child_node_change_freq = $dom->createElement('changefreq', 'weekly');
                $child_node_priority = $dom->createElement('priority', '0.8');

                $main_node->appendChild($child_node_url);
                $main_node->appendChild($child_node_image);
                $main_node->appendChild($child_node_last_mod);
                $main_node->appendChild($child_node_change_freq);
                $main_node->appendChild($child_node_priority);
                $root->appendChild($main_node);
            }
            return $root;
        }

        //coming soon properties by high school xml
        function cSByHighSchool($CSPropertiesByHighSchool, $dom = null, $root = null)
        {
            foreach ($CSPropertiesByHighSchool as $pHS) {
                $main_node = $dom->createElement('url');
                $child_node_url = $dom->createElement('URL', config('app.url') . '/coming-soon-by-high-school/' .  str_replace(' ', '-', strtolower($pHS->HighSchool)));
                $child_node_image = $dom->createElement('images', count($pHS->images()));
                $child_node_last_mod = $dom->createElement('lastmod', $pHS->updated_at);
                $child_node_change_freq = $dom->createElement('changefreq', 'weekly');
                $child_node_priority = $dom->createElement('priority', '0.8');

                $main_node->appendChild($child_node_url);
                $main_node->appendChild($child_node_image);
                $main_node->appendChild($child_node_last_mod);
                $main_node->appendChild($child_node_change_freq);
                $main_node->appendChild($child_node_priority);
                $root->appendChild($main_node);
            }
            return $root;
        }

        //coming soon properties by Listing id xml
        function cSByListingId($CSPropertiesByListingId, $dom = null, $root = null)
        {
            foreach ($CSPropertiesByListingId as $pLI) {
                $main_node = $dom->createElement('url');
                $child_node_url = $dom->createElement('URL', config('app.url') . '/property/' .  $pLI->ListingId);
                $child_node_image = $dom->createElement('images', count($pLI->images()));
                $child_node_last_mod = $dom->createElement('lastmod', $pLI->updated_at);
                $child_node_change_freq = $dom->createElement('changefreq', 'weekly');
                $child_node_priority = $dom->createElement('priority', '0.8');

                $main_node->appendChild($child_node_url);
                $main_node->appendChild($child_node_image);
                $main_node->appendChild($child_node_last_mod);
                $main_node->appendChild($child_node_change_freq);
                $main_node->appendChild($child_node_priority);
                $root->appendChild($main_node);
            }
            return $root;
        }

        /**********************************************************************************************************************************************************************/

        //properties by type
        function propertyByType($propertyTypes, $dom = null, $root = null)
        {
            foreach ($propertyTypes as $pT) {
                $main_node = $dom->createElement('url');
                $child_node_url = $dom->createElement('URL', config('app.url') . '/' .  $pT);
                $child_node_image = $dom->createElement('images', 0);
                $child_node_last_mod = $dom->createElement('lastmod', Carbon::now());
                $child_node_change_freq = $dom->createElement('changefreq', 'weekly');
                $child_node_priority = $dom->createElement('priority', '0.8');

                $main_node->appendChild($child_node_url);
                $main_node->appendChild($child_node_image);
                $main_node->appendChild($child_node_last_mod);
                $main_node->appendChild($child_node_change_freq);
                $main_node->appendChild($child_node_priority);

                $root->appendChild($main_node);
            }
            return $root;
        }

        /**********************************************************************************************************************************************************************/

        //create XML object
        function createXML($object, $fileName, $type, $cities = null)
        {
            $dom = new DOMDocument();
            $dom->encoding = 'utf-8';
            $dom->xmlVersion = '1.0';
            $dom->formatOutput = true;
            $xml_file_name = $fileName;
            $root = $dom->createElement('urlset');
            switch ($type) {
                case ('oh_by_city'):
                    $dom->appendChild(oHByCity($object, $dom, $root, $cities));
                    break;
                case ('oh_by_zip'):
                    $dom->appendChild(oHByZip($object, $dom, $root));
                    break;
                case ('oh_by_high_school'):
                    $dom->appendChild(oHByHighSchool($object, $dom, $root));
                    break;
                case ('oh_by_listing_id'):
                    $dom->appendChild(oHByListingId($object, $dom, $root));
                    break;
                case ('cs_by_city'):
                    $dom->appendChild(cSByCity($object, $dom, $root, $cities));
                    break;
                case ('cs_by_zip'):
                    $dom->appendChild(cSByZip($object, $dom, $root));
                    break;
                case ('cs_by_high_school'):
                    $dom->appendChild(cSByHighSchool($object, $dom, $root));
                    break;
                case ('cs_by_listing_id'):
                    $dom->appendChild(cSByListingId($object, $dom, $root));
                    break;
                case ('by_property_type'):
                    $dom->appendChild(propertyByType($object, $dom, $root));
                    break;
            }
            $dom->save(public_path($xml_file_name));
            echo "$xml_file_name has been successfully created";
        }

        /**********************************************************************************************************************************************************************/
        createXML($this->OHPropertiesByCity, 'open-house-properties-by-city-sitemap.xml', 'oh_by_city', $cities);
        createXML($this->OHPropertiesByZipCode, 'open-house-properties-by-zipcode-sitemap.xml', 'oh_by_zip');
        createXML($this->OHPropertiesByHighSchool, 'open-house-properties-by-high-school-sitemap.xml', 'oh_by_high_school');
        createXML($this->OHPropertiesByListingId, 'open-house-properties-by-listing-id-sitemap.xml', 'oh_by_listing_id');

        /**********************************************************************************************************************************************************************/
        createXML($this->CSPropertiesByCity, 'coming-soon-properties-by-city-sitemap.xml', 'cs_by_city', $cities);
        createXML($this->CSPropertiesByZipCode, 'coming-soon-properties-by-zipcode-sitemap.xml', 'cs_by_zip');
        createXML($this->CSPropertiesByHighSchool, 'coming-soon-properties-by-high-school-sitemap.xml', 'cs_by_high_school');
        createXML($this->CSPropertiesByListingId, 'coming-soon-properties-by-listing-id-sitemap.xml', 'cs_by_listing_id');

        /**********************************************************************************************************************************************************************/
        createXML($propertyTypes, 'properties-by-type-sitemap.xml', 'by_property_type');

        /**********************************************************************************************************************************************************************/

        //main site url
        $domMainSite = new DOMDocument();
        $domMainSite->encoding = 'utf-8';
        $domMainSite->xmlVersion = '1.0';
        $domMainSite->formatOutput = true;
        $xml_file_name = 'main_site-sitemap.xml';
        $root = $domMainSite->createElement('SiteMap');
        $main_node = $domMainSite->createElement('url');
        $child_node_url = $domMainSite->createElement('URL', config('app.url') . '/');
        $child_node_image = $domMainSite->createElement('images', 0);
        $child_node_last_mod = $domMainSite->createElement('lastmod', Carbon::now());
        $child_node_change_freq = $domMainSite->createElement('changefreq', 'weekly');
        $child_node_priority = $domMainSite->createElement('priority', '0.8');

        $main_node->appendChild($child_node_url);
        $main_node->appendChild($child_node_image);
        $main_node->appendChild($child_node_last_mod);
        $main_node->appendChild($child_node_change_freq);
        $main_node->appendChild($child_node_priority);
        $root->appendChild($main_node);
        $domMainSite->appendChild($root);
        if (strpos(config('app.url'), 'openhouse') !== false) {

            $domMainSite->appendChild(propertyByType($propertyTypes, $domMainSite, $root));
            $domMainSite->appendChild(oHByCity($this->OHPropertiesByCity, $domMainSite, $root, $cities));
            $domMainSite->appendChild(oHByZip($this->OHPropertiesByZipCode, $domMainSite, $root));
            $domMainSite->appendChild(oHByHighSchool($this->OHPropertiesByHighSchool, $domMainSite, $root));
            $domMainSite->appendChild(oHByListingId($this->OHPropertiesByListingId, $domMainSite, $root));
        }
        if (strpos(config('app.url'), 'comingsoon') !== false) {
            $domMainSite->appendChild(propertyByType($propertyTypes, $domMainSite, $root));
            $domMainSite->appendChild(cSByCity($this->CSPropertiesByCity, $domMainSite, $root, $cities));
            $domMainSite->appendChild(cSByZip($this->CSPropertiesByZipCode, $domMainSite, $root));
            $domMainSite->appendChild(cSByHighSchool($this->CSPropertiesByHighSchool, $domMainSite, $root));
            $domMainSite->appendChild(cSByListingId($this->CSPropertiesByListingId, $domMainSite, $root));
        }
        $domMainSite->save(public_path($xml_file_name));
        echo "$xml_file_name has been successfully created";
    }
}
