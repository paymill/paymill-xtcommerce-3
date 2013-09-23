<?php

require_once ('includes/application_top.php');
require_once (DIR_FS_CATALOG . 'ext/modules/payment/paymill/lib/Services/Paymill/Log.php');

$sql = "SELECT * FROM `pi_paymill_logging`";
if (isset($_POST['submit'])) {
    $sql = "SELECT * FROM `pi_paymill_logging` WHERE debug like '%" . xtc_db_input($_POST['search_key']) . "%'";
}
$logs = xtc_db_query($sql);
$logModel = new Services_Paymill_Log();
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
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                            <input value="" name="search_key"/><input type="submit" value="Search..." name="submit"/>
                        </form>
                        <table>
                            <tr class="dataTableHeadingRow">
                                <th class="dataTableHeadingContent">ID</th>
                                <th class="dataTableHeadingContent">Debug</th>
                                <th class="dataTableHeadingContent">Date</th>
                            </tr>
                            <?php while ($log = xtc_db_fetch_array($logs)): ?>
                            <tr class="dataTableRow">
                                <td class="dataTableContent"><?php echo $log['id']; ?></td>
                                <td class="dataTableContent">
                                    <?php $logModel->fill($log['debug']) ?>
                                    <table>
                                        <tr class="dataTableHeadingRow">
                                            <?php foreach ($logModel->toArray() as $key => $value): ?>
                                                <th class="dataTableHeadingContent"><?php echo strtoupper(str_replace('_', ' ', $key)); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                        <tr class="dataTableRow">
                                            <?php foreach ($logModel->toArray() as $key => $value): ?>
                                            <td class="dataTableContent">
                                                <?php if (strlen($value) > 300): ?>
                                                    <a href="<?php echo xtc_href_link('paymill_log.php', 'id=' . $log['id'] . '&key=' . $key, 'SSL', true, false); ?>">See more</a>
                                                <?php else: ?>
                                                    <pre><?php echo $value; ?></pre>
                                                <?php endif; ?>
                                            </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    </table>
                                </td>
                                <td class="dataTableContent"><?php echo $log['date']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </table>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                            <input value="" name="search_key"/><input type="submit" value="Search..." name="submit"/>
                        </form>
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