<?php

class FastCheckout
{
    private $_fastCheckoutFlag = false;
    
    public function canCustomerFastCheckoutCcTemplate($userId)
    {
        $flag = 'false';
        if ($this->canCustomerFastCheckoutCc($userId)) {
            $flag = 'true';
        }
        
        return $flag;
    }    
    
    public function canCustomerFastCheckoutElvTemplate($userId)
    {
        $flag = 'false';
        if ($this->canCustomerFastCheckoutElv($userId)) {
            $flag = 'true';
        }
        
        return $flag;
    }    
    
    public function canCustomerFastCheckoutCc($userId)
    {   
        return $this->hasCcPaymentId($userId) && $this->_fastCheckoutFlag;
    }
    
    public function canCustomerFastCheckoutElv($userId)
    {   
        return $this->hasElvPaymentId($userId) && $this->_fastCheckoutFlag;
    }
    
    public function saveCcIds($userId, $newClientId, $newPaymentId)
    {
        if ($this->_canUpdate($userId)) {
            $sql = "UPDATE `pi_paymill_fastcheckout`SET `paymentID_CC` = '$newPaymentId' WHERE `userID` = '$userId'";
        } else {
            $sql = "INSERT INTO `pi_paymill_fastcheckout` (`userID`, `clientID`, `paymentID_CC`) VALUES ('$userId', '$newClientId', '$newPaymentId')";
        }

        tep_db_query($sql);
    }
    
    public function saveElvIds($userId, $newClientId, $newPaymentId)
    {   
        if ($this->_canUpdate($userId)) {
            $sql = "UPDATE `pi_paymill_fastcheckout`SET `paymentID_ELV` = '$newPaymentId' WHERE `userID` = '$userId'";
        } else {
            $sql = "INSERT INTO `pi_paymill_fastcheckout` (`userID`, `clientID`, `paymentID_ELV`) VALUES ('$userId', '$newClientId', '$newPaymentId')";
        }
        
       tep_db_query($sql);
    }
    
    private function _canUpdate($userId)
    {
        $data = $this->loadFastCheckoutData($userId);
        return $data;
    }
    
    public function loadFastCheckoutData($userId)
    {
        $sql = "SELECT * FROM `pi_paymill_fastcheckout` WHERE `userID` = '$userId'";
        
        return tep_db_fetch_array(tep_db_query($sql));
    }
    
    public function hasElvPaymentId($userId)
    {
        $data = $this->loadFastCheckoutData($userId);
        return $data && array_key_exists('paymentID_ELV', $data) && !empty($data['paymentID_ELV']);
    }
    
    public function hasCcPaymentId($userId)
    {
        $data = $this->loadFastCheckoutData($userId);
        
        return $data && array_key_exists('paymentID_CC', $data) && !empty($data['paymentID_CC']);
    }

    public function setFastCheckoutFlag($fastCheckoutFlag)
    {
        $this->_fastCheckoutFlag = $fastCheckoutFlag;
    }
    
}