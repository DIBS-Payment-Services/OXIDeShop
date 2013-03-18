<?php
class dibs_pw_helpers_cms {
    
    public function getConfigData($var) {
        $cfg = oxConfig::getInstance();
        return $cfg->getShopConfVar($var, null ,"module:dibspw");
         
    }
    
    
    /*
     * Performing specific operation on callback function
     */
    public function  helper_dibs_hook_callback($mOrderInfo) {
        $oOrder = oxNew('oxorder');
        $oOrder->load($_POST['orderid']);
        $oOrder->oxorder__oxtransstatus = new oxField('OK');
        $oOrder->oxorder__oxpaid = new oxField(date("Y-m-d G:i:s"));
        $oOrder->save();
    }
    
    /*
     * Get order number int istead of Oxid id str value
     */
    public function helper_getOrderId($ordNum) {
        $oDb    = new OxDb(); //oxDb::getDb(true);
        $dbObj  = $oDb->getDb(true); 
        $ordnum = $oDb->escapeString($ordNum);
        $sQuery = "SELECT `OXID` from oxorder WHERE `OXORDERNR` = {$ordnum}";
        $result = $dbObj->getOne($sQuery);
        return $result;
    }
    
}
?>