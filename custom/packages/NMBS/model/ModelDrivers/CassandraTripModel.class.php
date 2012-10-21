<?php
/**
 * Copyright (C) 2012 by iRail vzw/asbl
 *
 * @author Hannes Van De Vreken (hannes aŧ irail.be) 
 * @license AGPLv3
 *
 */

require_once( __DIR__ . "/../../lib/Cassandra.class.php" );

use phpcassa\Connection\ConnectionPool;
use phpcassa\ColumnFamily;
use phpcassa\ColumnSlice;

class CassandraTripModel extends TripModelDriver{
	
    private static $limit    = 200 ;
    private static $tripcf   = NULL ;
    private static $routecf   = NULL ;
    private static $stoptimescf   = NULL ;
    private static $otherday = 3 ;// 3 am
    private static $dateformat = 'Ymd' ;
	
	public function __construct(){
		C::setup();
        self::$tripcf = C::getColumnFamily('trips');
        self::$routecf = C::getColumnFamily('routes');
        self::$stoptimescf = C::getColumnFamily('stop_times');
    }
    
    /**
     * @param string $country (3 letter countrycode)
     * @param string $company
     * @param object $trip
     */
    public function save( $country, $company, $trip ){
        $tz = date_default_timezone_get();
        date_default_timezone_set('CET');
        
        // add route if not existant
        // fyi: a route - trip relation is like a class - object relation
        $route = $this->add_route($country, $company, $trip->stops );
        $trip->route = $route;
        if( $this->exists($country, $company, $trip->tid, $trip->iteration) ){
            $orig = $this->get( $country, $company, date( self::$dateformat , time() - self::$otherday * 3600 ), 
                                $trip->tid, $trip->iteration );
            $hash1 = md5( serialize($orig->stops) );
            $trip->stops = $this->merge($orig, $trip);
            $hash2 = md5( serialize($trip->stops) );
            if( $hash1 == $hash2 ){ // not 100,00% sure, but fair enough
                return TRUE ; // get outta here, you don't need to save this
            }
        }
        $result = $this->put($country, $company, $trip );
        date_default_timezone_set($tz); // uncommit your actions
        return $result ;
    }
    
    /**
     * @param string $country
     * @param string $company
     * @param string $tid
     * @param uint32 $iteration
     */
    public function exists( $country, $company, $tid, $iteration ){
        
        $key = array($country, $company, date( self::$dateformat, time() - self::$otherday * 3600 ));
        
        $slice = new ColumnSlice( array( $tid, $iteration ), array( $tid, $iteration ), self::$limit ); 
        try{
            $count = self::$tripcf->get_count( $key, $slice );
        }catch ( exception $e ){
            // improve error handling pl0x
            echo $e ; exit ;
        }
        return $count > 0 ;
    }
    
    /**
     * @param string $country
     * @param string $company
     * @param object $trip
     * @return true or false
     * 
     * it is possible old data is overwritten with this put. It should be handled by '$this->merge(...)'
     */
    private function put( $country, $company, $trip ){
        $key1 = array( $country, $company, date( self::$dateformat, time() - self::$otherday * 3600 ));
        // country, company, trip_id, iteration
        $key2 = array( $country, $company, date( self::$dateformat, time() - self::$otherday * 3600) , $trip->tid, $trip->iteration );
        
        $data = array(  'route'     => $trip->route,
                        'type'      => $trip->type,
                        'tid'       => $trip->tid,
                        'country'   => $trip->country,
                        'company'   => $trip->company,
                        'iteration' => $trip->iteration ) ;
                        
        if( isset( $trip->stops[0]['dep'] ) ){
            $data['starttime'] = $trip->stops[0]['dep'] ;
        }
        if( isset( $trip->stops[count($trip->stops)-1]['arr'] ) ){
            $data['endtime'] = $trip->stops[count($trip->stops)-1]['arr'] ;
        }
        
        $a1 = array(array(array( $trip->tid, $trip->iteration ), serialize($data) ) ) ;
        $a2 = array();
        $i = 0 ;
        foreach( $trip->stops as &$stop ){
            $a2[] = array( $i , serialize( $stop ) ) ;
            $i++ ;
        }
        try{
            self::$tripcf->insert( $key1, $a1 ) ;
            self::$stoptimescf->insert( $key2, $a2 ) ;
        }catch( exception $e ){
            // improve error handling pl0x
            echo $e ; exit ;
        }
        return TRUE ;
    }
    
