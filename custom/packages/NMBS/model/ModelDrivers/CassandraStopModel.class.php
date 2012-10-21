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

class CassandraStopModel extends StopModelDriver{
	
    private static $limit    = 200 ;
	private static $stopcf   = NULL ;
	
	public function __construct(){
		C::setup();
		self::$stopcf = C::getColumnFamily('stops');
	}
    
    /*
     * @param string $country (3 letter countrycode)
     * @param string $company
     * @param object $stops or array of objects.
     *        objects should have at least a public 'id' attribute
     */
    public function save( $country, $company, $stops ){
        
        $stops = !is_array($stops) ? array($stops) : $stops ;
        
        $mapping = function($stop) {
            if( !isset($stop->sid) ){
                var_dump( $stop ); echo " doesn't have an 'id' attribute." ; exit ;
            }
            return array( $stop->sid, serialize( $stop) );
        };
        
        $stopdata = array_map($mapping, $stops);
        //return $stopdata ;
        $key = array( $country, $company );
        try{
            self::$stopcf->insert( $key, $stopdata );
        }catch( exception $e ){
            // improve error handling pl0x
            echo $e ; exit ;
        }
    }
    
    /*
     * @param string $country
     * @param string $company
     * @param uint32 $id
     * @return array( objects )
     */
    public function get( $country, $company, $sid = NULL ){
        
        // TODO add some caching here.
        if( $this->count_stops($country, $company, $sid ) < 1 ){
            return array();
        }
        // set row key
        $key = array($country, $company);
        
        // prepare columnslice
        if( $sid === NULL ){
            $slice = new ColumnSlice('','', self::$limit);
        }else{
            $slice = new ColumnSlice(array($sid), array($sid));
        }
        
        try{ // this can go wrong
            $results = self::$stopcf->get($key,$slice);
            $unpacking = function( $stop ) {
                return unserialize( end( $stop ) ) ;
            };
            return array_map( $unpacking, $results );
        }catch( exception $e ){
            // improve error handling pl0x
            echo $e ; exit ;
        }
    }
    
    /*
     * @param string $country
     * @param string $company
     * @param uint32 $id
     * @return boolean
     */
    public function exists( $country, $company, $id ){
        return ( $this->count_stops($country, $company, $id ) > 0 ) ;
    }
    
    /*
     * @param string $country
     * @param string $company
     * @param uint32 $id
     * @return uint32
     */
    private function count_stops( $country, $company, $id = NULL ){
        // set row key
        $key = array($country, $company);
        
        // prepare columnslice
        if( $id === NULL ){
            $slice = new ColumnSlice('','', self::$limit);
        }else{
            $slice = new ColumnSlice(array($id), array($id));
        }
        try{
            return self::$stopcf->get_count( $key, $slice );
        }catch( exception $e ){
            // improve error handling pl0x
            echo $e ; exit ;
        }
        return 0 ;
    }
}
 
?>
