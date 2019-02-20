<?php

require_once 'vendor/autoload.php';
use League\Csv\Writer;

function validateDate($date, $format = 'Ymd')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

$db = new MysqliDb ('localhost', 'root', '', 'test');

if ($_GET) {

    if (array_key_exists('quires', $_GET)) {
        if (array_key_exists('HTTPS', $_SERVER)) {
            if ($_SERVER['HTTPS'] == 'on') {
                $https = 'https';
            } else {
                $https = 'http';
            }
        } else {
            $https = 'http';
        }
        header('Location: '.$https.'://'.$_SERVER['HTTP_HOST'].$_GET['quires'], true, 301);
        exit(0);
    }

    $days = $_GET['days'];
    if ($days) {
        $db->where ('paymentDate >= NOW() - INTERVAL '.$days.' DAY');
        $db->where ('paymentDate <= NOW()');
    } else {
        $sdate = $_GET['sdate'];
        if (validateDate($sdate)) {
            $startDate = date('Y-m-d', mktime(0, 0, 0, substr($sdate, 4, 2), substr($sdate, 6, 2), substr($sdate, 0, 4)));
            $db->where ('paymentDate', $startDate, '>=');
        }

        $edate = $_GET['edate'];
        if (validateDate($edate)) {
            $endDate = date('Y-m-d', mktime(0, 0, 0, substr($edate, 4, 2), substr($edate, 6, 2), substr($edate, 0, 4)));
            $db->where ('paymentDate', $endDate, '<=');
        }
    }

    $channel = $_GET['channel'];
    if ($channel) $db->where ('channel', $channel);

    $sku = $_GET['sku'];
    if ($sku) $db->where ('`sku-qty`', '%'.$sku.'%', 'like');

    $state = $_GET['state'];
    if ($state) $db->where ('state', $state);

    $country = $_GET['country'];
    if ($country) $db->where ('country', $country);

    $price = $_GET['price'];
    if ($price) $db->where ('orderTotal', $price);

    if (array_key_exists('fields', $_GET)) {
        $fields = $_GET['fields'];
    } else {
        $fields = null;
    }

    $results = $db->get ('orders', null, $fields);

    if (array_key_exists('keep', $_GET)) {
        if ($_GET['keep'] == 'on') {
            $data = ['link' => $_SERVER['REQUEST_URI'], 'last_used' => $db->now()];
            $id = $db->insert ('links', $data);
        }
    }

    if ($results) {
        $csv = Writer::createFromString('');
        $csv->insertOne(array_keys($results[0]));
        $csv->insertAll($results);
        $csv->output('test.csv');
        exit(0);
    }

}

?><html>
    <head>
        <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.23.0/moment.min.js"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.0-alpha14/js/tempusdominus-bootstrap-4.min.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.0-alpha14/css/tempusdominus-bootstrap-4.min.css" />
    </head>
    <body class="bg-light">
    <div class="container">
        <form class="py-5" method="get">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="dateStart">date start</label>
                    <input type="text" class="form-control datepicker" data-toggle="datetimepicker" data-target="#dateStart" id="dateStart" placeholder="" value="" name="sdate">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="dateEnd">date end</label>
                    <input type="text" class="form-control datepicker" data-toggle="datetimepicker" data-target="#dateEnd"  id="dateEnd" placeholder="" value="" name="edate">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="days">days (more prior than date)</label>
                    <input type="text" class="form-control" id="days" placeholder="" value="" name="days">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="channel">channel</label>
                    <input type="text" class="form-control" id="channel" placeholder="" value="" name="channel">
                </div>

            </div>

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="sku">sku</label>
                    <input type="text" class="form-control" id="sku" placeholder="" value="" name="sku">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="state">state (2 letters)</label>
                    <input type="text" class="form-control" id="state" placeholder="" value="" name="state">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="country">country (2 letters)</label>
                    <input type="text" class="form-control" id="country" placeholder="" value="" name="country">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="price">price (maybe float)</label>
                    <input type="text" class="form-control" id="price" placeholder="" value="" name="price">
                </div>
            </div>

            <div class="row">
                <div class="col-md-5 mb-3">
                    <label for="fields">select the fields</label>
                    <select class="custom-select d-block w-100" id="fields" multiple name="fields[]">
                        <option value="paymentDate">date_paid</option>
                        <option value="orderId">order_id</option>
                        <option value="orderKey">orderKey</option>
                        <option value="channel">channel</option>
                        <option value="`sku-qty`">sku, qty</option>
                        <option value="orderTotal">price</option>
                        <option value="shippingAmount">shippingcost</option>
                        <option value="trackingNumber">tracking_no</option>
                        <option value="customerEmail">customer_email</option>
                        <option value="name">customer_name</option>
                        <option value="company">company</option>
                        <option value="street1">street1</option>
                        <option value="street2">street2</option>
                        <option value="street3">street3</option>
                        <option value="city">city</option>
                        <option value="state">state</option>
                        <option value="postalCode">postalCode</option>
                        <option value="country">country</option>
                        <option value="phone">phone</option>
                    </select>
                </div>
            </div>

            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="keep" name="keep">
                <label class="custom-control-label" for="keep">Keep</label>
            </div>
            <button class="btn btn-primary btn-lg mt-3" type="submit">Send</button>
        </form>

        <hr class="mb-4">
        <?php $links = $db->get('links', null, 'link'); ?>
        <form class="py-5" method="get">
            <div class="row">
                <div class="col-md-5 mb-3">
                    <label for="quires">Saved quires</label>
                    <select class="custom-select d-block w-100" id="quires" required name="quires">
                        <?php foreach ($links as $link): ?>
                        <option value="<?=$link['link']?>"><?=$link['link']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button class="btn btn-primary btn-lg mt-3" type="submit">Execute</button>
        </form>
    </div>
    <script type="text/javascript">
        $(function () {
            $('.datepicker').datetimepicker({
                format: 'YYYYMMDD'
            });
        });
    </script>
    </body>
</html>