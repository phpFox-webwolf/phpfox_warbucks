<!DOCTYPE html>
<!--
Pieced together by webwolf
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>Finding Orphans</title>
    </head>
    <body>
        
        <h2>This program will display Files that no longer have a purpose on your website</h2>
        
        <form id="attach_fix" action="/warbucks/"  method="post">
            <input type="submit"  name="fix" value="Fix" />
            <h3>Click "Fix" to link unpaired attachment Files to attachment database</h3>
                <h4>
                    The fix function will Parse attachments from the text of blogs, mail messages, or forum posts.<br />
                    These will be compared to the attachment db table for records that have no item_id assigned.<br />
                    If a match is found, it will update the item_id to match the source content item.
                </h4>
        </form>        
        

        <form name="showfiles" action="/warbucks/"  method="post">
            <input type="submit"  name="show" value="Show" />
            <h3>Click "Show" to display Orphaned Files</h3>
        </form>        
        <?php
        
        // Sets flag for action
        $bDelete = filter_input(INPUT_POST,'delete')=='Delete'?TRUE:FALSE;
        $bShow = filter_input(INPUT_POST,'show')=='Show'?TRUE:FALSE;
        $bFix = filter_input(INPUT_POST,'fix')=='Fix'?TRUE:FALSE;

        defined("CONFIG_PATH")
            or define("CONFIG_PATH", dirname(__FILE__) . '/resources/'); 
        
        // Get the configuration settings for this program
        if(checkIncludes(CONFIG_PATH.'config.php'))
        {
                include_once CONFIG_PATH.'config.php';
        }

        // Get the file processing library file
        if(checkIncludes(LIBRARY_PATH.'filetool.php'))
        {
                require LIBRARY_PATH.'filetool.php';
        } 

        // Get the phpfox configuration settings
        if(checkIncludes(PHPFOX_SETTINGS.'server.sett.php'))
        {
                require PHPFOX_SETTINGS.'server.sett.php';
        } 

        // Get the service file for this program
        if(checkIncludes(LIBRARY_PATH.'service.php'))
        {
                require LIBRARY_PATH.'service.php';
        } 

        // Establish a file tool object
        $oFile=new filetool;

        // Establish a service object
        $oService = new warbucks_service($_CONF);

        // Set up an array of modules with which photos are associated 
        $aPicModules=array();
        $aPicModules['ad']='image_path';
        $aPicModules['egift']='file_path';
        $aPicModules['event']='image_path';
        $aPicModules['pages']='image_path';
        $aPicModules['marketplace']='image_path';
        $aPicModules['music_album']='image_path';
        $aPicModules['photo']='destination';
        $aPicModules['poll']='image_path';
        $aPicModules['quiz']='image_path';
        if(!defined('V4')) {
            $aPicModules['video']='image_path';
        }
        $aPicModules['user']='user_image';
        
        $aPicModules['attachment']='destination';

        $iTotalFiles=0;
        $iTotalFileSize=0;
        $iTVF=0;

        // Perform the attachment table repair actions
        if($bFix) {
            // Set up parameters for extracting attachment information from text fields
            $aAttachModules=[];
            $aAttachModules[]=array('module'=>'blog', 'table'=>'blog_text', 'field'=>'text_parsed', 'index'=>'blog_id', 'add'=>'');
            $aAttachModules[]=array('module'=>'forum', 'table'=>'forum_post_text', 'field'=>'text_parsed', 'index'=>'post_id', 'add'=>'post');
            $aAttachModules[]=array('module'=>'mail', 'table'=>'mail_text', 'field'=>'text_parsed', 'index'=>'mail_id', 'add'=>'');

            // Process each module defined above
            foreach($aAttachModules as $aAttachModule) {
                // return an array of found images
                $aTemp=$oService->getAttachmentData($aAttachModule);
                // Process each attachment found in the text fields
                foreach($aTemp as $aEntry) {
                    $aEntry['add']=$aAttachModule['add'];
                    
                    $aResults=$oService->fixAttachment($aEntry); 
                    if($aResults['fixed'] > 0) {
                        echo '<span style="color:#00ff99;">'.'Attachment ID '.$aResults['fixed'].' Has been Fixed</span><br />';
                    }
                }
            }
        }        
        
        if($bShow || $bDelete) {
            
        $iTFM=0;
        // process each module pics
        foreach($aPicModules as $module=>$field) 
        {
            // This step required because of music_album having the image field
            $aModuleP=explode('_',$module);
            // Collect database information for images in each module
            $aImages=$oService->getData($module, $field);
            // Collect filesystem information for each module
            list($aFiles, $iVf)=$oFile->getFiles($aModuleP[0]);
            $iTVF+=$iVf;
            
            // Compare filesystem to database.
            // Remove files from orphan list if database entry exists
            foreach($aFiles as $key=>$value) {
                $iTFM++;
                
                if(in_array($value, $aImages)) {
                    unset($aFiles[$key]);
                }
            }
//            var_dump($aFiles);
            // Display the orphaned files and totals
            foreach($aFiles as $sFile) {

                $aFileOrphans=$oFile->searchFile($sFile, $aModuleP[0]);
                foreach($aFileOrphans as $sFileOrphan) {
                    if($bDelete) {
                        if(unlink($sFileOrphan)) {
                            echo '<span style="color:#ccc">'.$sFileOrphan.'</span>  DELETED<br />'; 
                        } else {
                            echo "Warning: File ".$sFileOrphan." not deleted.".'<br />';
                            $iTotalFiles++;
                            $iTotalFileSize+=filesize($sFileOrphan);
                        }
                    } else {
                        echo $sFileOrphan.': '.filesize($sFileOrphan).'<br />';
                        $iTotalFiles++;
                        $iTotalFileSize+=filesize($sFileOrphan);
                    }
                }
            }
        } 
        
        echo "Out of a total of ".$iTFM." files (".$iTVF." files with size variations):<br />";
        echo "Total Orphaned Files: ".$iTotalFiles.'<br />';
        echo "Total Orphaned Files Size: ".$iTotalFileSize.'<br />'; 
}
        function checkIncludes($sPath) {

            try {
                if(!file_exists($sPath)) {
                    throw new Exception('Could not find '.$sPath);
                }
            }
            catch (Exception $e) {
                Echo '<h3>' . $e->getMessage()." in file ".$e->getFile()." at line ".$e->getLine().'</h3><br /> This file is required before the program can continue.'; 
                return FALSE;
            }
            return TRUE;
        }
        if($bShow && $iTotalFiles > 0)  {
        ?>
            <br /><br />
            <span>To delete the orphaned files, click this button.</span>
            <form name="deletefile" action="/warbucks/"  method="post">
                <input type="submit" onClick="return confirm('Do you really want to delete all of these files?');" name="delete" value="Delete" />
            </form>
        <?php
        }
        ?>
    </body>
</html>
