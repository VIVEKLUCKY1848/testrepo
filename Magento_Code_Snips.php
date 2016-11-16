<?php

## Magento 1.x version check if module is installed and enabled in *.php and *.phtml files start
$modules = Mage::getConfig()->getNode('modules')->children();
$modulesArray = (array)$modules;
$moduleStatus = (string)$modulesArray['Company_Module']->active;
if(isset($modulesArray['Company_Module']) && $moduleStatus != "false") {
// Only then do your module coding here
}
## Magento 1.x version check if module is installed and enabled in *.php and *.phtml files finish
