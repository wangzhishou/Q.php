<?php
/**
 *  class file.
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @link http://www.php.com/
 * @copyright Copyright &copy; 2009 Leng Sheng Hong
 * @license http://www.php.com/license
 * @version $Id: WebApp.php 1000 2009-06-22 18:27:22
 * @package 
 * @since 1.0
 */

/**
 *  is a singleton class serving common framework functionalities.
 *
 * You can access  in every class to retrieve configuration settings,
 * DB connections, application properties, logging, loader utilities and etc.
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @version $Id: .php 1000 2009-07-7 18:27:22
 * @package 
 * @since 1.0
 */
class Q {
    protected static $_app;
    protected static $_conf;
    protected static $_logger;
    protected static $_db;
    protected static $_useDbReplicate;
    protected static $_cache;
    protected static $_acl;
	protected static $_session;
	protected static $_translator;
    protected static $_globalApps;

    /**
     * @return Config configuration settings defined in <i>common.conf.php</i>, auto create if the singleton has not been created yet.
     */
    public static function conf(){
        if(self::$_conf===NULL){
            self::$_conf = new Config;
        }
        return self::$_conf;
    }
    
    /**
     * Set the list of  applications. 
     * <code>
     * //by default, Q::loadModelFromApp() will load from this application path
     * $apps['default'] = '/var/path/to/shared/app/'
     * $apps['app2'] = '/var/path/to/shared/app2/'
     * $apps['app3'] = '/var/path/to/shared/app3/' 
     * </code>
     * @param array $apps 
     */
    public static function setGlobalApps($apps){
        self::$_globalApps = $apps;
    }
    
    /**
     * Imports the definition of Model class(es) from a  application
     * @param string|array $modelName Name(s) of the Model class to be imported
     * @param string $appName Name of the application to be loaded from
     * @param bool $createObj Determined whether to create object(s) of the class
     * @return mixed returns NULL by default. If $createObj is TRUE, it creates and return the Object(s) of the class name passed in.
     */
    public static function loadModelFromApp($modelName, $appName='default', $createObj=false){
        return self::load($modelName, self::$_globalApps[$appName] . 'model/', $createObj);
    }
    
    /**
     * Imports the definition of User defined class(es) from a  application
     * @param string|array $className Name(s) of the Model class to be imported
     * @param string $appName Name of the application to be loaded from
     * @param bool $createObj Determined whether to create object(s) of the class
     * @return mixed returns NULL by default. If $createObj is TRUE, it creates and return the Object(s) of the class name passed in.
     */
    public static function loadClassFromApp($className, $appName='default', $createObj=false){
        return self::load($className, self::$_globalApps[$appName] . 'class/', $createObj);
    }
    
    /**
     * Imports the definition of Controller class from a  application
     * @param string $class_name Name of the class to be imported
     */
    public static function loadControllerFromApp($controllerName, $appName='default'){
        return self::load($controllerName, self::$_globalApps[$appName] . 'controller/');
    }

    /**
	 * @param string $appType The type of application you want. Options are: 'WebApp' and 'CliApp'
     * @return WebApp|CliApp the application singleton, auto create if the singleton has not been created yet.
     */
    public static function app($appType='WebApp'){
        if(self::$_app===NULL){
            self::loadCore('app/' . $appType);
            self::$_app = new $appType();
        }
        return self::$_app;
    }

    /**
	 * @param string $class the class to use for ACL. Can be Acl or RbAcl
     * @return Acl|RbAcl the application ACL singleton, auto create if the singleton has not been created yet.
     */
    public static function acl($class = 'Acl'){
        if(self::$_acl===NULL){
            self::loadCore('auth/' . $class);
            self::$_acl = new $class;
        }
        return self::$_acl;
    }

    /**
     * Call this method to use database replication instead of a single db server.
     */
    public static function useDbReplicate(){
        self::$_useDbReplicate = true;
    }

    /**
     * @return SqlMagic the database singleton, auto create if the singleton has not been created yet.
     */
    public static function db(){
        if(self::$_db===NULL){
            if(self::$_useDbReplicate===NULL){
                self::loadCore('db/SqlMagic');
                self::$_db = new SqlMagic;
            }else{
                self::loadCore('db/MasterSlave');
                self::$_db = new MasterSlave;
            }
        }

        if(!self::$_db->connected)
            self::$_db->connect();

        return self::$_db;
    }

