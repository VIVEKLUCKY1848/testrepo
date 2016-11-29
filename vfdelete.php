<?php
define('MAGENTO_ROOT', getcwd());
$mageFilename = MAGENTO_ROOT . '/app/Mage.php';
require_once $mageFilename; Mage::setIsDeveloperMode(true);
$base_url = Mage::getBaseUrl();
Mage::app('default');

try {
	$default_setup = Mage::getConfig()->getResourceConnectionConfig("default_setup");
	$dbhost = $default_setup->host;
	$dbuser = $default_setup->username;;
	$dbpass = $default_setup->password;
	$dbName = $default_setup->dbname;
	$connection = new mysqli($dbhost, $dbuser, $dbpass, $dbName);
} catch(Exception $e) {
	echo $e;
}

if($connection->connect_error) {
	echo "Error Occurred While Connection To DataBase";
}

//SQL statements separated by semi-colons
$sqlStatements = "SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE `company_1_mapping`;
TRUNCATE `company_level_1_cat`;
TRUNCATE `company_level_1_litre`;
TRUNCATE `company_level_1_make`;
TRUNCATE `company_level_1_year`;
TRUNCATE `company_level_1_model`;
TRUNCATE `company_mapping_notes`;
TRUNCATE `company_note`;
TRUNCATE `company_mapping_paint`;
ALTER TABLE `company_1_mapping` AUTO_INCREMENT=1;
ALTER TABLE `company_level_1_cat` AUTO_INCREMENT=1;
ALTER TABLE `company_level_1_litre` AUTO_INCREMENT=1;
ALTER TABLE `company_level_1_make` AUTO_INCREMENT=1;
ALTER TABLE `company_level_1_model` AUTO_INCREMENT=1;
ALTER TABLE `company_level_1_year` AUTO_INCREMENT=1;
ALTER TABLE `company_mapping_notes` AUTO_INCREMENT=1;
ALTER TABLE `company_note` AUTO_INCREMENT=1;
ALTER TABLE `company_mapping_paint` AUTO_INCREMENT=1;
-- TRUNCATE `catalog_compare_item`;
SET FOREIGN_KEY_CHECKS = 1;";

$sqlResult = $connection->multi_query($sqlStatements);
if($sqlResult == true) {
	Mage::app()->cleanCache();
	echo 'Successfully clear all vehiclefit data.';
	header( "refresh:2;url=$base_url" );
}
else {
   echo 'Error occurred executing statements.';
}
