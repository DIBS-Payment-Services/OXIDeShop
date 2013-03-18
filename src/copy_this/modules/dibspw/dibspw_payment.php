<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class DIBSPw_Payment extends DIBSPw_Payment_parent {
   
    
    public function __construct() {
     
    }
    
   
    
    
    
    public function render()
    {
        parent::render();

        // remove itm from list
        unset( $this->_aViewData["sumtype"][2]);

            // all usergroups
            $oGroups = oxNew( "oxlist" );
            $oGroups->init( "oxgroups");
            $oGroups->selectString( "select * from ".getViewName( "oxgroups", $this->_iEditLang ) );

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        if ( $soxId != "-1" && isset( $soxId)) {
            // load object
            $oPayment = oxNew( "oxpayment" );
            $oPayment->loadInLang( $this->_iEditLang, $soxId );

            $oOtherLang = $oPayment->getAvailableInLangs();
            if (!isset($oOtherLang[$this->_iEditLang])) {
                // echo "language entry doesn't exist! using: ".key($oOtherLang);
                $oPayment->loadInLang( key($oOtherLang), $soxId );
            }
            $this->_aViewData["edit"] =  $oPayment;

          
            
            // remove already created languages
            $aLang = array_diff ( oxLang::getInstance()->getLanguageNames(), $oOtherLang);
            if ( count( $aLang))
                $this->_aViewData["posslang"] = $aLang;

            foreach ( $oOtherLang as $id => $language) {
                $oLang = new oxStdClass();
                $oLang->sLangDesc = $language;
                $oLang->selected = ($id == $this->_iEditLang);
                $this->_aViewData["otherlang"][$id] = clone $oLang;
            }

            // #708
            $this->_aViewData['aFieldNames'] = oxUtils::getInstance()->assignValuesFromText( $oPayment->oxpayments__oxvaldesc->value );
        }

        
        if ( oxConfig::getParameter("aoc") ) {

            $aColumns = array();
            //include 'inc/payment_main.inc.php';
            //var_dump($aColumns);
            
            $aColumns = array( 'container1' => array(    // field , table,  visible, multilanguage, ident
                                        array( 'oxtitle',  'oxgroups', 1, 0, 0 ),
                                        array( 'oxid',     'oxgroups', 0, 0, 0 ),
                                        array( 'oxid',     'oxgroups', 0, 0, 1 ),
                                        ),
                     'container2' => array(
                                        array( 'oxtitle',  'oxgroups', 1, 0, 0 ),
                                        array( 'oxid',     'oxgroups', 0, 0, 0 ),
                                        array( 'oxid',     'oxobject2group', 0, 0, 1 ),
                                        )
                    );
            
            $this->_aViewData['oxajax'] = $aColumns;

            return "popups/payment_main.tpl";
        } 

        $this->_aViewData["editor"] = $this->_generateTextEditor( "100%", 300, $oPayment, "oxpayments__oxlongdesc");

        // If Payment method is DIBS PW then add additional options to the form pameters
        if( $oPayment->oxpayments__oxid->value == "dibspw" ) {
            
            // Get params from 'oxconfig' table and pass it to the settings form.
            $cfg =  oxConfig::getInstance();
            $this->_aViewData["mrchantId_value"]  = $cfg->getShopConfVar("DIBSmerchantId", null ,"module:dibspw");
            $this->_aViewData["hmac_value"]       = $cfg->getShopConfVar("DIBShmac", null ,"module:dibspw");
            $this->_aViewData["addfee_value"]     = $cfg->getShopConfVar("DIBSaddfee", null ,"module:dibspw");
            $this->_aViewData["testMode"]         = array( array( 'name' => 'yes', 'value' => 'yes' ), 
                                                           array( 'name' => 'no', 'value'  =>  'no' ) );
            $this->_aViewData["testmode_value"]   = $cfg->getShopConfVar("DIBStestMode", null ,"module:dibspw");
            $this->_aViewData["testmode_value"]   = $cfg->getShopConfVar("DIBStestMode", null ,"module:dibspw");
            $this->_aViewData["addFee"]           = array( array( 'name' => 'yes', 'value' => 'yes' ), 
                                                           array( 'name' => 'no', 'value'  =>  'no' ) );
            $this->_aViewData["captureNow"]       = array( array( 'name' => 'yes', 'value' => 'yes' ), 
                                                           array( 'name' => 'no', 'value'  =>  'no' ) );
            $this->_aViewData["addfee_value"]     = $cfg->getShopConfVar("DIBSaddFee", null ,"module:dibspw");
            $this->_aViewData["capturenow_value"] = $cfg->getShopConfVar("DIBScaptureNow", null ,"module:dibspw");
            $this->_aViewData["paytype_value"]    = $cfg->getShopConfVar("DIBSpaytype", null ,"module:dibspw");
            $this->_aViewData["langpw"]           = array( array( 'name' => 'English', 'value' =>  'en_UK' ), 
                                                           array( 'name' => 'Danish', 'value'  =>  'da_DK' ),
                                                           array( 'name' => 'Swedish', 'value'  => 'sv_SE' ),
                                                           array( 'name' => 'Norwegian', 'value'  =>  'nb_NO' ),
                                                  ); 
            
            
            $this->_aViewData["distrType"]           = array( array( 'name' => 'email', 'value' => 'email' ), 
                                                           array( 'name' => 'paper', 'value'  =>  'paper' ) );
            
            $this->_aViewData["distrType_value"] = $cfg->getShopConfVar("DIBSdistrType", null ,"module:dibspw");
            $this->_aViewData["langpw_value"]     = $cfg->getShopConfVar("DIBSlangpw", null ,"module:dibspw");
            $this->_aViewData["account_value"]    = $cfg->getShopConfVar("DIBSaccount", null ,"module:dibspw");
            
            return "dibspw_payment_main.tpl";
       
         } else {
        
             return "payment_main.tpl";
        }
        
        
    } 
    
    public function save()
    {
       
        parent::save();
        
        
       
        $soxId = $this->getEditObjectId();
        $aParams    = oxConfig::getParameter( "editval");
        // checkbox handling
        if ( !isset( $aParams['oxpayments__oxactive']))
            $aParams['oxpayments__oxactive'] = 0;
        if ( !isset( $aParams['oxpayments__oxchecked']))
            $aParams['oxpayments__oxchecked'] = 0;

        $oPayment = oxNew( "oxpayment" );

        if ( $soxId != "-1")
            $oPayment->loadInLang( $this->_iEditLang, $soxId );
        else
            $aParams['oxpayments__oxid'] = null;
        //$aParams = $oPayment->ConvertNameArray2Idx( $aParams);

        $oPayment->setLanguage(0);
        $oPayment->assign( $aParams);

        // setting add sum calculation rules
        $aRules = (array) oxConfig::getParameter( "oxpayments__oxaddsumrules" );
        $oPayment->oxpayments__oxaddsumrules = new oxField( array_sum( $aRules ) );

        //#708
        if ( !is_array( $this->_aFieldArray))
            $this->_aFieldArray = oxUtils::getInstance()->assignValuesFromText( $oPayment->oxpayments__oxvaldesc->value );
        // build value
        $sValdesc = "";
        foreach ( $this->_aFieldArray as $oField)
            $sValdesc .= $oField->name . "__@@";

        $oPayment->oxpayments__oxvaldesc = new oxField( $sValdesc, oxField::T_RAW );
        $oPayment->setLanguage($this->_iEditLang);
        $oPayment->save();

        // set oxid if inserted
        $this->setEditObjectId( $oPayment->getId() );
        
        if( $oPayment->oxpayments__oxid->value == "dibspw" ) {
            
            // Get DIBS specific parameters form POST query and saving it to the 'oxconfig' table
            $cfg        =  oxConfig::getInstance();
            $merchantId = oxConfig::getParameter("merchantId");
            $addFee     = oxConfig::getParameter("addFee");
            $hmac       = oxConfig::getParameter("hmac");
            $testMode   = oxConfig::getParameter("testMode");
            $captureNow = oxConfig::getParameter("captureNow"); 
            $paytype    = oxConfig::getParameter("paytype"); 
            $langPw     = oxConfig::getParameter("langpw"); 
            $account    = oxConfig::getParameter("account"); 
            $distrType  = oxConfig::getParameter("distrType"); 
            // Svaing parametesr to the 'oxconfig' table
            $cfg->saveShopConfVar("str", "DIBSmerchantId" ,$merchantId, null ,"module:dibspw");
            $cfg->saveShopConfVar("str", "DIBShmac", $hmac, null,"module:dibspw");
            $cfg->saveShopConfVar("str", "DIBStestMode", $testMode, null ,"module:dibspw");
            $cfg->saveShopConfVar("str", "DIBSaddFee", $addFee, null ,"module:dibspw");
            $cfg->saveShopConfVar("str", "DIBScaptureNow", $captureNow, null ,"module:dibspw");
            $cfg->saveShopConfVar("str", "DIBSpaytype", $paytype, null, "module:dibspw" );
            $cfg->saveShopConfVar("str", "DIBSlangpw", $langPw, null, "module:dibspw");
            $cfg->saveShopConfVar("str", "DIBSaccount", $account, null ,"module:dibspw");
            $cfg->saveShopConfVar("str", "DIBSdistrType", $distrType, null ,"module:dibspw");
            
         }
         
    }
    
} 
?>
