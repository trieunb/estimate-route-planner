<!DOCTYPE html>
<html lang="">
    <head>
        <meta charset="utf-8">
        <title>Job request</title>
        <link href="//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet">
        <style type="text/css">
            td.wpt10 {
                width: 30%;
            }
            span.top-leaf-referral {
                font-size: 30px;
            }
            span.new-referal {
                font-size: 22px;
            }
            @media print {
               #header {
                    display: none;
               }
               #footer {
                display: none;
               }
            }

        </style>
    </head>
    <body>
        <div class="container">
            <div class="row">
                <div class="col-xs-6">
                    <span class="top-leaf-referral"><?php echo $companyInfo['name'] ?></span>
                    <br>
                    <span><?php echo $companyInfo['full_address'] ?></span>
                    <br>
                    Tel
                    <span><?php echo $companyInfo['primary_phone_number'] ?></span>
                    Fax
                    <span><?php echo $companyInfo['fax'] ?></span>
                </div>
                <div class="col-xs-6 text-right">
                    <br>
                    <label>Date:</label> <span><?php echo $referral['date_requested'] ?></span>
                    <br>
                    <label>Status:</label> <span><?php echo $referral['status'] ?></span>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <p><h2 class="text-center">Job Information</h2></p>
                </div>
                <div class="col-sm-12">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td class="wpt10">
                                    <label>Customer</label>
                                </td>
                                <td colspan="3">
                                    <span><?php echo $referral['customer_display_name'] ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="wpt10">
                                    <label>Address</label>
                                </td>
                                <td colspan="3">
                                    <span><?php echo $referral['address'] . ' ' . $referral['city']  .', ' . $referral['state'] . ' ' . $referral['zip_code'] ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="wpt10">
                                    <label>Email</label>
                                </td>
                                <td colspan="5">
                                    <span><?php echo $referral['email'] ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="wpt10">
                                    <label>Primary Phone</label>
                                </td>
                                <td colspan="3">
                                    <span><?php echo $referral['primary_phone_number'] ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="wpt10">
                                    <label>Date Service is Needed</label>
                                </td>
                                <td colspan="3">
                                    <span><?php echo $referral['date_service'] ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="wpt10">
                                    <label>How did you find out about us</label>
                                </td>
                                <td colspan="3">
                                    <span><?php echo $referral['how_find_us'] ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="wpt10">
                                    <label>
                                        Discribe the type of<br>
                                        tree service you require
                                    </label>
                                </td>
                                <td colspan="3">
                                    <span><?php echo $referral['type_of_service_description'] ?></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <script type="text/javascript">
            window.onload = function() { self.print(); }
        </script>
    </body>
</html>
