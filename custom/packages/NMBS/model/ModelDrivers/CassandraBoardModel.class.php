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

class CassandraBoardModel extends BoardModelDriver{
	
	private static $servers  = array('192.168.5.4');
	private static $keyspace = 'iRail' ;
    private static $limit    = 50 ;
    private static $boardcf   = NULL ;
    private static $otherday = 3 ;// 3 am
    private static $dateformat = 'Ymd' ;
	
	public function __construct(){
		C::setup( self::$servers, self::$keyspace );
        self::$boardcf = C::getColumnFamily('board');
    }
    
    /**
     * @param string $country
     * @param string $company
     * @param object $board
     * @param true
     */
    public function save( $country, $company, $board ){
        
        foreach( $board->trips as $trip ){
            $this->save_trip($country, $company, $board->sid, $board->da, $trip) ;
        }
        return TRUE ;
    }
    
    /**
     * @param string $country
     * @param string $company
     * @param uint32 $sid short for: stop_id
     * @param string $da enum('D','A')
     * @return void
     */
    private function save_trip( $country, $company, $sid, $da, $trip ){
        
        $key = array( $country, $company, $sid, date( self::$dateformat, time() - self::$otherday * 3600 ), $da );
        $trip_time = $da == 'D' ? $trip['dep'] : $trip['arr'] ;
        $slice = new ColumnSlice( array($trip_time,''), array($trip_time,'') );
        try{
            $count = self::$boardcf->get_count($key, $slice) ;
        }catch( exception $e ){
            // TODO error handling pl0x
            echo $e ; exit ;
        }
        
        $exists = FALSE ;
        if( $count != 0 ){ // get right duplicate key. add an extra key entry if trip not yet stored on board
            $result = self::$boardcf->get($key, $slice) ;
            foreach( $result as $entry ){
                $t = unserialize( $entry[1] );
                if( $trip['tid'] == $t['tid'] &&
                    $trip['iteration'] == $t['iteration'] &&
                    $trip['country'] == $t['country'] &&
                    $trip['company'] == $t['company'] ){
                    
                    $count = $entry[0][1];
                    $exists = TRUE ;
                    break ;
                }
            }
        }
        
        if( $exists ){
            return TRUE ; //void
        }
        $data = array();
        $data[] = array( array( $trip_time ,$count), serialize($trip) );
        try{
            self::$boardcf->insert( $key, $data );
        }catch( exception $e ){
            // TODO add error handling pl0x
            echo $e ; exit ;
        }
        return TRUE ;
    }
    
    /**
     * @param string $country
     * @param string $company
     * @param uint32 $sid    //stop_id
     * @param long   $time   // unix timestamp
     * @param uint32 $number // limit for rows returned
     */
    public function get( $country, $company, $sid, $da, $time, $number = 50 ){
        
        $slice = new ColumnSlice( array( $time , ''), '' );
        $key = array( $country, $company, $sid, date( self::$dateformat, $time ), $da ) ;
        
        $board = new stdClass();
        $board->country  = $country ;
        $board->company  = $company ;
        $board->sid      = $sid ;
        $board->trips = array() ;
        
        try{
            if( self::$boardcf->get_count($key, $slice) < 1 ){
                return $board ;
            }
            $result = self::$boardcf->get($key, $slice) ;
            
            $mapping = function( $row ) {
                return unserialize($row[1]) ;
            };
            
            return $board->trips = array_map( $mapping, $result ) ;
            
        }catch( exception $e ){
            // TODO add error handling pl0x
            echo $e ; exit ;
        }
        
    }
}
 
?>
