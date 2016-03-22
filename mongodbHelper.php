<?php

class mongodbHelper {

	private $mongo;
	private $db;
	private $stateCollection;
	private $businessMsgCollection;
	private $freelanceworkMsgCollection;

	/**
		Constructor method.
	*/
	public function __construct()
	{
		$this->m = new MongoClient();
		$this->db = $this->m->wasinbot;
		$this->stateCollection = $this->db->state;
		$this->businessMsgCollection = $this->db->businessMsg;
		$this->freelanceworkMsgCollection = $this->db->freelanceworkMsg;
	}

	/**
		Check if there is a document with input "chat_id" in state collection.

		@return true if there's a document with specified "chat_id", otherwise return false
	*/
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

	/**
		Get the document with specified "chat_id".

		@return document with specified "chat_id"
	*/
	public function getDocInStateCollection($chat_id)
	{
		$query = array("chat_id" => $chat_id);
		$doc = $this->stateCollection->findOne($query);
		return $doc;
	}

	/**
		Insert a new document into state collection with "state_id".
	*/
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

	/**
		Get document via chat_id from freelanceworkMsg collection.

		@return Document
	*/
	public function getDocInFreelanceworkMsgCollection($chat_id)
	{
		$query = array("chat_id" => $chat_id);
		$doc = $this->freelanceworkMsgCollection->findOne($query);
		return $doc;
	}

	/**
		Update freelanceworkMsg doc with type_id.
		Consult google doc for type_id.

		It will update "type_id" field without remove other fields.
	*/
	public function updateFreelanceworkMsgWithTypeId($chat_id, $type_id)
	{
		$this->freelanceworkMsgCollection->update(array("chat_id" => $chat_id), array('$set' => array("type_id" => $type_id, "update_at" => new MongoDate())), array("upsert" => true));
	}

	/**
		Update freelanceworkMsg doc with ideaText.
		Consult google doc for ideaText.

		It will update "ideaText" field without remove other fields.
	*/
	public function updateFreelanceworkMsgWithIdeaText($chat_id, $ideaText)
	{
		$this->freelanceworkMsgCollection->update(array("chat_id" => $chat_id), array('$set' => array("ideaText" => $ideaText, "update_at" => new MongoDate())), array("upsert" => true));
	}

	/**
		Update freelanceworkMsg doc with budgetTypeId.
		Consult google doc for budgetTypeId.

		It will update "budgetTypeId" field without remove other fields.
	*/
	public function updateFreelanceworkMsgWithBudgetTypeId($chat_id, $budgetTypeId)
	{
		$this->freelanceworkMsgCollection->update(array("chat_id" => $chat_id), array('$set' => array("budgetTypeId" => $budgetTypeId, "update_at" => new MongoDate())), array("upsert" => true));
	}

	/**
		Update freelanceworkMsg doc with timeTypeId.
		Consult google doc for timeTypeId.

		It will update "timeTypeId" field without remove other fields.
	*/
	public function updateFreelanceworkMsgWithTimeTypeId($chat_id, $timeTypeId)
	{
		$this->freelanceworkMsgCollection->update(array("chat_id" => $chat_id), array('$set' => array("timeTypeId" => $timeTypeId, "update_at" => new MongoDate())), array("upsert" => true));
	}

	/**
		Update freelanceworkMsg doc with proposerEmail.
		Consult google doc for proposerEmail.

		It will update "proposerEmail" field without remove other fields.
	*/
	public function updateFreelanceworkMsgWithProposerEmail($chat_id, $proposerEmail)
	{
		$this->freelanceworkMsgCollection->update(array("chat_id" => $chat_id), array('$set' => array("proposerEmail" => $proposerEmail, "update_at" => new MongoDate())), array("upsert" => true));
	}

	/**
		Update freelanceworkMsg doc with proposerFirstName.
		Consult google doc for proposerFirstName.

		It will update "proposerFirstName" field without remove other fields.
	*/
	public function updateFreelanceworkMsgWithProposerFirstName($chat_id, $proposerFirstName)
	{
		$this->freelanceworkMsgCollection->update(array("chat_id" => $chat_id), array('$set' => array("proposerFirstName" => $proposerFirstName, "update_at" => new MongoDate())), array("upsert" => true));
	}

	/**
		Update freelanceworkMsg doc with status.
		Consult google doc for status.

		It will update "status" field without remove other fields.
	*/
	public function updateFreelanceworkMsgWithStatus($chat_id, $status)
	{
		$this->freelanceworkMsgCollection->update(array("chat_id" => $chat_id), array('$set' => array("status" => $status, "update_at" => new MongoDate())), array("upsert" => true));
	}
}

?>