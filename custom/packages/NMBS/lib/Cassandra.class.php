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
    require_once( __DIR__ . '/ClusterConfig.class.php');
    
    /**
     * use : namespacez
     */
    use phpcassa\Connection\ConnectionPool ;
    use phpcassa\ColumnSlice ;
    use phpcassa\ColumnFamily ;
    
    class C {
        private static $pool = NULL ;
        private static $init = FALSE ; // is it initialized?
        
        /**
         * @param array $servers array of ip adresses formatted as w.x.y.z
         * @param string $keyspace name of the cassandra keyspace
         * @param mixed $credentials array( username => $username, password => $password )
         * Run this function once. Next time it will be ignored.
         * Leave parameters open if you want to setup from config file 'ClusterConfig.class.php'
         */
        public static function setup( $servers = NULL, $keyspace = NULL, $credentials = NULL ){
            if( self::$init ){
                return FALSE ;
                // or throw some vague exception...
            }
            self::$init = TRUE ;
            
            if( is_null($servers) && is_null($keyspace) && is_null($credentials) ){
                $servers = ClusterConfig::$SERVERS ;
                $credentials = array( 'username' => ClusterConfig::USERNAME,
                                      'password' => md5(ClusterConfig::PASSWD)
                                    );
                $keyspace = ClusterConfig::KEYSPACE ;
            }
            self::$pool = new ConnectionPool(   $keyspace, $servers, NULL, ConnectionPool::DEFAULT_MAX_RETRIES, 
                                                5000, 5000, ConnectionPool::DEFAULT_RECYCLE, // some default settings
                                                $credentials ); // and our configured credentials
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
