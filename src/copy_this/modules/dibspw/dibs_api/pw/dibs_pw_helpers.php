<?php

//ini_set('display_errors',1);
//error_reporting(E_ALL);

class dibs_pw_helpers extends dibs_pw_helpers_cms implements dibs_pw_helpers_interface {

    /**
     * Flag if this module uses tax amounts instead of tax percents.
     * 
     * @var bool
     */
    public static $bTaxAmount = true;
    
    /**
     * Process write SQL query (insert, update, delete) with build-in CMS ADO engine.
     * 
     * @param string $sQuery SQL query string.
     */
    function helper_dibs_db_write($sQuery) {
        return oxDb::getDb()->Execute($sQuery);
    }
    
    /**
     * Read single value ($sName) from SQL select result.
     * If result with name $sName not found null returned.
     * 
     * @param string $sQuery SQL query string.
     * @param string $sName Name of field to fetch.
     * @return mixed 
     */
    function helper_dibs_db_read_single($sQuery, $sName) {
	    $oDb = oxDb::getDb(true);
        $result =  $oDb->getRow($sQuery);
        return $result[$sName];
    }
    
    /**
     * Return settings with CMS method.
     * 
     * @param string $sVar Variable name.
     * @param string $sPrefix Variable prefix.
     * @return string 
     */
    function helper_dibs_tools_conf($sVar, $sPrefix = 'DIBS') {
        return $this->getConfigData($sPrefix . $sVar);
    }
    
    /**
     * Return CMS DB table prefix.
     * 
     * @return string 
     */
    function helper_dibs_tools_prefix() {
        return "";
    }
    
    /**
     * Returns text by key using CMS engine.
     * 
     * @param type $sKey Key of text node.
     * @param type $sType Type of text node. 
     * @return type 
     */
    function helper_dibs_tools_lang($sKey, $sType = 'msg') {
        
    }

    /**
     * Get full CMS url for page.
     * 
     * @param string $sLink Link or its part to convert to full CMS-specific url.
     * @return string 
     */
    function helper_dibs_tools_url($sLink) {
             $cfg = oxConfig::getInstance();
             return $cfg->getShopUrl(). $sLink  ;
    }
    
    /**
     * Build CMS order information to API object.
     * 
     * @param mixed $mOrderInfo All order information, needed for DIBS (in shop format).
     * @param bool $bResponse Flag if it's response call of this method.
     * @return object 
     */
    function helper_dibs_obj_order($mOrderInfo, $bResponse = FALSE) {
        return (object)array(
                    'orderid'  => $mOrderInfo->ordNum,
                    'amount'   => $mOrderInfo->getPriceForPayment(),
                    'currency' => $mOrderInfo->getBasketCurrency()->name,
               );
    }
    
    /**
     * Build CMS each ordered item information to API object.
     * 
     * @param mixed $mOrderInfo All order information, needed for DIBS (in shop format).
     * @return object 
     */
    function helper_dibs_obj_items($mOrderInfo) {
        $aItems = array();
        $dsc = new oxDiscount();
        $totalBasePrice = new oxPrice();
        
        $prcj = new oxPrice();
        $test = 0;
        foreach($mOrderInfo->getContents() as $oItem) {
            $aItems[] = (object)array(
                'id'    => $oItem->getProductId(),
                'name'  => $oItem->getTitle(),
                'sku'   => $oItem->getProductId(),
                'price' => $oItem->getUnitPrice()->getNettoPrice(),
                'qty'   => $oItem->getAmount(),
                'tax'   => $oItem->getUnitPrice()->getBruttoPrice() - $oItem->getUnitPrice()->getNettoPrice()
            );
        }
        
        // Calculate discounts
        $fDiscount = "";
        $prcObj = new oxPrice();
        
        foreach($mOrderInfo->getDiscounts() as $d ) {
                $prcObj->add($d->dDiscount);
        }
        
        
        // Total discount 
        $fDiscount = $mOrderInfo->getTotalDiscount()->getBruttoPrice();
        $fDiscount = $prcObj->getBruttoPrice();
        
        if(!empty($fDiscount)) {
            $aItems[] = (object)array(
                'id'    => 'discount0',
                'name'  => oxLang::getInstance()->translateString("DIBSPW_TOTAL_DISCOUNT"),
                'sku'   => '',
                'price' => -$fDiscount,
                'qty'   => 1,
                'tax'   => 0
            );
        }
        
        
        if($mOrderInfo->getDeliveryCosts()) {
            //$aItems[] = $this->helper_dibs_obj_ship($mOrderInfo);
        }
        
        return $aItems;
    }
    
