<?php
require_once ('includes/application_top.php');
class xtc_toolbox
{
    /**
     * Executes sql query
     *
     * @param $sql
     *
     * @return resource
     */
    function dbQuery($sql)
    {
        return xtc_db_query($sql);
    }

    /**
     * Executes sql statements returning an array
     * @param $sql
     *
     * @return array|bool|mixed
     */
    function dbFetchArray($sql)
    {
        return xtc_db_fetch_array(xtc_db_query($sql));
    }

    /**
     * Returns the name of the Fast Checkout Table as a string
     * @return string
     */
    function getFastCheckoutTableName()
    {
        return "pi_paymill_fastcheckout";
    }

    /**
     * Returns an event-url as a string
     *
     * @param String $eventName
     *
     * @return string
     */
    function getEvent($eventName)
    {
        return xtc_href_link('ext/modules/payment/paymill/events/WebhookListener.php', '&action='.$eventName, 'SSL', false, false);
    }

    /**
     * Returns the list of events to be created
     * @return array
     */
    function getEventList(){
        $eventList = array(
            $this->getEvent('chargeback') => 'chargeback.executed',
            $this->getEvent('refund') => 'refund.succeeded'
        );

        return $eventList;
    }
}