    /**
     * @return Session
     */
    public static function session($namespace = null){
        if(self::$_session===NULL){
            self::loadCore('session/Session');
            self::$_session = new Session($namespace);
        }
        return self::$_session;
    }
	
	/**
     * @return true/false according to cache system being installed
     */
    public static function cacheSession($prefix = 'Session/', $type='file'){
		$cache = self::cache($type);
		self::loadCore('session/CacheSession');
		return CacheSession::installOnCache($cache, $prefix);
    }

	 /**
	  * @return Translator
	  */
    public static function translator($adapter, $data, $options=array()) {
        if(self::$_translator===NULL){
            self::loadCore('translate/Translator');
            self::$_translator = new Translator($adapter, $data, $options);
        }
        return self::$_translator;
    }

	/**
	 * Simple accessor to  Translator class. You must be sure you have initialised it before calling. See translator(...)
	 * @return Translator
	 */
	public static function getTranslator() {
		return self::$_translator;
	}

    /**
     * @return Log logging tool for logging, tracing and profiling, singleton, auto create if the singleton has not been created yet.
     */
    public static function logger(){
        if(self::$_logger===NULL){
            self::loadCore('logging/Log');
            self::$_logger = new Log(self::conf()->DEBUG_ENABLED);
        }
        return self::$_logger;
    }

    /**
     * @param string $cacheType Cache type: file, php, front, apc, memcache, xcache, eaccelerator. Default is file based cache.
     * @return FileCache|PhpCache|FrontCache|ApcCache|MemCache|XCache|EAcceleratorCache file/php/apc/memcache/xcache/eaccelerator & frontend caching tool, singleton, auto create if the singleton has not been created yet.
     */
    public static function cache($cacheType='file') {
        if($cacheType=='file'){
            if(isset(self::$_cache['file']))
                return self::$_cache['file'];

            self::loadCore('cache/FileCache');
            self::$_cache['file'] = new FileCache;
            return self::$_cache['file'];
        }
        else if($cacheType=='php'){
            if(isset(self::$_cache['php']))
                return self::$_cache['php'];

            self::loadCore('cache/PhpCache');
            self::$_cache['php'] = new PhpCache;
            return self::$_cache['php'];
        }
        else if($cacheType=='front'){
            if(isset(self::$_cache['front']))
                return self::$_cache['front'];

            self::loadCore('cache/FrontCache');
            self::$_cache['front'] = new FrontCache;
            return self::$_cache['front'];
        }
        else if($cacheType=='apc'){
            if(isset(self::$_cache['apc']))
                return self::$_cache['apc'];

            self::loadCore('cache/ApcCache');
            self::$_cache['apc'] = new ApcCache;
            return self::$_cache['apc'];
        }
        else if($cacheType=='xcache'){
            if(isset(self::$_cache['xcache']))
                return self::$_cache['xcache'];

            self::loadCore('cache/XCache');
            self::$_cache['xcache'] = new XCache;
            return self::$_cache['xcache'];
        }
        else if($cacheType=='eaccelerator'){
            if(isset(self::$_cache['eaccelerator']))
                return self::$_cache['eaccelerator'];

            self::loadCore('cache/EAcceleratorCache');
            self::$_cache['eaccelerator'] = new EAcceleratorCache;
            return self::$_cache['eaccelerator'];
        }
        else if($cacheType=='memcache'){
            if(isset(self::$_cache['memcache']))
                return self::$_cache['memcache'];

            self::loadCore('cache/MemCache');
            self::$_cache['memcache'] = new MemCache(Q::conf()->MEMCACHE);
            return self::$_cache['memcache'];
        }
    }

