<?php
/**
 * Copyright (C) 2012 by iRail vzw/asbl
 *
 * @author Hannes Van De Vreken (hannes aŧ irail.be) 
 * @license AGPLv3
 *
 * A request to this resource is made with the following URI:
 *     tdtinstance/transport/trip/:country/:company/:trip-id(/:yyyy/:mm/:dd(/:iteration))
 *     
 * Iteration:
 *     some companies tend to have one indication for all vehicles doing the same route at the same time
 */

require_once( __DIR__ . "/model/TripModel.class.php");

class Trip extends AResource{
    
    public function call(){
        
        // Get parameter from the request: $country, $company, $tid, $iteration, $date
        
        $tripmodel = new TripModel() ;
        $results = $tripmodel->get( $country, $company, $date, $tid, $iteration = NULL );
        
        return $results ;
    }
    
    public static function getDoc(){
        return "I present to you: the fabulous list of NMBS/SNCB stops!";
    }
}

?>
