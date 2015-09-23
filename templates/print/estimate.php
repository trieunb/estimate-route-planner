<!DOCTYPE html>
<html lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Estimates</title>
        <link href="//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet">
        <style type="text/css">
            @media all {
                span.top-leaf-estimate {
                    font-size: 22px;
                }
               #header {
                    display: none;
               }
               #footer {
                display: none;
               }
                .line {
                    border: 1px solid #000;
                    margin-bottom: 30px;
                    margin-top: 10px;
                }

                .title-estimate {
                    font-size: 30px;
                }
                img.estimate-logo {
                    width: 100px;
                }
                .row.top {
                    margin-bottom: 30px;
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
                    text-align: center;
                }
                .top .estimate-service {
                    width: 40%;
                    float: left;
                    display: inline-block;
                    text-align: right;
                }
                table {
                    width: 100%;
                }
                .title-service {
                    text-align: right;
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
                      width: 50%;
                      float: left;
                      display: inline-block;
                }
                table.tabl-info.table.table-bordered th.text-center {
                    width: 50%;
                }
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
                            $logoURL = erp_asset_url($companyInfo->logo_url);
                        } else {
                            $logoURL = erp_asset_url('images/default-logo.png');
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
                            <label>Dua Date:</label>
                            <span><?php echo $estimate->due_date ?></span>
                        <br>
                            <label>Status:</label>
                            <span><?php echo $estimate->status ?></span>
                    </div>
                </div>
            </div>

            <div class="row form-info">
                <table class="tabl-info table table-bordered">
                    <thead>
                        <th colspan="2" class="text-center">Billing Information</th>
                        <th colspan="2" class="text-center">Job Information</th>
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
                                    <?php
                                    echo $estimate->bill_address .
                                    ' ' . $estimate->bill_city .
                                    ', ' . $estimate->bill_state .
                                    ' ' . $estimate->bill_zip_code ;
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
                                    ' ' . $estimate->job_zip_code ;
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
            <div class="row estimate-line">
                <table class="table table-bordered table-line">
                    <thead>
                        <tr>
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
                            <tr>
                                <td><span><?php echo $line['product_service_name'] ?></span></td>
                                <td><span><?php echo $line['description'] ?></span></td>
                                <td><span><?php echo $line['qty'] ?></span></td>
                                <td><span><?php echo $line['rate'] ?></span></td>
                                <td><span><?php echo $amount ?></span></td>
                            </tr>
                        <?php } ?>
                        <tr>
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
                        <img src="<?php echo erp_asset_url($estimate->customer_signature) ?>">
                    <?php endif; ?>
                    <br>
                    <label>Sold By:</label>
                    <span><?php echo $estimate->sold_by_1_display_name ?></span><br>
                    <label>Sold By:</label>
                    <span><?php echo $estimate->sold_by_2_display_name ?></span>
                </div>
                <div class="footer-right">
                    <span><?php echo $estimate->estimate_footer; ?></span>
                </div>
            </div>
        </div>
        <script type="text/javascript">
            window.onload = function() { self.print(); }
        </script>
    </body>
</html>
