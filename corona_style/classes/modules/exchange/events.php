<?php
	//new umiEventListener('cron', 'catalog', 'fixPrice');

  //new umiEventListener('exchangeOnAddElement', 'catalog', 'updateObjects');
  new umiEventListener('exchangeOnImportFinish', 'exchange', 'fixRelaton');


?>
