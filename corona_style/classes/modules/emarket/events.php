<?php
	$handler = new umiEventListener('order-status-changed', 'emarket', 'fixOrder');
	$handler->setIsCritical(true);
	new umiEventListener('systemModifyObject ', 'emarket', 'fixDiscount');

?>
