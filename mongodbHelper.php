<?php

class mongodbHelper {

	private $mongo;
	private $db;
	private $stateCollection;
	private $businessMsgCollection;

	public function __construct()
	{
		$this->m = new MongoClient();
		$this->db = $this->m->wasinbot;
		$this->stateCollection = $this->db->state;
		$this->businessMsgCollection = $this->db->businessMsg;
	}

	public function hasDocInStateCollection($chat_id)
	{
		$query = array("chat_id" => $chat_id);
		$doc = $this->stateCollection->findOne($query);
		if (empty($doc))
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	public function getDocInStateCollection($chat_id)
	{
		$query = array("chat_id" => $chat_id);
		$doc = $this->stateCollection->findOne($query);
		return $doc;
	}

	public function insertDocToStateCollection($chat_id, $state_id)
	{
		$doc = array("chat_id" => $chat_id,
					"state_id" => $state_id);
		$this->stateCollection->insert($doc);
	}

	public function updateDocToStateCollection($chat_id, $state_id)
	{
		$this->stateCollection->update(array("chat_id" => $chat_id),
                                array(
                                	"chat_id" => $chat_id,
                                	"state_id" => $state_id),
                                array("upsert" => false));
	}

	public function getStateIdFromDoc($doc)
	{
		return $doc["state_id"];
	}

	/**
		Update businessMsg doc with type-id.
		Consult google doc for type-id.

		It will update "type_id" field without remove other fields.
	*/
	public function updateBusinessMsgWithTypeId($chat_id, $type_id)
	{
		$this->businessMsgCollection->update(array("chat_id" => $chat_id), array('$set' => array("type_id" => $type_id, "update_at" => new MongoDate())), array("upsert" => true));
	}

	/**
		Update businessMsg doc with type-id.
		Consult google doc for productDescriptionText.

		It will update "productDescriptionText" field without remove other fields.
	*/
	public function updateBusinessMsgWithProductDescriptionText($chat_id, $prod_descriptionText)
	{
		$this->businessMsgCollection->update(array("chat_id" => $chat_id), array('$set' => array("productDescriptionText" => $prod_descriptionText, "update_at" => new MongoDate())), array("upsert" => true));
	}

	/**
		Update businessMsg doc with offerText.
		Consult google doc for offerText.

		It will update "offerText" field without remove other fields.
	*/
	public function updateBusinessMsgWithOfferText($chat_id, $offerText)
	{
		$this->businessMsgCollection->update(array("chat_id" => $chat_id), array('$set' => array("offerText" => $offerText, "update_at" => new MongoDate())), array("upsert" => true));
	}

	/**
		Update businessMsg doc with proposerEmail.
		Consult google doc for proposerEmail.

		It will update "proposerEmail" field without remove other fields.
	*/
	public function updateBusinessMsgWithProposerEmail($chat_id, $proposerEmail)
	{
		$this->businessMsgCollection->update(array("chat_id" => $chat_id), array('$set' => array("proposerEmail" => $proposerEmail, "update_at" => new MongoDate())), array("upsert" => true));
	}

	/**
		Update businessMsg doc with proposerFirstName.
		Consult google doc for proposerFirstName.

		It will update "proposerFirstName" field without remove other fields.
	*/
	public function updateBusinessMsgWithProposerFirstName($chat_id, $proposerFirstName)
	{
		$this->businessMsgCollection->update(array("chat_id" => $chat_id), array('$set' => array("proposerFirstName" => $proposerFirstName, "update_at" => new MongoDate())), array("upsert" => true));
	}

	/**
		Update businessMsg doc with status.
		Consult google doc for status.

		It will update "status" field without remove other fields.
	*/
	public function updateBusinessMsgWithStatus($chat_id, $status)
	{
		$this->businessMsgCollection->update(array("chat_id" => $chat_id), array('$set' => array("status" => $status, "update_at" => new MongoDate())), array("upsert" => true));
	}

	/**
		Get document via chat_id from businessMsg collection.

		@return Document
	*/
	public function getDocInBusinessMsgCollection($chat_id)
	{
		$query = array("chat_id" => $chat_id);
		$doc = $this->businessMsgCollection->findOne($query);
		return $doc;
	}
}

?>