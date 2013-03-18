<?php
require dirname(__FILE__).'/dibs_api/pw/dibs_pw_api.php';
class DIBSPw_Order extends DIBSPw_Order_parent {
  
  // DIBS api object stored here.
  protected $DIBSPwObj;
  
  public function __construct() {  
    $this->DIBSPwObj = new dibs_pw_api();   
  }
  
  /*
   * Overload system order::_getNextStep() method for handle Payment process
   * @param mixed $iSuccess status of finilizig order process bafore 
   * last step (thank you) or failure wit error 
   * 
   * @return null 
   */
  protected function _getNextStep($iSuccess)
  {
    
    $oOrder = oxNew('oxorder');
    
    // check if the payment method is DIBS (payment id == 'dibspw') 
    // load order information from session
    $oOrder->load(oxSession::getVar('sess_challenge')); 
    if($oOrder->load(oxSession::getVar('sess_challenge')) && $this->getBasket()->getPaymentId() == 'dibspw') {
        // Payment successfully completed. Change orer status and 
        // invoke parent::_getNextStep();
        if ($iSuccess === 'DIBSPayOK') {
               $oxOrder = unserialize(oxSession::getInstance()->getVar('oxuserobj'));
               $oxEmail = oxNew( 'oxemail' );
               if(true === $oxEmail->sendOrderEMailToUser($oxOrder)) {
                  oxSession::getInstance()->deleteVar('oxuserobj'); 
                  $iSuccess   = 1;
                } else {
                    $iSuccess = 0;
                }
                
                // send email to owner
                if ($oxEmail->sendOrderEMailToOwner( $oxOrder )){}
                
                $oOrder->oxorder__oxtransstatus = new oxField('OK');
                $oOrder->oxorder__oxpaid = new oxField(date("Y-m-d G:i:s"));
                $oOrder->save(); 
                return parent::_getNextStep($iSuccess);  
            }
        
        // Redirect to DIBS PAyment Service IF $iSuccess status in (1,0,3) 
        // 1 - email sent is ok 
        // 0 - email sent is failure but the rest is ok 
        // 3 - it's a repeated order 
        // In it's case we can process with DIBS Payment
        if (is_numeric($iSuccess) && (in_array($iSuccess,array(1,0,3)))) {
            
            // Get Basket  
            $oBasket = $this->getBasket();
            $oBasket->ordNum = $oOrder->oxorder__oxordernr->getRawValue();
    
            // Get parameters for DIBS Payment Service
            $params = $this->DIBSPwObj->api_dibs_get_requestFields($oBasket);
            
            // Mark order as 'Not completed' in DB           
            $oOrder->oxorder__oxtransstatus = new oxField('Not completed');
            $oOrder->save();
             
            // Send request to DIBS, just POST via HTML Form
            $params['dibsurl'] = $this->DIBSPwObj->api_dibs_get_formAction();
            $this->sendRequest($params); 
               
        } else { // Else another status means error, invoke  parent::_getNextStep($iSuccess) 
                 // function. Some error. 
            return parent::_getNextStep($iSuccess);  
        }
            
    } else { // If it is not disb request just invoke 
             // parent::_getNextStep($iSuccess) function
             return parent::_getNextStep($iSuccess);  
    }
     
  }
  
    /*
    * Create and send form by POST to DIBS Payment Service
    */
    private function sendRequest( $params ) {
        $prefix = "<form id=\"paymentform\" method=\"post\" action=\"".$this->DIBSPwObj->api_dibs_get_formAction()."\">";
        $out = '';
        foreach ($params as $key=>$value) {
            $out .= "<input type=\"hidden\" name=\"$key\" value=\"$value\">";
        }
        $postfix = "</form> <script type=\"text/javascript\">document.getElementById(\"paymentform\").submit();</script>";
        $output = $prefix.$out.$postfix;
        echo $output;
    } 
  
    /*
     * Succesfll payment process. Return from DIBS Payment Window. 
     */
    public function processDIBSPayReturn() {
        $oOrder = oxNew('oxorder');
        $oOrder->load($this->DIBSPwObj->helper_getOrderId($_POST['orderid']));    
        $error = $this->DIBSPwObj->api_dibs_action_success($oOrder);    
        if( empty($error) ) { 
            $iSuccess = 'DIBSPayOK';
            return $this->_getNextStep($iSuccess); 
        } else {
            
        }
    }

    /*
     * Procss of cancelling order.
     */
    public function processDIBSPayCancel() {
        $oOrder = oxNew('oxorder');
        $oOrder->load($this->DIBSPwObj->helper_getOrderId($_POST['orderid']));        
        $oBasket = $this->getSession()->getBasket();
        $oBasket->deleteBasket();
        $oOrder->cancelOrder();
        $oOrder->oxorder__oxtransstatus = new oxField('Cancelled');
        $oOrder->oxorder__oxpaid = new oxField(date("Y-m-d G:i:s"));
        $oOrder->save(); 
        
        // delete it from the session
        $oBasket->deleteBasket();
        oxSession::deleteVar( 'sess_challenge' );
        $this->DIBSPwObj->api_dibs_action_cancel();
        
    }
    /*
     * Process of Server to Server callback after payment
     */ 
     public function processCallback() {
        $oOrder = oxNew('oxorder');
        $oOrder->load($this->DIBSPwObj->helper_getOrderId($_POST['orderid']));    
        $this->DIBSPwObj->api_dibs_action_callback($oOrder);
   }


}
?>
