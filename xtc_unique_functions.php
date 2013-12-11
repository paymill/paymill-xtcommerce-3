<?php
class xtc_unique_functions
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
}