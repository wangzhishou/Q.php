<?php
/**
 * Loader class file.
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @link http://www.php.com/
 * @copyright Copyright &copy; 2009 Leng Sheng Hong
 * @license http://www.php.com/license
 */

/**
 * A class that provides shorthand functions to access/load files on the server.
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @version $Id: Controller.php 1000 2009-07-7 18:27:22
 * @package .uri
 * @since 1.0
 */
class Loader {

    /**
     * short hand of Q::loadCore()
     * @param string $class_name
     */
    public function core($class_name){
        Q::loadCore($class_name);
    }

    /**
     * short hand of Q::autoload()
     * @param string $class_name
     */
    public function autoload($class_name){
        Q::autoload($class_name);
    }

    /**
     * short hand of Q::loadHelper()
     * @param string $class_name
     * @param bool $createObj
     * @return mixed If $createObj is True, it returns the created Object
     */
    public function helper($class_name, $createObj=false){
        return Q::loadHelper($class_name, $createObj);
    }

    /**
     * short hand of Q::loadClass()
     * @param string $class_name
     * @param bool $createObj
     * @return mixed If $createObj is True, it returns the created Object
     */
    public function classes($class_name, $createObj=false){
        return Q::loadClass($class_name, $createObj);
    }

    /**
     * short hand of Q::loadController()
     * @param string $class_name
     */
    public function controller($class_name){
        Q::loadController($class_name);
    }

    /**
     * short hand of Q::loadModel()
     * @param string $class_name
     * @param bool $createObj
     * @return mixed If $createObj is True, it returns the created Object
     */
    public function model($class_name, $createObj=false){
        return Q::loadModel($class_name, $createObj);
    }

    /**
     * Reads a file and send a header to force download it.
     * @param string $file_str File name with absolute path to it
     * @param bool $isLarge If True, the large file will be read chunk by chunk into the memory.
     * @param string $rename Name to replace the file name that would be downloaded
     */
    public function download($file, $isLarge=FALSE, $rename=NULL){
        if(headers_sent())return false;
        
        if($rename==NULL){
            if(strpos($file, '/')===FALSE && strpos($file, '\\')===FALSE)
                $filename = $file;
            else{
                $filename = basename($file);
            }
        }else{
            $filename = $rename;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();

        if($isLarge)
            $this->readfile_chunked($file);
        else
            readfile($file);
    }

    /**
     * Read a file and display its content chunk by chunk
     * @param string $filename
     * @param bool $retbytes
     * @return mixed
     */
    private function readfile_chunked($filename, $retbytes = TRUE, $chunk_size = 1024) {
        $buffer = '';
        $cnt =0;
        // $handle = fopen($filename, 'rb');
        $handle = fopen($filename, 'rb');
        if ($handle === false) {
            return false;
        }
        while (!feof($handle)) {
            $buffer = fread($handle, $chunk_size);
            echo $buffer;
            ob_flush();
            flush();
            if ($retbytes) {
                $cnt += strlen($buffer);
            }
        }
        $status = fclose($handle);
        if ($retbytes && $status) {
            return $cnt; // return num. bytes delivered like readfile() does.
        }
        return $status;
    }
}
