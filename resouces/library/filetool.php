<?php

/*
 * This class deals with file operations
 * Pieced together by Webwolf
 */

Class filetool
{
/*
 * Gets all files saved under the module's directory
 * $sModule - string (like 'user') must have a corrosponding folder under /file/pic 
 * except for attachments which is under /file
 */
    function getFiles($sModule)
    {
        $aResult=[];
        $iTVF=0;
        if($sModule == 'attachment') {
            $aExt=array('jpg', 'jpeg', 'gif', 'png', 'bmp', 'zip');
            $sTargetDir=PHPFOX_FULL_DIR.'file/'.$sModule;
        }
        else {
            $aExt=array('jpg', 'jpeg', 'gif', 'png', 'bmp');
            $sTargetDir=PHPFOX_FULL_DIR.'file/pic/'.$sModule;
        }
        
        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sTargetDir), RecursiveIteratorIterator::SELF_FIRST);
        foreach($objects as $object){
            if($objects->isDot() || $objects->isDir() || !in_array($object->getExtension(), $aExt)) { 
                continue;
            }

            $sResult1 = explode('_',pathinfo($object, PATHINFO_FILENAME));
            $sResult2 = $sResult1[0].'.'.$object->getExtension();
            $sResult =  substr(str_replace($sTargetDir, '', $object->getPath()).'/'.$sResult2,1);

            $iTVF++;
            if(!in_array($sResult, $aResult))
            {
                $aResult[]=$sResult;
            }
        }
//        var_dump($aResult);        
        return array($aResult, $iTVF);
    }
/*
 * Gets all versions of files based upon the base file
 * $sFile - string
 * $sModule - string
 * $sFile will look like '2016/03/xyza4238a0b923820dcc509a6f75849b.jpg'
 * This is what the database entry would look like
 * $sModule will be 'user' (or any of the other pic folders) or 'attachment'
 */
    function searchFile($sFile, $sModule)
    {
        $searchString = pathinfo($sFile, PATHINFO_FILENAME);
        if($sModule == 'attachment') {
            $sDir=PHPFOX_FULL_DIR.'file/'.$sModule.'/';
            $sDir.= pathinfo($sFile,  PATHINFO_DIRNAME)=='.'?'':pathinfo($sFile,  PATHINFO_DIRNAME).'/';
        } else {
            $sDir=PHPFOX_FULL_DIR.'file/pic/'.$sModule.'/';
            $sDir.= pathinfo($sFile,  PATHINFO_DIRNAME)=='.'?'':pathinfo($sFile,  PATHINFO_DIRNAME).'/';
        }
        $files = glob($sDir.'*.*');
        $filesFound = array();
        foreach($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);

            if(strpos($name, $searchString)!==FALSE) {
                 $filesFound[] = $file;
            } 
        }

        return $filesFound;
    }
    
    
}