    /**
     * @param object $orig
     * @param object $updates
     */
    private function merge( $orig, $updates ){
        
        $tz = date_default_timezone_get();
        date_default_timezone_set('CET');
        
        $stops = array();
        foreach( $orig->stops as $stop ){
            foreach( $updates->stops as $stop_update ){
                if( $stop['stop'] == $stop_update['stop'] ){
                    // let's merge these 2 stops.
                    if( isset($stop_update['cancelled']) ){
                        // remove all timing data, vehicle will never stop there
                        unset( $stop['dep'] );
                        unset( $stop['arr'] );
                        unset( $stop['delay'] );
                        unset( $stop['plat_change'] );
                        unset( $stop['platf'] );
                        $stop['cancelled'] = 1 ; // mark cancellation
                    }else{
                        // store more historic data if possible
                        $history = array();
                        if( isset( $stop['history'] ) ){
                            $history = $stop['history'];
                        }
                        
                        // the delay has changed
                        if( $stop['delay'] != $stop_update['delay'] ){
                            $history[] = array( time(), 'delay', $stop['delay'], $stop_update['delay'] );
                            $stop['delay'] = $stop_update['delay'] ;
                        }
                        
                        if( !isset( $stop['platf'] ) && isset( $stop_update['platf'] ) ){
                            $stop['platf'] = $stop_update['platf'] ;
                        }else if( isset( $stop_update['platf'] )){
                            // the platform changed
                            if( $stop['platf'] == $stop_update['platf'] ){
                                $history[] = array( time(), 'plaf_change', $stop['platf'], $stop_update['platf'] );
                                $stop['delay'] = $stop_update['delay'] ;
                            }
                        }
                        
                        // set current platform abnormality flag
                        if( isset( $stop_update['plat_change'] ) ){
                            $stop['plat_change'] = 1 ;
                        }else{
                            unset( $stop['plat_change'] );
                        }
                        
                        if( count($history) != 0 ){
                            $stop['history'] = $history;
                        }
                    }
                    break; // aye, BREAK!
                }
            }
            $stops[] = $stop ;
        }
        
        date_default_timezone_set($tz);
        return $stops ;
    }
    
    /**
     * @param string $country
     * @param string $company
     * @param object $trip pass-by-ref
     */
    private function add_route( $country, $company, &$trip ){
        
        $tripcore = array();
        foreach( $trip as $stop ){
            $tripcore[] = $stop['stop'] ;
        }
        $serialized_trip = serialize( $tripcore ) ;
        $hash = md5(serialize( $tripcore ));
        
        $key = array($country, $company);
        
        // limit is considered high enough never ever to be exceeded by number of routes with same hash
        $lim = 50;
        $slice = new ColumnSlice( array( $hash, '' ), array( $hash, '' ), $lim ); 
        try{
            $count = self::$routecf->get_count( $key, $slice );
        }catch ( exception $e ){
            // improve error handling pl0x
            echo $e ; exit ;
        }
        $tobeadded = TRUE ;
        if( $count != 0 ){
            try{
                $result = self::$routecf->get( $key, $slice );
            }catch( exception $e ){
                // improve error handling pl0x
                echo $e ; exit ;
            }
            // check if existant and change $count, if not existant: change $tobeadded
            foreach( $result as $row ){
                if( end( $row ) == $serialized_trip ){
                    $count = end($row[0]) ;
                    $tobeadded = false ;
                    break ;
                }
            }
        }
        
        if( $tobeadded ){
            //echo "adding route..." ;
            $data = array(array(array( $hash, $count ), serialize($tripcore) ));
            self::$routecf->insert( $key, $data );
        }
        
        return array( $hash, $count ) ;
    }
    
    /**
     * @param string $country
     * @param string $company
     * @param uint32 $date
     * @param string $id
     * @param uint32 $iteration
     * @return object
     */
    public function get( $country, $company, $date, $tid, $iteration = NULL ){
        // TODO handle iteration === NULL
        $key1 = array($country, $company, $date, $tid, $iteration );
        $slice1 = new ColumnSlice('','');
        
        $format = function( $stop ){
             return unserialize( end($stop) ); 
        };
        
        try{
            if( self::$stoptimescf->get_count( $key1, $slice1 ) < 1 ){
                return array() ;
            }else{
                // get stops
                $result = self::$stoptimescf->get( $key1, $slice1 );
                $result = array_map( $format, $result );
                $trip = new stdClass();
                $trip->stops = $result;
                $trip->tid = $tid;
                
                // get more data
                $key2 = array($country, $company, $date );
                $slice2 = new ColumnSlice( array($tid, $iteration), array($tid, $iteration) );
                $result = self::$tripcf->get( $key2, $slice2 );
                $meta = unserialize($result[0][1]) ;
                
                $trip->iteration = $meta['iteration'] ;
                $trip->type = $meta['type'];
                $trip->company = $meta['company'];
                $trip->country = $meta['country'];
                
                return $trip ;
            }
        }catch( exception $e ){
            // error handling pl0x
            echo $e ; exit ;
        }
    }
}
 
?>
