<?php
/**
 * Copyright (C) 2012 by iRail vzw/asbl
 *
 * @author Hannes Van De Vreken (hannes aŧ irail.be) 
 * @license AGPLv3
 *
 * A request to this resource is made with the following URI:
 *     tdtinstance/transport/liveboard/:country/:company/:stop-id/(:direction(/:yyyy/:mm/:dd/:hh/:mm(/:ss)))
 */

require_once( __DIR__ . "/model/BoardModel.class.php");

class Liveboard extends AResource{
    
    public function call(){
        // DEFAULTS
        $direction = 'departures' ; //
        $time = time();             // 
        $number = 20 ;              //
        
        $boardmodel = new BoardModel();
        
        // Get request parameters: $country, $company, $sid (stop_id), $direction
        // TODO as stated here ^
        
        $da = preg_match( '/arrivals/i', $direction ) ? 'A' : 'D';
        
        return $boardmodel->cassandra->get( $country, $company, $sid, $da, $time, $number );
    }
    
    public static function getDoc(){
        return "Request for the /departures or /arrival of this stop";
    }
}

?>