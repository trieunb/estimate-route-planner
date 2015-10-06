<!DOCTYPE html>
<html lang="">
    <head>
        <meta charset="utf-8">
        <title>Estimate</title>
        <style type="text/css">
            body {
                font-size: 12px;
            }
            span.top-leaf-estimate {
                font-size: 22px;
            }
            label {
                font-weight: 600;
            }
           table {
               width: 100%;
               max-width: 100%;
               margin-bottom: 20px;
               border-spacing: 2px;
               border-color: grey;
               margin: 20px 0px;
            }
            .title-estimate {
                font-size: 30px;
                margin-top: 5px;
            }
            img.estimate-logo {
                width: 100px;
            }
            th, tr {
                text-align: left;
            }
            .row.top {
                margin-bottom: 0;
            }
            .top .info {
                width: 40%;
                display: inline-block;
                float: left;
            }
            .top .logo {
                width: 20%;
                float: left;
                display: inline-block;
            }
            .top .estimate-service {
                width: 40%;
                float: left;
                display: inline-block;
                text-align: right;
            }
            .top .estimate-service label {
                font-weight: bold;
            }

            .title-service {
                font-size: 22px;
            }
            table.tbl-service tbody tr th {
                float: right;
            }
            table.tbl-service tbody tr td {
                text-align: right;
            }
            .signature-left {
                  width: 50%;
                  float: left;
                  display: inline-block;
            }
            .footer-right {
                  width: 45%;
                  float: right;
                  display: inline-block;
            }
            table.tabl-info, th, td {
                border: 1px solid black;
                border-collapse: collapse;
            }
            table.table-line, th, td {
                border: 1px solid black;
                border-collapse: collapse;
            }
            th, td {
                padding: 5px;
            }
            table.tabl-info tbody tr th {
                width:10% !important;
            }

        </style>
    </head>
    <body>
        <div class="container">
            <div class="row top">
                <div class="info">
                    <span class="top-leaf-estimate">
                        <?php echo $companyInfo->name ?>
                    </span>
                    <br>
                    <span><?php echo $companyInfo->full_address ?></span>
                    <br>
                    Tel:
                    <span><?php echo $companyInfo->primary_phone_number ?></span>
                    Fax:
                    <span><?php echo $companyInfo->fax ?></span>
                </div>
                <div class="logo">
                    <?php
                        $logoURL = '';
                        if ($companyInfo->logo_url) {
                            $logoURL = $companyInfo->logo_url;
                        } else {
                            $logoURL = 'images/default-logo.png';
                        }
                    ?>
                    <img src="<?php echo $logoURL ?>" class="estimate-logo">
                </div>
                <div class="estimate-service">
                    <div>
                        <span colspan="2" class="title-service">Service Agreement</span>
                    </div>
                    <div>
                            <label>Estimate Date:</label>
                            <span><?php echo $estimate->txn_date ?></span>
                        <br>
                            <label>Due Date:</label>
                            <span><?php echo $estimate->due_date ?></span>
                        <br>
                            <label>Status:</label>
                            <span><?php echo $estimate->status ?></span>
                    </div>
                </div>
            </div>

            <div class="form-info">
                <table class="tabl-info">
                    <thead>
                        <tr>
                            <th colspan="2" style="text-align:center; width:50%;">Billing Information</th>
                            <th colspan="2" style="text-align:center; width:50%;">Job Information</th>
                        </tr>
                    </thead>
                    <tbody>

                        <tr>
                            <th>Name</th>
                            <td><?php echo $estimate->customer_display_name ?></td>
                            <th>Name</th>
                            <td><?php echo $estimate->job_customer_display_name ?></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td>
                                <?php if ($estimate->bill_address) { ?>
                                    <?php echo $estimate->bill_address .
                                    ' ' . $estimate->bill_city .
                                    ', ' . $estimate->bill_state .
                                    ' ' . $estimate->bill_zip_code;
                                    ?>
                                <?php } ?>
                            </td>
                            <th>Address</th>
                            <td>
                                <?php if($estimate->job_address) { ?>
                                    <?php
                                    echo $estimate->job_address .
                                    ' ' . $estimate->job_city .
                                    ', ' . $estimate->job_state .
                                    ' ' . $estimate->job_zip_code;
                                    ?>
                                <?php } ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Primary Phone</th>
                            <td><?php echo $estimate->primary_phone_number ?></td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <th>Secondary Phone</th>
                            <td><?php echo $estimate->alternate_phone_number ?></td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo $estimate->email ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="row localtion-note">
                <div class="note">
                    <label>Location Notes:</label>
                    <span><?php echo $estimate->location_notes ?></span>
                </div>
            </div>

            <div class="row">
                <table class="table-line">
                    <thead class="thead-bg">
                        <tr style="border: 1px solid #ddd;">
                            <th><span>ACTIVITY</span></th>
                            <th><span>Description</span></th>
                            <th><span>QTY</span></th>
                            <th><span>RATE</span></th>
                            <th><span>AMOUNT</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            foreach ($lines as $line) {
                                $amount = $line['qty'] * $line['rate'];
                        ?>
                            <tr style="border: 1px solid #ddd;">
                                <td>
                                    <span><b><?php echo $line['product_service_name'] ?></b></span>
                                </td>
                                <td><span><?php echo $line['description'] ?></span></td>
                                <td><span><?php echo $line['qty'] ?></span></td>
                                <td><span><?php echo $line['rate'] ?></span></td>
                                <td><span><?php echo $amount ?></span></td>
                            </tr>
                        <?php } ?>
                        <tr style="border: 1px solid #ddd;">
                            <td colspan="4">
                            </td>
                            <td>
                                TOTAL <?php echo '$' . $estimate->total ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="row">
                <div class="signature-left">
                    <label>Date of Signature:</label>
                    <span><?php echo $estimate->date_of_signature ?></span><br>
                    <?php if ($estimate->customer_signature) :?>
                        <img style="max-width: 100%" src="<?php echo $estimate->customer_signature ?>">
                    <?php endif; ?>
                    <br>
                    <label>Sold By:</label>
                    <span><?php echo $estimate->sold_by_1 ?></span><br>
                    <label>Sold By:</label>
                    <span><?php echo $estimate->sold_by_2 ?></span>
                </div>
                <div class="footer-right">
                    <span><?php echo $estimate->estimate_footer; ?></span>
                </div>
            </div>
        </div>
    </body>
</html>