    /**
     * Imports the definition of class(es) and tries to create an object/a list of objects of the class.
     * @param string|array $class_name Name(s) of the class to be imported
     * @param string $path Path to the class file
     * @param bool $createObj Determined whether to create object(s) of the class
     * @return mixed returns NULL by default. If $createObj is TRUE, it creates and return the Object of the class name passed in.
     */
    protected static function load($class_name, $path, $createObj=FALSE){
        if(is_string($class_name)===True){
			$pure_class_name = basename($class_name);
            class_exists($pure_class_name, false)===True || require_once($path . "$class_name.php");
            if($createObj)
                return new $pure_class_name;
        }else if(is_array($class_name)===True){
            //if not string, then a list of Class name, require them all.
            //make sure the class_name has array with is_array
            if($createObj)
                $obj=array();

            foreach ($class_name as $one) {
				$pure_class_name = basename($one);
                class_exists($pure_class_name, false)===True || require_once($path . "$one.php");
                if($createObj)
                    $obj[] = new $pure_class_name;
            }

            if($createObj)
                return $obj;
        }
    }

    /**
     * Imports the definition of User defined class(es). Class file is located at <b>SITE_PATH/protected/class/</b>
     * @param string|array $class_name Name(s) of the class to be imported
     * @param bool $createObj Determined whether to create object(s) of the class
     * @return mixed returns NULL by default. If $createObj is TRUE, it creates and return the Object(s) of the class name passed in.
     */
    public static function loadClass($class_name, $createObj=FALSE){
        return self::load($class_name, self::conf()->SITE_PATH . Q::conf()->PROTECTED_FOLDER . "class/", $createObj);
    }

    /**
     * Imports the definition of Controller class. Class file is located at <b>SITE_PATH/protected/controller/</b>
     * @param string $class_name Name of the class to be imported
     */
    public static function loadController($class_name){
		return self::load($class_name, self::conf()->SITE_PATH . Q::conf()->PROTECTED_FOLDER . 'controller/', false);
    }

    /**
     * Imports the definition of Model class(es). Class file is located at <b>SITE_PATH/protected/model/</b>
     * @param string|array $class_name Name(s) of the Model class to be imported
     * @param bool $createObj Determined whether to create object(s) of the class
     * @return mixed returns NULL by default. If $createObj is TRUE, it creates and return the Object(s) of the class name passed in.
     */
    public static function loadModel($class_name, $createObj=FALSE){
        return self::load($class_name, self::conf()->SITE_PATH . Q::conf()->PROTECTED_FOLDER . 'model/', $createObj);
    }

    /**
     * Imports the definition of Helper class(es). Class file is located at <b>BASE_PATH/protected/helper/</b>
     * @param string|array $class_name Name(s) of the Helper class to be imported
     * @param bool $createObj Determined whether to create object(s) of the class
     * @return mixed returns NULL by default. If $createObj is TRUE, it creates and return the Object(s) of the class name passed in.
     */
    public static function loadHelper($class_name, $createObj=FALSE){
        return self::load($class_name, self::conf()->BASE_PATH ."helper/", $createObj);
    }

    /**
     * Imports the definition of  framework core class. Class file is located at <b>BASE_PATH</b>.
     * @example If the file is in a package, called <code>loadCore('auth/Log')</code>
     * @param string $class_name Name of the class to be imported
     */
    public static function loadCore($class_name){
        require_once self::conf()->BASE_PATH ."$class_name.php";
    }

    /**
     * Imports the definition of Model class(es) in a certain module or from the main app.
     *
     * @param string|array $class_name Name(s) of the Model class to be imported
     * @param string $path module folder name. Default is the main app folder.
     * @param bool $createObj Determined whether to create object(s) of the class
     * @return mixed returns NULL by default. If $createObj is TRUE, it creates and return the Object(s) of the class name passed in.
     */
    public static function loadModelAt($class_name, $moduleFolder=Null, $createObj=FALSE){
        if($moduleFolder===null){
            $moduleFolder = Q::getAppPath();
        }else{
            $moduleFolder = Q::getAppPath() . 'module/' . $moduleFolder;            
        }
        return self::load($class_name, $moduleFolder . "/model/", $createObj);
    }

