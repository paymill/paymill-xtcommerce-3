<?php
require_once('abstract/WebHooksAbstract.php');
class WebHooks extends WebHooksAbstract
{
    /**
     * Returns the list of events to be created
     * @return array
     */
    function getEventList(){
        $eventList = array(
            xtc_href_link('ext/modules/payment/paymill/events/WebhookListener.php', '&action=chargeback', 'SSL', false, false) => 'chargeback.executed',
            xtc_href_link('ext/modules/payment/paymill/events/WebhookListener.php', '&action=refund', 'SSL', false, false) => 'refund.succeeded'
        );

        return $eventList;
    }
}