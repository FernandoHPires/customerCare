<?php

namespace App\Amur\BO;

use App\Amur\Bean\ILogger;
use App\Amur\Bean\IDB;
use App\Models\PropertyTable;
use App\Models\IlaTable;

class ILABO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function index($applicationId) {

        $propertyTable = PropertyTable::query()
        ->where('application_id', $applicationId)
        ->first();

        $orderby = '';

        if ($propertyTable) {

            if ($propertyTable->postal_code != '' && $propertyTable->city != '' && $propertyTable->province != '') {

                $postalCode = $propertyTable->postal_code;
                $city = $propertyTable->city;
                $province = $propertyTable->province;

                $postalCode1 = substr($postalCode, 0, 3);
                $postalCode2 = substr($postalCode, 0, 2);
                $postalCode3 = substr($postalCode, 0, 1);

                $this->logger->info('ILABO->index',[$postalCode1, $postalCode2, $postalCode3, $city, $province]);
        
                $orderby = "order by if(lower(postal_code) like lower('$postalCode1%'),0,1),
                            if(lower(postal_code) like lower('$postalCode2%'),0,1),
                            if(lower(postal_code) like lower('$postalCode3%'),0,1),
                            if(lower(city)        like lower('$city%'),0,1),
                            if(lower(province)    like lower('$province%'),0,1)";
            }
        }

        $query = "select * from ila_table $orderby";
        $ilas = $this->db->query($query);

        $data = array();
        foreach($ilas as $key => $value) {
            $data[] = [
                'id' => $value->ila_code,
                'firmName' => $value->firm_name,
                'name' => $value->name,
                'position' => $value->position,
                'telephone' => $value->telephone,
                'fax' => $value->fax,
                'email' => $value->email,
                'address' => $this->address($value),
                'useAsIla' => $value->use_ila,
                'comments' => $value->comments,
                'rating' => $value->rating,

                'unitNumber' => $value->unit_number,
                'streetNumber' => $value->street_number,
                'streetName' => $value->street_name,
                'streetType' => $value->street_type,
                'direction' => $value->street_direction,
                'city' => $value->city,
                'province' => $value->province,
                'postalCode' => $value->postal_code,
                'poBox' => $value->po_box_number,
                'stn' => $value->station,
                'rr' => $value->rural_route,
                'site' => $value->site,
                'comp' => $value->compartment,
            ];
        }
        
        return $data;
    }

    public function show($code) {
        $ila = IlaTable::find($code);

        if(!$ila) {
            return false;
        }

        return [
            'id' => $ila->ila_code,
            'firmName' => $ila->firm_name,
            'name' => $ila->name,
            'position' => $ila->position,
            'telephone' => $ila->telephone,
            'fax' => $ila->fax,
            'email' => $ila->email,
            'address' => $this->address($ila),
            'useAsIla' => $ila->use_ila,
            'comments' => $ila->comments,
            'rating' => $ila->rating,

            'unitNumber' => $ila->unit_number,
            'streetNumber' => $ila->street_number,
            'streetName' => $ila->street_name,
            'streetType' => $ila->street_type,
            'direction' => $ila->street_direction,
            'city' => $ila->city,
            'province' => $ila->province,
            'postalCode' => $ila->postal_code,
            'poBox' => $ila->po_box_number,
            'stn' => $ila->station,
            'rr' => $ila->rural_route,
            'site' => $ila->site,
            'comp' => $ila->compartment,
        ];
    }

    public function address($value) {
        $address = '';

        if($value->unit_number != '') {
            $address .= $value->unit_number . '-';
        }
        $address .= $value->street_number . ' ' . $value->street_name . ' ' . $value->street_type . ' ' . $value->street_direction. ' '; 

        if ($value->po_box_number != '') {
            $address .= 'PO Box: ' . $value->po_box_number . ' ';
        }

        if ($value->station != '') {
            $address .= 'STN: ' . $value->station . ' ';
        }

        if ($value->rural_route != '') {
            $address .= 'RR: ' . $value->rural_route . ' ';
        }

        if ($value->site != '') {
            $address .= 'SITE: ' . $value->site . ' ';
        }

        if ($value->compartment != '') {
            $address .= 'COMPARTMENT: ' . $value->compartment . ' ';
        }
        
        $address .= $value->city . ' ' . $value->province . ' ' . $value->postal_code;

        return $address;
    }

    public function addIlaFirms(
        $ilaFirmName, $ilaName, $ilaPosition, $ilaTelephone, $ilaFax,
        $ilaEmail, $ilaUnitNumber, $ilaStreetNumber, $ilaStreetName,
        $ilaStreetType, $ilaDirection, $ilaCity, $ilaProvince,
        $ilaPostalCode, $ilaPOBox, $ilaSTN, $ilaRR, $ilaSite,
        $ilaComp, $ilaRating, $useAsIla, $ilaComments
    ) {

        $ilaTable = new IlaTable();
        $ilaTable->firm_name = $ilaFirmName;
        $ilaTable->name = $ilaName;
        $ilaTable->position = $ilaPosition;
        $ilaTable->telephone = $ilaTelephone;
        $ilaTable->fax = $ilaFax;
        $ilaTable->email = $ilaEmail;
        $ilaTable->unit_number = $ilaUnitNumber;
        $ilaTable->street_number = $ilaStreetNumber;
        $ilaTable->street_name = $ilaStreetName;
        $ilaTable->street_type = $ilaStreetType;
        $ilaTable->street_direction = $ilaDirection;
        $ilaTable->city = $ilaCity;
        $ilaTable->province = $ilaProvince;
        $ilaTable->postal_code = $ilaPostalCode;
        $ilaTable->po_box_number = $ilaPOBox;
        $ilaTable->station = $ilaSTN;
        $ilaTable->rural_route = $ilaRR;
        $ilaTable->site = $ilaSite;
        $ilaTable->compartment = $ilaComp;
        $ilaTable->use_ila = $useAsIla;
        $ilaTable->rating = $ilaRating;
        $ilaTable->comments = $ilaComments;
        $ilaTable->save();        
    }

    public function updateIlaFirm(
        $id, $firmName, $name, $position, $telephone, $fax, $email, $useAsIla, $comments, $rating,
        $unitNumber, $streetNumber, $streetName, $streetType, $direction, $city, $province, $postalCode
    ) {
        $ilaTable = IlaTable::find($id);
        if(!$ilaTable) {
            return false;
        }
    
        $ilaTable->firm_name = $firmName;
        $ilaTable->name = $name;
        $ilaTable->position = $position;
        $ilaTable->telephone = $telephone;
        $ilaTable->fax = $fax;
        $ilaTable->email = $email;
        $ilaTable->use_ila = $useAsIla;
        $ilaTable->comments = $comments;
        $ilaTable->rating = $rating;
        $ilaTable->unit_number = $unitNumber;
        $ilaTable->street_number = $streetNumber;
        $ilaTable->street_name = $streetName;
        $ilaTable->street_type = $streetType;
        $ilaTable->street_direction = $direction;
        $ilaTable->city = $city;
        $ilaTable->province = $province;
        $ilaTable->postal_code = $postalCode;
        $ilaTable->save();
    }
}