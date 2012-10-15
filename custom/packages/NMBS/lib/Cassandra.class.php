<?php
  /**
    * This is an autoload package for cassandra nosql distributed database
    * And yes, it is inspired by redbean.
    *
    * @copyright (C) 2012 by iRail vzw/asbl
    * @license AGPLv3
    * @author Hannes Van De Vreken <hannes aŧ irail.be>
     */
    
    require_once( __DIR__ . '/autoload.php');
    
    /**
     * use : namespacez
     */
    use phpcassa\Connection\ConnectionPool ;
    use phpcassa\ColumnSlice ;
    use phpcassa\ColumnFamily ;
    
    class C {
        private static $servers = NULL ;
        private static $pool = NULL ;
        private static $keyspace = NULL ;
        private static $init = FALSE ;
        
        /**
         * @param array $servers array of ip adresses formatted as w.x.y.z
         * @param string $keyspace name of the cassandra keyspace
         * 
         * Run this function once. Next time it will be ignored
         */
        public static function setup( $servers, $keyspace ){
            if( self::$init ){
                return FALSE ;
                // or throw some vague exception...
            }
            self::$init = TRUE ;
            self::$servers = $servers;
            self::$keyspace = $keyspace ;
            self::$pool = new ConnectionPool( $keyspace, $servers );
            return TRUE ;
        }
        
        /**
         * @param string $cf: name of the ColumnFamily
         * @return ColumnFamily or False
         */
        public static function getColumnFamily( $cf ){
            $columnFamily = new ColumnFamily( self::$pool, $cf );
            $columnFamily->return_format = ColumnFamily::ARRAY_FORMAT;
            $columnFamily->insert_format = ColumnFamily::ARRAY_FORMAT;
            return $columnFamily;
        }
    }
    
    
?>
