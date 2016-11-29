<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once './app/Mage.php';
umask(0);
Mage::app('admin');

$db = Mage::getSingleton('core/resource')->getConnection('core_write');
$sales_flat_order_grid= Mage::getSingleton('core/resource')->getTableName('sales_flat_order_grid');

$collection = Mage::getResourceModel('sales/order_collection')
                ->addAttributeToSelect('*')
                ->addFieldToFilter('status', 'canceled')
                ->addFieldToFilter('customer_email', array('eq' => "test@webexpressen.no"))
                ->load();

## echo $collection->getSelect();exit;
							
## echo "<pre/>";print_r($collection->getData());die;

$orderGridColl = Mage::getResourceModel('sales/order_grid_collection')
                ->addAttributeToSelect('*')
                ->addFieldToFilter('status', 'canceled')
                ->addFieldToFilter('billing_name', array('like' => "Fredrik test%"))
                ->load();

## echo "<pre/>";print_r($orderGridColl->getData());die;

foreach ($orderGridColl as $col) {
    try {
        $orderIncId = $col->getIncrementId();
        $col->delete();
        echo $orderIncId." order is now deleted!!!";
    } catch (Exception $e) {
        throw $e;
    }
}

die;

foreach ($collection as $col) {
    try {
        $col->delete();
        $order_increment_id = $order->getIncrementId();
        if($order_increment_id && $col->getIncrementId() != $order_increment_id) {
            $db->query("DELETE FROM ".$sales_flat_order_grid." WHERE increment_id='".mysql_escape_string($order_increment_id)."'");
        }
        echo $order_increment_id." order is now deleted!!!"."<br/>";
    } catch (Exception $e) {
        throw $e;
    }
}

/*tar -zcvf ooberpad_14-12-2015.tar.gz --exclude='*.tar' --exclude='*.log' magento/

rm -rf magento/var/cache/*
rm -rf magento/var/session/*

tar -zcvf ooberpad_14-12-2015.tar.gz --exclude='*.tar' --exclude='*.log' magento/

scp root@128.199.104.39:/var/www/html/ooberpad_14-12-2015.tar.gz /home/ubuntu/Downloads

scp root@128.199.104.39:ooberpad_14-12-2015.tar.gz /home/ubuntu/Downloads
*/

/*
DELETE 
FROM `sales_flat_order_grid` 
WHERE entity_id IN  (
    SELECT * FROM (
        SELECT g.entity_id
        FROM `sales_flat_order_grid` AS g
            INNER JOIN `sales_flat_order` AS o
                ON g.`entity_id` = o.`entity_id`
        WHERE g.entity_id IS NULL AND o.`customer_email` = "hashir@perceptionsystem.com"
    ) AS t
)
* 
SELECT *
FROM `sales_flat_order_grid` 
WHERE entity_id IN  (
    SELECT * FROM (
        SELECT g.entity_id
        FROM `sales_flat_order_grid` AS g
            INNER JOIN `sales_flat_order` AS o
                ON g.`entity_id` = o.`entity_id`
        WHERE g.entity_id IS NULL AND o.`customer_email` = "hashir@perceptionsystem.com"
    ) AS t
)
* 
DELETE
FROM `sales_flat_order_grid` 
WHERE entity_id IN  (
    SELECT * FROM (
        SELECT g.entity_id
        FROM `sales_flat_order_grid` AS g
            INNER JOIN `sales_flat_order` AS o
                ON g.`entity_id` = o.`entity_id`
        WHERE o.`customer_email` = "hashir@perceptionsystem.com"
    ) AS t
)
*
DELETE
FROM `sales_flat_order_grid` 
WHERE entity_id IN  (
    SELECT * FROM (
        SELECT g.entity_id
        FROM `sales_flat_order_grid` AS g
            INNER JOIN `sales_flat_order` AS o
                ON g.`entity_id` = o.`entity_id`
        WHERE o.`customer_firstname` = "denish" OR o.`customer_firstname` = "test"
    ) AS t
)
*/