    /**
     * Imports the definition of Controller class(es) in a certain module or from the main app.
     *
     * @param string|array $class_name Name(s) of the Controller class to be imported
     * @param string $path module folder name. Default is the main app folder.
     */
    public static function loadControllerAt($class_name, $moduleFolder=Null){
        if($moduleFolder===null){
            $moduleFolder = Q::getAppPath();
        }else{
            $moduleFolder = Q::getAppPath() . 'module/' . $moduleFolder;            
        }        
		require_once $moduleFolder . '/controller/'.$class_name.'.php';
    }

    /**
     * Imports the definition of User defined class(es) in a certain module or from the main app.
     *
     * @param string|array $class_name Name(s) of the class to be imported
     * @param string $path module folder name. Default is the main app folder.
     * @param bool $createObj Determined whether to create object(s) of the class
     * @return mixed returns NULL by default. If $createObj is TRUE, it creates and return the Object(s) of the class name passed in.
     */
    public static function loadClassAt($class_name, $moduleFolder=Null, $createObj=FALSE){
        if($moduleFolder===null){
            $moduleFolder = Q::getAppPath();
        }else{
            $moduleFolder = Q::getAppPath() . 'module/' . $moduleFolder;            
        }
        return self::load($class_name, $moduleFolder. "/class/", $createObj);
    }

    /**
     * Loads template tag class from plugin directory for both main app and modules
     * 
     * @param string $class_name Template tag class name
     * @param string $moduleFolder Folder name of the module. If Null, the class will be loaded from main app.
     */
    public static function loadPlugin($class_name, $moduleFolder=Null){
        if($moduleFolder===null){
            require_once Q::getAppPath() . 'plugin/'. $class_name .'.php';
        }else{
            require_once Q::getAppPath() .'module/'. $moduleFolder .'/plugin/'. $class_name .'.php';
        }
    }
	
