<?php

ini_set('display_errors',1);
error_reporting(E_ALL);

ob_start();
ob_end_clean();

if($_GET['oid']!='') {
set_time_limit(0);
	define('MAGENTO_ROOT', '..');
	//echo MAGENTO_ROOT;
	$compilerConfig = MAGENTO_ROOT . '/includes/config.php';
	if (file_exists($compilerConfig)) {
		include $compilerConfig;
	}
	$mageFilename = MAGENTO_ROOT . '/app/Mage.php';
	require_once $mageFilename;

	if (isset($_SERVER['MAGE_IS_DEVELOPER_MODE'])) {
		Mage::setIsDeveloperMode(true);
	}
	Mage::init();
	$img_path = ''; $cat_name = ''; $ordQty = 0; $item_name=''; $optionValueNumber = 0; $strSize = ''; $product_bg_img = '';
	$order_id = $_GET['oid'];
	$item_id = $_GET['iid'];
    $order = Mage::getModel("sales/order")->load($order_id); //load order by order id 
    $arrAddressDetails = $order->getShippingAddress()->getData(); 
    $order_inc_id = $order->getIncrementId();
    $ordered_items = $order->getAllItems(); 
    foreach($ordered_items as $_item){ 
    	if ($_item->getItemId() == $item_id)
        {
        	//$img_path = $_item->getData('img_path');
        	
        	$item_name = $_item->getName();
        	//echo '<br/>';
        	//$ordQty = $_item->getQtyToInvoice();
        	$ordQty = $_item->getQtyOrdered();
        	//echo '<br/>';
        	$options = $_item->getProductOptions();
        	//echo $_item->getData('product_options');
        	$options = unserialize($_item->getData('product_options'));
        	
        	$oldjson = $options['info_buyRequest']['txtJsonImg'];
        	
        	$oldjson = str_ireplace ( '"fontSize":27' , '"fontSize":18' , $oldjson );
        	
        	##echo "<pre/>";print_r($oldjson);die;
        	
        	$source = json_decode($oldjson);
        	
        	foreach($source as $object => $array):
        		foreach($array as $obj):
        			if($obj->fontSize == 12):
        				$obj->fontSize = 8;
        			endif;
        		endforeach;
        	endforeach;

		$newjson = json_encode($source);
		$options['info_buyRequest']['txtJsonImg'] = $newjson;
        	
        	//$options = $_item->getProduct()->getTypeInstance(true)->getOrderOptions($_item->getProduct());
        	//print_r($options);
        	 $img_path = $options['info_buyRequest']['txtSVGImg'];
        	 if($img_path=='')
		{
			//$img_path = $options['info_buyRequest']['txtSVGImg'];
			$img_path = $_item->getData('img_path');
		}
		
			$img_path = str_replace(array('source sans pro', 'alex brush', 'brush script'), array('source-sans-pro', 'alex-brush', 'brush script'), $img_path);
			
			##echo "<pre/>";print_r($img_path);die;
			
        	$customOptions = $options['options'];   
    		if(!empty($customOptions))
    		{
			foreach ($customOptions as $option)
			{	    
    			$optionTitle = $option['label'];
    			$optionId = $option['option_id'];
    			$optionType = $option['option_type'];
    			//echo '<br/>';
    			$optionValue = $option['value'];
    			if($optionType == 'drop_down' || $optionType == 'radio'){
    				$optionValueNumber = filter_var($optionValue, FILTER_SANITIZE_NUMBER_INT);
					$strSize = $optionTitle.': '.$optionValue;
				}
			}
    		}
        	//echo '<br/>';
        	$product_id = $_item->getProductId();
        	$product=Mage::getModel('catalog/product')->load($product_id);

			$cat_name = '';
			$categoryIds = $product->getCategoryIds();

			if(count($categoryIds) ){	
    			foreach($categoryIds AS $firstCategoryId) {
    				$_category = Mage::getModel('catalog/category')->load($firstCategoryId);
    				$cat_name = $_category->getName();
    				//echo '<br/>';
    			}
			}
			$product_bg_img = $options['info_buyRequest']['prod_img_path'];
        }
    }
    if($optionValueNumber > 0){
    	$total_number = ($ordQty * $optionValueNumber);
    }else{
    	$total_number = $ordQty;
    }
    //echo $ordQty.'-'.$optionValueNumber.'-'.$total_number;
    //echo $cat_name.'<br/>';
    if($cat_name == 'Navnelapper' || $cat_name == 'Refleks')
	{
		if( $product->getAttributeText('editor_size') == '3cmX1.3cm'){
		$htmlCss = '<html><head>
			<style>
		img {
    		image-rendering: -moz-crisp-edges;
    		image-rendering: -webkit-optimize-contrast;
    		-ms-interpolation-mode: bicubic
		}
		.rounded {
			border:0.09mm solid #ffffff;
			border-radius: 1.3mm;
			background-clip: border-box;
		}
		
		</style></head>
    <body>';
		$html = $htmlCss.'<div style="width:100%;height:auto;border:0.1mm solid #ffffff;">';
		$html .= '<div style="width:95%;height:auto;margin:16px 7px 8px 21px">		
		';
		$j=0;
		for($i=0; $i<$total_number; $i++){
			if($i%50 == 0){ if($i>49){$html .=  '<div style="width:100%;margin-top:130px;text-align:center">&nbsp;</div>';} $html .=  '<div style="width:234px;height:151px;float:left;margin:0 2px 4px 3px;text-align:center;padding-top:10px;" class="rounded"><img src="mytag.jpg" border="0" style="width:224px"/><br/><font style="font-size:11px">('.$item_name.') bestilling nr '.$order_inc_id.'<br/>
Bestill enkelt p책 MyTag.no</font></div>'; $html .= '<div style="width:42.6%;height:160px;float:left;">'; $j=0;}
			if($j<=5){$html .= '<div style="width:115px;height:51px;float:left;margin:0 2px 4px 3px;" class="rounded"><img src="data:image/svg+xml;base64,'.base64_encode($img_path).'"/></div>';}
			if($j==5) {$html .= '</div>';}
			
			if($j>5) {$html .= '<div style="width:115px;height:51px;float:left;margin:0 2px 4px 3px;" class="rounded"><img src="data:image/svg+xml;base64,'.base64_encode($img_path).'"/></div>';}
			$j++;
		}
		$html .= '
		</div><div><body></html>';
		//echo "<pre/>";print_r($img_path);
		//echo $html; exit();
		}else if( $product->getAttributeText('editor_size') == '430mmX29mm'){
			$img_path = str_replace('  ', "&#8194;", $img_path);
			$html = '<html><head>
			<style>
		img {
    		image-rendering: -moz-crisp-edges;
    		image-rendering: -webkit-optimize-contrast;
    		-ms-interpolation-mode: bicubic
		}
		table { border-collapse: collapse; margin-top: 0; text-align: center; border:0}	
		.bg {  background-image: url("data:image/svg+xml;base64,'.base64_encode($img_path).'"); background-repeat: no-repeat; }		
		</style></head>
    <body>

		<table border="" cellspacing="0" cellpadding="0" width="100%"><tr>
		<td width="30%">&nbsp;</td><td width="40%" align="center" valign="middle"><img src="mytag.jpg" border="0" style="width:271px"/><br/><br/><font style="font-size:20px">('.$item_name.') bestilling nr '.$order_inc_id.'<br/>
Bestill enkelt p책 MyTag.no<br/><br/>Antall:'.$ordQty.'<br/>'.$strSize.'</font></td><td width="30%">&nbsp;</td></tr>		
		<tr>
		<td colspan="3" width="100%" height="40">&nbsp;</td>
		</tr>
		<tr>
		<td colspan="3" style="width:100%;height:55px;border:1px dashed gray">
			
						<div style="width:100%;height:auto;float:left;border:0px solid red;background-clip: border-box;"><img src="data:image/svg+xml;base64,'.base64_encode($img_path).'" /></div>
					
		</td>
		</tr>
		
		</table>
		<body></html>';
		}
	}else{
		$width =0; $height=0;
		if($product_bg_img!=''){
			list($width, $height, $type, $attr) = getimagesize($product_bg_img);
		}
		
		$html = '<html><head>
			<style>
		img {
    		image-rendering: -moz-crisp-edges;
    		image-rendering: -webkit-optimize-contrast;
    		-ms-interpolation-mode: bicubic
		}
		table { border-collapse: collapse; margin-top: 0; text-align: center; border:0}		
		</style></head>
    <body>

		<table border="0" cellspacing="0" cellpadding="0"><tr>
		<td width="40%" align="center" valign="middle"><img src="mytag.jpg" border="0" style="width:271px"/><br/><br/><font style="font-size:20px">('.$item_name.') bestilling nr '.$order_inc_id.'<br/>
Bestill enkelt p책 MyTag.no<br/><br/>Antall:'.$ordQty.'<br/>'.$strSize.'</font></td><td width="60%">&nbsp;</td></tr>		
		<tr>
		<td colspan="2">
			<table border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td style="background:url('.$product_bg_img.');width:'.$width.'px;height:'.$height.'px">
					<img src="data:image/svg+xml;base64,'.base64_encode($img_path).'"/>
					</td>
				</tr>
			</table>
		</td>
		</tr>

	</table>
	<body></html>';
	}
}else{
	echo $html = 'Wrong Image';
}

$order_text = '# '.$order_inc_id.'<br/>'.$total_number.' '.$item_name;
		$ship_address = $arrAddressDetails['firstname'].' '.$arrAddressDetails['lastname'].'<br/>'.$arrAddressDetails['street'].'<br/>'.$arrAddressDetails['city'].', '.$arrAddressDetails['postcode'].'<br/>'.$arrAddressDetails['country_id'];
		$footer_html =  '<div style="width:100%;text-align:center">';
		$footer_html .=  '<div style="width:192px;height:108px;text-align:left;float:left" >'.$ship_address.'</div>';
		$footer_html .=  '<div style="width:145px;height:108px;text-align:left;float:left;padding-left:5px" class="rounded">'.$order_text.'</div>';
		$footer_html .=  '<div style="width:165px;height:108px;text-align:left;float:left" class="rounded">'.$ship_address.'</div>';
		$footer_html .=  '</div>';
		
//exit();
//==============================================================
//==============================================================
//==============================================================
include("mpdf.php");

if( $product->getAttributeText('editor_size') == '430mmX29mm'){
	$mpdf=new mPDF('', array(165,180), 0, '', 1.5, 2, 0, 0, 0, 0); //548 X 590
}else{
	$mpdf=new mPDF('', array(165,268)); //548 X 790
}
$mpdf->tMargin = 9;
$mpdf->lMargin = 0;
$mpdf->rMargin = 0;
//$mpdf->dpi = 300;
//$mpdf->img_dpi = 250;

$mpdf->SetDisplayMode(100);
$mpdf->SetHTMLFooter($footer_html);
$mpdf->WriteHTML($html);

$mpdf->Output();
exit;
//==============================================================
//==============================================================
//==============================================================
//==============================================================
//==============================================================


?>
