<?php

 class DIBSPw_oxOrder extends DIBSPw_oxOrder_parent {
  
    protected function _sendOrderByEmail( $oUser = null, $oBasket = null, $oPayment = null )
    {
        $iRet = self::ORDER_STATE_MAILINGERROR;

        // add user, basket and payment to order
        $this->_oUser    = $oUser;
        $this->_oBasket  = $oBasket;
        $this->_oPayment = $oPayment;

        $oxEmail = oxNew( 'oxemail' );
        
        
        
        $objSerial = serialize($this);
        oxSession::getInstance()->setVar('oxuserobj',  $objSerial);
             
        if ($this->oxorder__oxpaymenttype->getRawValue() != "dibspw") {
            // send order email to user
            if ( $oxEmail->sendOrderEMailToUser( $this, "Hello man" ) ) {
                // mail to user was successfully sent
                $iRet = self::ORDER_STATE_OK;
            } 
            
            // send order email to shop owner
            $oxEmail->sendOrderEMailToOwner( $this );
        }

    

        return $iRet;
    }
    
 }

?>