    /**
     * Provides auto loading feature. To be used with the Magic method __autoload
     * @param string $classname Class name to be loaded.
     */
    public static function autoload($classname){
//        if( class_exists($classname, false) === true )
//			return;
        
        //app
		$class['Config']      = 'app/Config';
		$class['SiteMagic']   = 'app/SiteMagic';
		$class['WebApp']      = 'app/WebApp';
        
        //auth
		$class['Acl']         = 'auth/Acl';
		$class['Auth']        = 'auth/Auth';
		$class['DigestAuth']  = 'auth/DigestAuth';
		$class['RbAcl']       = 'auth/RbAcl';    
        
        //cache
		$class['ApcCache']            = 'cache/ApcCache';
		$class['EAcceleratorCache']   = 'cache/EAcceleratorCache';
		$class['FileCache']           = 'cache/FileCache';
		$class['FrontCache']          = 'cache/FrontCache';
		$class['MemCache']            = 'cache/MemCache';
		$class['PhpCache']            = 'cache/PhpCache';
		$class['XCache']              = 'cache/XCache';
            
        //controller
		$class['Controller'] = 'controller/Controller';
        
        //db
		$class['DbExpression']    = 'db/DbExpression';
		$class['MasterSlave']     = 'db/MasterSlave';
		$class['Model']           = 'db/Model';
		$class['ModelGen']        = 'db/ModelGen';
		$class['SmartModel']      = 'db/SmartModel';
		$class['SqlMagic']        = 'db/SqlMagic';
        
        //db/manage
		$class['DbUpdater']       = 'db/manage/DbUpdater';
		$class['ManageDb']        = 'db/manage/ManageDb';
		$class['ManageMySqlDb']   = 'db/manage/adapters/ManageMySqlDb';
		$class['ManagePgSqlDb']   = 'db/manage/adapters/ManagePgSqlDb';
		$class['ManageSqliteDb']  = 'db/manage/adapters/ManageSqliteDb';
        
        //helper
		$class['Benchmark']       = 'helper/Benchmark';
		$class['File']            = 'helper/File';
		$class['FlashMessenger']  = 'helper/FlashMessenger';
		$class['Form']            = 'helper/Form';
		$class['GdImage']         = 'helper/GdImage';
		$class['Mailer']          = 'helper/Mailer';
		$class['Pager']           = 'helper/Pager';
		$class['RestClient']      = 'helper/RestClient';
		$class['TextHelper']      = 'helper/TextHelper';
		$class['Timezone']        = 'helper/Timezone';
		$class['UrlBuilder']      = 'helper/UrlBuilder';
		$class['Validator']       = 'helper/Validator';
        
        //logging
		$class['Log'] = 'logging/Log';
        
        //session
		$class['CacheSession'] = 'session/CacheSession';
		$class['Session']      = 'session/Session';      
        
        //translate
		$class['Translator'] = 'translate/Translator';
        
        //uri
		$class['Loader'] = 'uri/Loader';
		$class['UriRouter'] = 'uri/UriRouter';
        
        //view
		$class['View'] = 'uri/View';
		$class['ViewBasic'] = 'uri/ViewBasic';
        
        if(isset($class[$classname]))
            self::loadCore($class[$classname]);
        else{ 
            if(isset(Q::conf()->PROTECTED_FOLDER_ORI)===true){
                $path = Q::conf()->SITE_PATH . Q::conf()->PROTECTED_FOLDER_ORI;
            }else{
                $path = Q::conf()->SITE_PATH . Q::conf()->PROTECTED_FOLDER;                            
            }
            
            if(empty(Q::conf()->AUTOLOAD)===false){
                if(Q::conf()->APP_MODE=='dev'){
                    $includeSub = Q::conf()->AUTOLOAD;
                    $rs = array();
                    foreach($includeSub as $sub){
                        if(file_exists($sub)===false){     
                            if(file_exists($path. $sub)===true){
                                $rs = array_merge($rs, File::getFilePathList($path. $sub . '/') );                
                            }
                        }else{
                            $rs = array_merge($rs, File::getFilePathList( $sub . '/') );                
                        }
                    }

                    $autoloadConfigFolder = $path . 'config/autoload/';

                    $rsExisting = null;

                    if(file_exists($autoloadConfigFolder.'autoload.php')===true){
                        $rsExisting = include($autoloadConfigFolder.'autoload.php');
                    }

                    if($rs != $rsExisting){
                        if(!file_exists($autoloadConfigFolder)){
                            mkdir($autoloadConfigFolder);
                        }
                        file_put_contents($autoloadConfigFolder.'autoload.php', '<?php return '.var_export($rs, true) . ';');                    
                    }                                
                }
                else{
                    $rs = include_once($path . 'config/autoload/autoload.php');
                }

                if( isset($rs[$classname . '.php'])===true ){
                    require_once $rs[$classname . '.php'];
                    return;
                }
            }            
            
            //autoloading namespaced class                
            if(isset(Q::conf()->APP_NAMESPACE_ID)===true && strpos($classname, '\\')!==false){
                $pos = strpos($classname, Q::conf()->APP_NAMESPACE_ID);
                if($pos===0){
                    $classname = str_replace('\\','/',substr($classname, strlen(Q::conf()->APP_NAMESPACE_ID)+1));
                    require_once $path . $classname . '.php';
                }
            }
        }
    }
    
    /**
     * Get the path where the Application source is located.
     * @return string
     */
    public static function getAppPath(){
        if(isset(Q::conf()->PROTECTED_FOLDER_ORI)===true){
            return Q::conf()->SITE_PATH . Q::conf()->PROTECTED_FOLDER_ORI;
        }else{
            return Q::conf()->SITE_PATH . Q::conf()->PROTECTED_FOLDER;                            
        }        
    }

    /**
     * Simple benchmarking. To used this, set <code>$config['START_TIME'] = microtime(true);</code> in <i>common.conf.php</i> .
     * @param bool $html To return the duration as string in HTML comment.
     * @return mixed Duration(sec) of the benchmarked process. If $html is True, returns string <!-- Generated in 0.002456 seconds -->
     */
    public static function benchmark($html=false){
        if(!isset(self::conf()->START_TIME)){
            return 0;
        }
        $duration = microtime(true) - self::conf()->START_TIME;
        if($html)
            return '<!-- Generated in ' . $duration . ' seconds -->';
        return $duration;
    }

    public static function powerby(){
        return 'Powered by <a href="http://www.php.com/">PHP Framework</a>.';
    }

    public static function version(){
        return '1.4.1';
    }
}