    /**
     * Build CMS shipping information to API object.
     * 
     * @param mixed $mOrderInfo All order information, needed for DIBS (in shop format).
     * @return object 
     */
    function helper_dibs_obj_ship($mOrderInfo) {
        return (object)array(
            'id'    => 'shipping0',
            'name'  => oxLang::getInstance()->translateString("DIBSPW_TOTAL_DELIVERY"),
            'sku'   => '',
            'price' => $mOrderInfo->getDeliveryCosts(),
            'qty'   => 1,
            'tax'   => 0
        );
    }
    
    /**
     * Build CMS customer addresses to API object.
     * 
     * @param mixed $mOrderInfo All order information, needed for DIBS (in shop format).
     * @return object 
     */
    function helper_dibs_obj_addr($mOrderInfo) {
        return (object)array(
            'shippingfirstname'  => $mOrderInfo->getBasketUser()->oxuser__oxfname->getRawValue(), 
            'shippinglastname'   => $mOrderInfo->getBasketUser()->oxuser__oxlname->getRawValue(),
            'shippingpostalcode' => $mOrderInfo->getBasketUser()->oxuser__oxzip->getRawValue(),
            'shippingpostalplace'=> $mOrderInfo->getBasketUser()->oxuser__oxcity->getRawValue(),
            'shippingaddress2'   => $mOrderInfo->getBasketUser()->oxuser__oxstreet->getRawValue(),
            'shippingaddress'    => $mOrderInfo->getBasketUser()->oxuser__oxcountryid->getRawValue(), 
            
            'billingfirstname'   => $mOrderInfo->getBasketUser()->oxuser__oxfname->getRawValue(),
            'billinglastname'    => $mOrderInfo->getBasketUser()->oxuser__oxlname->getRawValue(),
            'billingpostalcode'  => $mOrderInfo->getBasketUser()->oxuser__oxzip->getRawValue(),
            'billingpostalplace' => $mOrderInfo->getBasketUser()->oxuser__oxcity->getRawValue(),
            'billingaddress2'    => $mOrderInfo->getBasketUser()->oxuser__oxstreet->getRawValue(),
            'billingaddress'     => $mOrderInfo->getBasketUser()->oxuser__oxcountryid->getRawValue(),
            
            'billingmobile'      => $mOrderInfo->getBasketUser()->oxuser__oxfon->getRawValue(),
            'billingemail'       => $mOrderInfo->getBasketUser()->oxuser__oxusername->getRawValue()
        );
    }
    
    /**
     * Returns object with URLs needed for API, 
     * e.g.: callbackurl, acceptreturnurl, etc.
     * 
     * @param mixed $mOrderInfo All order information, needed for DIBS (in shop format).
     * @return object 
     */
    function helper_dibs_obj_urls($mOrderInfo = null) {
        return (object)array(
                    'acceptreturnurl' => "index.php?cl=order&fnc=processDIBSPayReturn",
                    'callbackurl'     => "http://ukrmodules.dibs.dk/5c65f1600b8_dcbf.php", //"index.php?cl=order&fnc=processCallback",
                    'cancelreturnurl' => "index.php?cl=order&fnc=processDIBSPayCancel", //"index.php?cl=basket",
                    'carturl'         => "customer/account/index"
                );
    }
    
    /**
     * Returns object with additional information to send with payment.
     * 
     * @param mixed $mOrderInfo All order information, needed for DIBS (in shop format).
     * @return object 
     */
    function helper_dibs_obj_etc($mOrderInfo) {
        return (object)array(
                    'sysmod'      => 'oxid_1_0_0',
                    'callbackfix' => $this->helper_dibs_tools_url("index.php?cl=order&fnc=processCallback")
                );
    }
    
    /**
     * Hook that allows to execute CMS-specific action during callback execution.
     * 
     * @param mixed $mOrderInfo All order information, needed for DIBS (in shop format).
     */
    function helper_dibs_hook_callback($oOrder) {
        $oSession = Mage::getSingleton('checkout/session');
        $oSession->setQuoteId($oSession->getDibspwStandardQuoteId(true));            
            
        if (((int)$this->helper_dibs_tools_conf('sendmailorderconfirmation', '')) == 1) {
            $oOrder->sendNewOrderEmail();
        }
            
	$this->removeFromStock((int)$_POST['orderid']);
        $this->setOrderStatusAfterPayment();
        $oSession->setQuoteId($oSession->getDibspwStandardQuoteId(true));
    }
}
?>
