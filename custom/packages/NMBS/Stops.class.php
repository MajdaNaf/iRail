<?php
/**
 * Copyright (C) 2012 by iRail vzw/asbl
 *
 * @author Hannes Van De Vreken (hannes aŧ irail.be) 
 * @license AGPLv3
 *
 * 
 */

require_once( __DIR__ . "/model/StopModel.class.php");

class Stops extends AResource{
    
    public function call(){
        
        $country = 'BEL' ;
        $company = 'NMBSSNCB' ;
        $baseurl = Config::$HOSTNAME . Config::$SUBDIR . 'NMBS/Liveboard' ;
        
        $stopmodel = new StopModel() ;
        $results = $stopmodel->get( $country, $company );
        
        foreach( $results as &$stop ){
            $stop->departures = $baseurl . '/' . $country . '/' . $company . '/' . $stop->sid . '/departures' ;
        }
        return $results ;
    }
    
    public static function getDoc(){
        return "I present to you: the fabulous list of NMBS/SNCB stops!";
    }
}

?>
