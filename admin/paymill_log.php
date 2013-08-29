<?php

require_once ('includes/application_top.php');

?>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
    <tr>
        <td width="<?php echo BOX_WIDTH; ?>" valign="top">
            <?php if (CURRENT_TEMPLATE != 'xtc5'): ?>
            <table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
                <!-- left_navigation //-->
                <?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
                <!-- left_navigation_eof //-->
            </table>
            <?php endif;?>
        </td>
        <!-- body_text //-->
        <td width="100%" valign="top">
            <table border="0" width="100%" cellspacing="0" cellpadding="2">
                <tr>
                    <td>
                        <table border="0" width="100%" cellspacing="0" cellpadding="2" height="40">
                            <tr>
                                <td class="pageHeading">Log</td>
                            </tr>
                            <tr>
                                <td><img width="100%" height="1" border="0" alt="" src="images/pixel_black.gif"></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <a href="<?php echo xtc_href_link('paymill_logging.php'); ?>"><b class="button"></b></a>
                        <hr/>
                        <table border="0" width="100%" cellspacing="0" cellpadding="2">
                            <tr>
                                <th class="pageHeading">Request</th>
                            </tr>
                            <tr>
                                <td style="text-align: center;">
                                    <textarea style="width: 600px; height: 500px;">
                                        
                                    </textarea>
                                </td>
                            </tr>
                        </table>
                        <hr/>
                        <table border="0" width="100%" cellspacing="0" cellpadding="2">
                            <tr>
                                <th class="pageHeading">Response</th>
                            </tr>
                            <tr>
                                <td style="text-align: center;">
                                    <textarea style="width: 600px; height: 500px;">
                                        
                                    </textarea>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <hr/>
            <a href="<?php echo xtc_href_link('paymill_logging.php'); ?>"><b class="button"></b></a>
        </td>
    </tr>
</table>
<!-- body_eof //-->
<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br/>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>