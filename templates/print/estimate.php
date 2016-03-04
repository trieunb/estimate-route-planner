<!DOCTYPE html>
<html lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Estimate #<?php echo $estimate->doc_number ?></title>
        <style type="text/css">
            <?php include 'estimate.css' ?>
            <?php if ($_REQUEST['_do'] === 'printEstimate') : ?>
                @media screen {
                    .print-container {
                        width: 1024px;
                        margin: 0 auto;
                    }
                }
            <?php endif; ?>
        </style>
    </head>
    <body class="print">
        <div class="print-container">
            <div class="section header">
                <div class="logo">
                    <?php
                        $logoURL = '';
                        if ($companyInfo->logo_url) {
                            $logoURL = erp_asset_url($companyInfo->logo_url);
                        } else {
                            $logoURL = erp_asset_url('images/default-logo.png');
                        }
                    ?>
                    <img src="<?php echo $logoURL ?>" class="estimate-logo">
                </div>
                <div class="company-info">
                    <span class="name"><?php echo $companyInfo->name ?></span>
                    <br>
                    <?php echo $companyInfo->mailing_address ?>
                    <br>
                    <br>
                    <?php echo $companyInfo->primary_phone_number ?>
                    <br>
                    <?php echo $companyInfo->email ?>
                    <br>
                    <?php echo $companyInfo->website ?>
                </div>
                <div class="estimate-info">
                    <span class="title">Estimate</span>
                    <table class="bordered">
                        <tbody>
                            <tr class="head">
                                <td>
                                    Date
                                </td>
                                <td>
                                    Estimate #
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <?php if ($estimate->txn_date) echo Date('m-d-Y', strtotime($estimate->txn_date)) ?>
                                </td>
                                <td>
                                    <?php echo $estimate->doc_number ?>
                                </td>
                            </tr>
                            <tr class="head">
                                <td class="invisible">
                                </td>
                                <td>
                                    Exp. Date
                                </td>
                            </tr>
                            <tr>
                                <td class="invisible">
                                </td>
                                <td>
                                    <?php if ($estimate->expiration_date) echo Date('m-d-Y',strtotime($estimate->expiration_date)) ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="section address">
                <div class="billing">
                    <table class="bordered">
                        <tr class="head">
                            <td>
                                Address
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php if ($estimate->bill_company_name) : ?>
                                    <?php echo $estimate->bill_company_name ?>
                                <?php else: ?>
                                    <?php echo $estimate->customer_display_name ?>
                                <?php endif ?>
                                <br>
                                <?php echo $estimate->bill_address ?>
                                <br>
                                <?php echo $estimate->bill_city ?>, <?php echo $estimate->bill_state ?> <?php echo $estimate->bill_zip_code ?>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="shipping">
                    <table class="bordered">
                        <tr class="head">
                            <td>
                                Ship To
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php if ($estimate->job_company_name) : ?>
                                    <?php echo $estimate->job_company_name ?>
                                <?php else: ?>
                                    <?php echo $estimate->job_customer_display_name ?>
                                <?php endif ?>
                                <br>
                                <?php echo $estimate->job_address ?>
                                <br>
                                <?php echo $estimate->job_city ?>, <?php echo $estimate->job_state ?> <?php echo $estimate->job_zip_code ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="section lines">
                <table class="bordered-columns">
                    <tr class="head">
                        <td>
                            Description
                        </td>
                        <td>
                            Quantity
                        </td>
                        <td>
                            Rate
                        </td>
                        <td>
                            Amount
                        </td>
                    </tr>
                    <?php
                        foreach ($lines as $line) {
                            $amount = $line['qty'] * $line['rate'];
                            $blankLine = !$line['product_service_name'] && !$line['description'] && ($amount == 0);
                    ?>
                        <?php if ($blankLine) : ?>
                            <tr class="line-blank">
                                <td colspan="4">
                                </td>
                            </tr>
                        <?php else : ?>
                            <tr>
                                <td>
                                    <li><?php echo nl2br($line['description']) ?></li>
                                </td>
                                <td><span><?php echo $line['qty'] ?></span></td>
                                <td><span><?php echo $line['rate'] ?></span></td>
                                <td>$<span><?php echo $amount ?></span></td>
                            </tr>
                        <?php endif; ?>
                    <?php } ?>
                    <tr class="head">
                        <td colspan="3" style="text-align:right;">
                            TOTAL
                        </td>
                        <td>
                            $<?php echo $estimate->total ?>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="section footer">
                <div class="left">
                    <strong>Disclaimer:</strong> <?php echo nl2br($estimate->disclaimer) ?>
                    <br>
                    <br>
                    <strong>Accepted agreement:</strong> <?php echo nl2br($estimate->estimate_footer) ?>
                    <br>
                    <br>
                    <br>
                    <strong>Sold By: </strong> <?php echo $estimate->sold_by_1 ?>
                    <br>
                    <?php if ($estimate->sold_by_2) : ?>
                        <strong>Sold By: </strong> <?php echo $estimate->sold_by_2 ?>
                    <?php endif ?>
                </div>
                <div class="right">
                    <strong>Date of Signature:</strong> <?php if ($estimate->date_of_signature) echo Date('m-d-Y', strtotime($estimate->date_of_signature)) ?>
                    <br>
                    <?php if ($estimate->customer_signature) : ?>
                        <?php
                            $imgURL = '';
                            if ($_REQUEST['_do'] === 'printEstimate') {
                                $imgURL = erp_asset_url($estimate->customer_signature);
                            } else {
                                $imgURL = $estimate->customer_signature;
                            }
                        ?>
                        <img style="max-width: 100%"src="<?php echo $imgURL ?>">
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if ($_REQUEST['_do'] === 'printEstimate') : ?>
            <script type="text/javascript">
                window.onload = function() { self.print(); }
            </script>
        <?php endif; ?>
    </body>
</html>
