<!DOCTYPE html>
<html lang="">
    <head>
        <meta charset="utf-8">
        <title>Estimate</title>
        <style type="text/css">
            @media all {
                span.top-leaf-estimate {
                    font-size: 30px;
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
                    width: 50%;
                    display: inline-block;
                    float: left;
                }
                .top .logo {
                    width: 50%;
                    float: left;
                    display: inline-block;
                }
                table {
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="row top">
                <div class="info">
                    <span class="top-leaf-estimate">
                        <?php echo $companyInfo['name'] ?>
                    </span>
                    <br>
                    <span><?php echo $companyInfo['full_address'] ?></span>
                    <br>
                    Tel:
                    <span><?php echo $companyInfo['primary_phone_number'] ?></span>
                    Fax:
                    <span><?php echo $companyInfo['fax'] ?></span>
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
            </div>

            <div class="row">
                <h3 class="title-estimate">ESTIMATE</h3>
                <table>
                    <tbody>
                        <tr>
                            <td>
                                <span>ADDRESS</span><br>
                                <span><?php echo $estimate['bill_address'] ?></span><br>
                                <span><?php if(isset($estimate['bill_city'])) echo $estimate['bill_city'] . ',' . $estimate['bill_state'] . ' ' . $customer['bill_zip_code']; ?></span>
                            </td>
                            <td>
                                <span>ESTIMATE # <?php echo $estimate['id'] ?></span><br>
                                <span>DATE <?php echo $estimate['txn_date'] ?></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

                <div class="line clearfix"></div>

                <div class="row clearfix">
                    <table class="table table-line">
                        <thead class="thead-bg">
                            <tr>
                                <th><span>ACTIVITY</span></th>
                                <th><span>QTY</span></th>
                                <th><span>RATE</span></th>
                                <th><span>AMOUNT</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $sum = 0;
                                foreach ($lines as $line) {
                                    $amount = $line['qty'] * $line['rate'];
                                    $sum = $sum + $amount;
                            ?>
                                <tr>
                                    <td><span><?php echo $line['product_service_name'] ?></span></td>
                                    <td><span><?php echo $line['qty'] ?></span></td>
                                    <td><span><?php echo $line['rate'] ?></span></td>
                                    <td><span><?php echo $amount ?></span></td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <td colspan="3">
                                </td>
                                <td>
                                    TOTAL <?php echo '$' . $sum ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row">
                    <div class="col-sm-12">
                        <span>Customer signature</span><br>
                        <br>
                        <span>Date: <?php echo $estimate['date_of_signature'] ?></span><br>
                        <?php if ($estimate['customer_signature']) : ?>
                            <img src="<?php echo $estimate['customer_signature'] ?>">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <script type="text/javascript">
            window.onload = function() { self.print(); }
        </script>
    </body>
</html>
