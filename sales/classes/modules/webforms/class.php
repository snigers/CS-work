<?php

class webforms_custom extends def_module {

	
	public function sendCustom() {

		//if (!umiCaptcha::checkCaptcha()) {
		//	$this->errorNewMessage("%errors_wrong_captcha%");
		//	$this->errorPanic();
		//}		

		//-------------------------------------------------------------------
		// Get necessary data
		$oTypes      = umiObjectTypesCollection::getInstance();
		$iBaseTypeId = $oTypes->getBaseType("webforms", "form");
		$iFormTypeId = getRequest('system_form_id');
		$sSenderIP   = getServer('REMOTE_ADDR');
		$iTime       = new umiDate( time() );
		$aAddresses  = getRequest('system_email_to');
		if(!is_array($aAddresses)) $aAddresses = array($aAddresses);
		$aRecipients = array();
		$webforms = cmsController::getInstance()->getModule('webforms');
		foreach($aAddresses as $address){
			$sEmailTo = $webforms->guessAddressValue($address);
			$sAddress = $webforms->guessAddressName($address);
			$aRecipients[] = array('email'=>$sEmailTo, 'name'=>$sAddress);
		}
		//if(!$oTypes->isExists($iFormTypeId) || $oTypes->getParentClassId($iFormTypeId) != $iBaseTypeId) {
		//	$this->errorNewMessage("%wrong_form_type%");
		//	$this->errorPanic();
		//}//
		//if(($ef = $this->checkRequiredFields($iFormTypeId)) !== true) {
		//	$this->errorNewMessage(getLabel('error-required_list').$this->assembleErrorFields($ef));
		//}
		//-------------------------------------------------------------------
		// Saving message and preparing it for sending
		$_REQUEST['data']['new']['sender_ip'] = $sSenderIP;  // Hack for saving files-only-forms
		$oObjectsCollection = umiObjectsCollection::getInstance();
		$iObjectId          = $oObjectsCollection->addObject($sAddress, $iFormTypeId);
		$oObjectsCollection->getObject($iObjectId)->setOwnerId(permissionsCollection::getInstance()->getUserId());
		cmsController::getInstance()->getModule('data')->saveEditedObject($iObjectId, true);
		$oObject            = $oObjectsCollection->getObject($iObjectId);
		$oObject->setValue('destination_address', $sEmailTo);
		$oObject->setValue('sender_ip', $sSenderIP);
		$oObject->setValue('sending_time', $iTime);
		$aMessage           = $webforms->formatMessage($iObjectId, true);
		//--------------------------------------------------------------------
		// Make an e-mail
		$oMail = new umiMail();
		//--------------------------------------------------------------------
		// Determine file fields
		$aFTypes     = array('file', 'img_file', 'swf_file');
		$aFields     = $oTypes->getType($oObject->getTypeId())->getAllFields();
		foreach($aFields as $oField) {
			$oType   = $oField->getFieldType();
			if(in_array($oType->getDataType(), $aFTypes)) {
				$oFile = $oObject->getValue($oField->getName());

				if($oFile instanceof umiFile) {
					$oMail->attachFile($oFile);
				} /*else {
					$this->errorNewMessage("%errors_wrong_file_type%");
					$this->errorPanic();
				}*/

			}
		}
		$recpCount = 0;
		foreach($aRecipients as $recipient) {
			foreach(explode(',', $recipient['email']) as $sAddress) {
				if(strlen(trim($sAddress))) {
					$oMail->addRecipient( trim($sAddress), $recipient['name'] );
					$recpCount++;
				}
			}
		}
		//if(!$recpCount) {
		//	$this->errorNewMessage(getLabel('error-no_recipients'));
		//}
		$oMail->setFrom($aMessage['from_email_template'], $aMessage['from_template']);
		//$oMail->setFrom($aMessage['from_template'],$aMessage['from_email_template']);
		$oMail->setSubject($aMessage['subject_template']);
		$oMail->setContent($aMessage['master_template']);
		$oMail->commit();
		$oMail->send();
		//--------------------------------------------------------------------
		// Send autoreply if should
		if(strlen($aMessage['autoreply_template'])) {
			$oMailReply = new umiMail();

			if (isset($aMessage['autoreply_email_recipient']) && $aMessage['autoreply_email_recipient']) {
				$oMailReply->addRecipient($aMessage['autoreply_email_recipient']);
			} else {
				$oMailReply->addRecipient($aMessage['from_email_template'], $aMessage['from_template']);
			}

			$oMailReply->setFrom($aMessage['autoreply_from_email_template'], $aMessage['autoreply_from_template']);
			$oMailReply->setSubject($aMessage['autoreply_subject_template']);
			$oMailReply->setContent($aMessage['autoreply_template']);
			$oMailReply->commit();
			$oMailReply->send();
		}
		//--------------------------------------------------------------------
		// Process events
		$oEventPoint = new umiEventPoint("webforms_post");
		$oEventPoint->setMode("after");
		$oEventPoint->setParam("email", $aMessage['from_email_template']);
		$oEventPoint->setParam("message_id", $iObjectId);
		$oEventPoint->setParam("form_id", $iFormTypeId);
		$oEventPoint->setParam("fio", $aMessage['from_template']);
		$this->setEventPoint($oEventPoint);
		//--------------------------------------------------------------------
		
		$result['status'] = 'ok';
		$buffer = outputBuffer::current();
		$buffer->charset('utf-8');
		$buffer->contentType('application/jsonp');
		$buffer->clear();
		$buffer->push(json_encode($result));
		$buffer->end();	
		
	}


};
?>
