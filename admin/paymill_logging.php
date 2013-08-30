<?php

require_once ('includes/application_top.php');
$logs = xtc_db_query("SELECT * FROM `pi_paymill_logging`");
?>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
<!-- header //-->
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
                                <td class="pageHeading">PAYMILL Log</td>
                            </tr>
                            <tr>
                                <td><img width="100%" height="1" border="0" alt="" src="images/pixel_black.gif"></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table>
                            <tr class="dataTableHeadingRow">
                                <th class="dataTableHeadingContent">ID</th>
                                <th class="dataTableHeadingContent">Debug</th>
                                <th class="dataTableHeadingContent">Message</th>
                                <th class="dataTableHeadingContent">Date</th>
                            </tr>
                            <?php while ($log = xtc_db_fetch_array($logs)): ?>
                            <tr class="dataTableRow">
                                <td class="dataTableContent"><?php echo $log['id']; ?></td>
                                <td class="dataTableContent"><?php echo $log['debug']; ?></td>
                                <td class="dataTableContent"><?php echo $log['message']; ?></td>
                                <td class="dataTableContent"><?php echo $log['date']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<!-- body_eof //-->
<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>