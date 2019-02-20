<?php

require_once 'vendor/autoload.php';
use MichaelB\ShipStation\ShipStationApi;

$db = new MysqliDb ('localhost', 'root', '', 'test');
$apiKey = 'a5f20d25182d4d2c8a2627dc314ad272';
$apiSecret = '45d791a218fa49cb89627b2d083daed0';
$options = [];

$ship = new ShipStationApi ($apiKey, $apiSecret, $options);

$orderService = $ship->getOrdersService();
$storeService = $ship->getStoresService();
$shipService = $ship->getShipmentsService();

$db->orderBy ('orderId', 'desc');
$sort_order = $db->getOne ('orders');
if ($sort_order) {
    $sort = $sort_order['createDate'];
} else {
    $sort = null;
}

$all = $orderService->getListing (['createDateStart'=>$sort]);
$data = json_decode($all->getBody()->getContents());
$i = 0;

foreach ($data->orders as $order) {

    if ($order->advancedOptions->storeId) {
        $store = json_decode($storeService->getStore($order->advancedOptions->storeId)->getBody()->getContents())->storeName;
    } else {
        $store = null;
    }

    $shipment = json_decode($shipService->listShipments(['orderId'=>$order->orderId])->getBody()->getContents())->shipments;
    if ($shipment) {
        $track_no = $shipment[0]->trackingNumber;
    } else {
        $track_no = null;
    }

    if ($order->items) {
        $sku = '';
        foreach ($order->items as $item) {
            $sku .= $item->sku.'*'.$item->quantity.';';
        }
    } else {
        $sku = null;
    }

    $entry = [
        'orderId' => $order->orderId,
        'orderKey' => $order->orderKey,
        'createDate' => $order->createDate,
        'paymentDate' => $order->paymentDate,
        'orderTotal' => $order->orderTotal,
        'shippingAmount' => $order->shippingAmount,
        'customerEmail' => $order->customerEmail,
        'name' => $order->shipTo->name,
        'company' => $order->shipTo->company,
        'street1' => $order->shipTo->street1,
        'street2' => $order->shipTo->street2,
        'street3' => $order->shipTo->street3,
        'city' => $order->shipTo->city,
        'state' => $order->shipTo->state,
        'postalCode' => $order->shipTo->postalCode,
        'country' => $order->shipTo->country,
        'phone' => $order->shipTo->phone,
        'channel' => $store,
        'trackingNumber' => $track_no,
        'sku-qty' => $sku
    ];

    $id = $db->insert ('orders', $entry);
    if($id) {
        $i++;
    }

}

if ($data->pages > 1) {
    for ($i = 2; $i <= $data->pages; $i++) {
        $all = $orderService->getListing (['page'=>$i]);
        $data = json_decode($all->getBody()->getContents());
        foreach ($data->orders as $order) {

            if ($order->advancedOptions->storeId) {
                $store = json_decode($storeService->getStore($order->advancedOptions->storeId)->getBody()->getContents())->storeName;
            } else {
                $store = null;
            }

            $shipment = json_decode($shipService->listShipments(['orderNumber'=>$order->orderId])->getBody()->getContents())->shipments;
            if ($shipment) {
                $track_no = $shipment[0]->trackingNumber;
            } else {
                $track_no = null;
            }

            if ($order->items) {
                $sku = '';
                foreach ($order->items as $item) {
                    $sku .= $item->sku.'*'.$item->quantity.';';
                }
            } else {
                $sku = null;
            }

            $entry = [
                'orderId' => $order->orderId,
                'orderKey' => $order->orderKey,
                'createDate' => $order->createDate,
                'paymentDate' => $order->paymentDate,
                'orderTotal' => $order->orderTotal,
                'shippingAmount' => $order->shippingAmount,
                'customerEmail' => $order->customerEmail,
                'name' => $order->shipTo->name,
                'company' => $order->shipTo->company,
                'street1' => $order->shipTo->street1,
                'street2' => $order->shipTo->street2,
                'street3' => $order->shipTo->street3,
                'city' => $order->shipTo->city,
                'state' => $order->shipTo->state,
                'postalCode' => $order->shipTo->postalCode,
                'country' => $order->shipTo->country,
                'phone' => $order->shipTo->phone,
                'channel' => $store,
                'trackingNumber' => $track_no,
                'sku-qty' => $sku
            ];
            $id = $db->insert ('orders', $entry);
            if($id) {
                $i++;
            }
        }
    }
}

echo $i.' orders(s) was import.';
