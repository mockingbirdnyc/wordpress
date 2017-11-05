<?php
defined("ABSPATH") or die("");

require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/ctrls/ctrl.base.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/class.scan.check.php');

/**
 * Controller for Tools 
 */
class DUP_PRO_CTRL_Tools extends DUP_PRO_CTRL_Base
{

    /**
     *  Init this instance of the object
     */
    function __construct()
    {
        add_action('wp_ajax_DUP_PRO_CTRL_Tools_runScanValidator', array($this, 'runScanValidator'));
        add_action('wp_ajax_DUP_PRO_CTRL_Tools_deleteInstallerFiles', array($this, 'deleteInstallerFiles'));
        add_action('wp_ajax_DUP_PRO_CTRL_Tools_migrationUploader', array($this, 'migrationUploader'));
    }

    /**
     * Calls the ScanValidator and returns a JSON result
     * 
     * @param string $_POST['scan-path']		The path to start scanning from, defaults to DUPLICATOR_WPROOTPATH
     * @param bool   $_POST['scan-recursive]	Recursively  search the path
     * 
     * @notes: Testing = /wp-admin/admin-ajax.php?action=DUP_PRO_CTRL_Tools_runScanValidator
     */
    public function runScanValidator($post)
    {
        @set_time_limit(0);
        $post = $this->postParamMerge($post);
        check_ajax_referer($post['action'], 'nonce');

        $result = new DUP_PRO_CTRL_Result($this);

        try {
            //CONTROLLER LOGIC
            $path = isset($post['scan-path']) ? $post['scan-path'] : DUPLICATOR_PRO_WPROOTPATH;
            if (!is_dir($path)) {
                throw new Exception("Invalid directory provided '{$path}'!");
            }
            $scanner = new DUP_PRO_ScanValidator();
            $scanner->recursion = (isset($post['scan-recursive']) && $post['scan-recursive'] != 'false') ? true : false;
            $payload = $scanner->run($path);

            //RETURN RESULT
            $test = ($payload->fileCount > 0) ? DUP_PRO_CTRL_Status::SUCCESS : DUP_PRO_CTRL_Status::FAILED;
            $result->process($payload, $test);
        } catch (Exception $exc) {
            $result->processError($exc);
        }
    }

    /**
     * Performs the upload process for site migration import
     *
     * @param action $_POST["action"]		The action to use for this request
     * @param action $_POST["nonce"]		The param used for security
     * @param action $_POST["$chunk_size"]	The byte count to read
     * @param string $_FILES["file"]["name"]
     *
     * @notes: Testing = /wp-admin/admin-ajax.php?action=DUP_PRO_CTRL_Tools_migrationUploader
     */
    public function migrationUploader($post)
    {
        @set_time_limit(0);
        $post = $this->postParamMerge($post);
        check_ajax_referer($post['action'], 'nonce');

        $result = new DUP_PRO_CTRL_Result($this);
        $store_path = DUPLICATOR_PRO_SSDIR_PATH_IMPORTS;

        $out = array();

        try {
            if (!file_exists($store_path)) {
                SnapLibIOU::mkdir($store_path);
            }

            //CONTROLLER LOGIC
            $ext_types = array('daf', 'zip');
            $archive_filename = isset($_FILES["file"]["name"]) ? $_FILES["file"]["name"] : null;
            $temp_filename = isset($_FILES["file"]["tmp_name"]) ? $_FILES["file"]["tmp_name"] : null;
            $chunk_size = isset($_POST["chunk_size"]) ? $_POST["chunk_size"] : 2024;
            $chunk_mode = isset($_POST["chunk_mode"]) ? $_POST["chunk_mode"] : 'chunk';
            $file_ext = pathinfo($archive_filename, PATHINFO_EXTENSION);


            //	$ini_upload = ini_get('upload_max_filesize');
            //	$ini_post   = ini_get('post_max_size');
            //	$ini_upload = SnapLibUtil::convertToBytes($ini_upload);
            //	$ini_post	= SnapLibUtil::convertToBytes($ini_post);

            $chunk = $_POST["chunk"];
            $chunks = $_POST["chunks"];
            $archive_filepath = "{$store_path}/" . $_FILES["file"]["name"];

            //	$out['filename']	= $file_target;
            //	$out['chunk_mode']	= $chunk_mode;
            //	$out['ini_upload']	= $ini_upload;
            //	$out['ini_post']	= $ini_post;

            if (!in_array($file_ext, $ext_types)) {
                throw new Exception("Invalid file extention specified. Please use '.daf' or '.zip'!");
            }

            //CHUNK MODE
            if ($chunk_mode == 'chunked') {

                $output = @fopen($archive_filepath . ".part", $chunks ? "ab" : "wb");
                $input = @fopen($temp_filename, "rb");

                if ($output === false) {
                    throw new Exception('Could not write output: ' . $archive_filepath);
                }

                if ($input === false) {
                    throw new Exception('Could not read input:' . $temp_filename);
                }

                while ($buffer = fread($input, $chunk_size)) {
                    fwrite($output, $buffer);
                }

                fclose($output);
                fclose($input);

                $out['mode'] = 'chunk';
                $out['status'] = 'chunking';

                if ($chunk == $chunks - 1) {
                    
           /*RSR TODO
                    // Now that it's been uploaded extract the installer.
                    $simple_file_name = pathinfo($archive_filename, PATHINFO_FILENAME);
                    rename($archive_filepath . ".part", $archive_filepath);

                    $installer_filepath = DUPLICATOR_PRO_SSDIR_PATH_IMPORTS . '/installer-backup.php';
                    $new_installer_filepath = str_replace('_archive', '_installer', $simple_file_name);
                    $new_installer_filepath = DUPLICATOR_PRO_SSDIR_PATH_IMPORTS . "/{$new_installer_filepath}.php";

                    if(file_exists($installer_filepath)) {
                        SnapLibIOU::rm($installer_filepath);
                    }

                    $relativeFilepaths = array();
                    $relativeFilepaths[] = 'installer.php';

                    if($file_ext == 'zip') {

                        // RSR TODO: Smart zip extract where the appropriate mechanism is auto chosen
                        DUP_PRO_Zip_U::extractFiles($archive_filename, $relativeFilepaths, $store_path);

                    } else {
                        // TODO: DupArchive expand files                        
                        DupArchiveEngine::expandFiles($archive_filename, $relativeFilepaths, $store_path);
                    }

                    if(!file_exists($new_installer_filepath)) {
                        throw new exception(DUP_PRO_U::__('Couldn’t extract backup installer from archive!'));
                    }
                    
                    SnapLibIOU::rename($new_installer_filepath, $installer_filepath);
*/
                    $out['status'] = 'upload complete';
                }

                //DIRECT MODE
            } else {
                move_uploaded_file($temp_filename, $archive_filepath);
                $out['status'] = 'complete';
                $out['mode'] = 'direct';
            }

            $payload = $out;

            //RETURN RESULT
            $test = ($payload == true) ? DUP_PRO_CTRL_Status::SUCCESS : DUP_PRO_CTRL_Status::FAILED;
            $result->process($payload, $test);
        } catch (Exception $exc) {
            DUP_PRO_LOG::trace("EXCEPTION: " . $exc->getMessage());
            $result->processError($exc);
        }
    }
}
