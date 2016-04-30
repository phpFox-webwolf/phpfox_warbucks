<?php

/*
 * This class performs service operations including
 * Database calls
 * Pieced together by Webwolf
 */

class warbucks_service
{

    private $_validSettings='';
    private $_errors=array();
    private $_variables=array();

    function __construct($_CONF)
    {
        $this->_messages='';
        $this->_CONF=$_CONF;
        $this->_oDBObj=$this->_getDbase();
        if(!is_object($this->_oDBObj))
        {
            $this->_errors[]=$this->_oDBObj;
        }
    }
    /*
     * Check for valid database object
     */
    function getInfo()
    {
        if(is_object($this->_oDBObj))
        {
            return 'true';
        }
        else
        {
            return false;
        }
    }

    /*
     * return any errors set within this class
     */
    function getErrors()
    {
        return $this->_errors;
    }

    /*
     * This function retrieves the filenames for a module from the database
     * $sModule - string
     * $sField - string
     * params are module name and field name that contains the path to the file
     */
    function getData($aModule, $sField)
    {
        $aRows=array();
        $iTMF=0;
//        echo "Processing entries from ".$aModule." module<br />";

        $res = $this->_oDBObj->query("SELECT ".$sField." FROM ".$this->_CONF['db']['prefix'].$aModule." WHERE ".$sField." IS NOT NULL");
        while ($row = $res->fetch_assoc()) {
            $iTMF++;
            $sTempRow=sprintf($row[$sField], '');
            $aRows[]=$sTempRow;
//            echo $sTempRow."<br />";
        }
        if($aModule=='user') {
            $res = $this->_oDBObj->query("SELECT image_path as ".$sField." FROM ".$this->_CONF['db']['prefix']."user_spam WHERE image_path IS NOT NULL");
    //        die("SELECT image_path FROM ".$this->_CONF['db']['prefix']." user_spam WHERE image_path IS NOT NULL");
            while ($row = $res->fetch_assoc()) {
                $iTMF++;
                $sTempRow=sprintf($row[$sField], '');
                $aRows[]='spam_question/'.$sTempRow;
    //            echo $sTempRow."<br />";
            }            
        }
//        var_dump($aRows);        
        echo "<br />".$iTMF . " Total file paths found in the ".$aModule." table<br />";
//        var_dump($aRows);
        
        return $aRows;
    }
    
    function getUnattached() {
        $resBlog = $this->_oDBObj->query("SELECT category_id, user_id, destination FROM ".$this->_CONF['db']['prefix']."attachment WHERE item_id = 0 ORDER BY category_id ASC ");
        while ($row = $resBlog->fetch_assoc()) {
            $row['destination']=sprintf($row['destination'], '');
            $aUnattached[]=$row;
        }
        return $aUnattached;
    }
    
    
    function getAttachmentData($aLocs)  {
        $aRows=array();
        $resBlog = $this->_oDBObj->query("SELECT ".$aLocs['field'].", ".$aLocs['index']." FROM ".$this->_CONF['db']['prefix']."".$aLocs['table']." WHERE ".$aLocs['field']." like '%/file/attachment%'");
        while ($row = $resBlog->fetch_assoc()) {
            $html = $row[$aLocs['field']];

            if(!$doc = new DOMDocument()) {die('PHP/DOM module Not Found.  This php module is required to parse the filenames.');}
            @$doc->loadHTML($html);

            $tags = $doc->getElementsByTagName('img');

            foreach ($tags as $tag) {
                $sImage1 = $tag->getAttribute('src');
                $sImage2 = substr($sImage1, strpos($sImage1, '/file/attachment/')+17);
                $sImage3 = str_replace('_view', '', $sImage2);
                $aRows[]=array('path'=>$sImage3, 'module'=>$aLocs['module'], "index"=>$row[$aLocs['index']]);
            }  
        }

        return $aRows;
    }
    
    function fixAttachment($aEntry)  {
        
        $oFileObj=new filetool;        
        $bGood=FALSE;
        // Get the id form attachment table of propective unpaired attachment
        $sql = "SELECT attachment_id, item_id FROM `phpfox_attachment`"
                . " WHERE `destination` = '".str_replace('.', '%s.', $aEntry['path'])
                . "' AND `category_id` = '".$aEntry['module']."'"; 

        $res=$this->_oDBObj->query($sql);
        $aAttRes = $res->fetch_assoc();
        $resId=$aAttRes['attachment_id'];
        $resItem=$aAttRes['item_id'];
//        var_dump($aEntry);
        if(empty($resId)) {
            echo '<span style="color:red;">';
            echo $aEntry['path'] . " from module ".$aEntry['module']." Item ".$aEntry['index']." not found in the attachment table<br />";
            $aFilesExist=$oFileObj->searchFile($aEntry['path'], 'attachment');
            if(empty($aFilesExist)) {
                echo "Files do not exist<br />";
            } else {
                echo "Files attached to this entry:<br />";
                foreach ($aFilesExist as $sFileExist) {
                    echo $sFileExist.'<br />';
                }
            }
            echo '</span>';
            return false;
        }
        if($resItem == 0) {
            // Set the item_id of the identified entry
            $sql = "UPDATE `phpfox_attachment`"
            . " SET `item_id`= '".$aEntry['index']
            . "' WHERE `attachment_id` = '".$resId."';";
            $bGood=$this->_oDBObj->query($sql);
            $sTable = (empty($aEntry['add']))?$aEntry['module']:$aEntry['module'].'_'.$aEntry['add'];
            $sField = (empty($aEntry['add']))?$aEntry['module'].'_id':$aEntry['add'].'_id';
            // Update the module table to increment total_attachment field
            $sql = "UPDATE `phpfox_".$sTable."` "
                    ." SET total_attachment = total_attachment + 1 "
                    ." WHERE ".$sField." = ".$aEntry['index'] .";";
            $bGood=$this->_oDBObj->query($sql);
            echo '<span style="color:#00ff99;">'.$aEntry['path'] . " attachment table entry ".$resId." item_id has been updated to ".$aEntry['index']."</span><br />";
            
        } else {
            echo $aEntry['path'] . ' is already linked to the attachment table. <br />';
        }


        if($bGood) {
            $aEntry['fixed']=$resId;
        } else {
            $aEntry['fixed']=FALSE;
        }
        return $aEntry;
    }
    
    /*
     * This initializes the database object
     */
    private function _getDbase()
    {
        $oDb = new mysqli($this->_CONF['db']['host'], $this->_CONF['db']['user'], $this->_CONF['db']['pass'], $this->_CONF['db']['name']);
        if ($oDb->connect_errno) {
            echo "Failed to connect to MySQL: " . $oDb->connect_error;
        }

        return $oDb;
    }
}
