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
		Update business msg doc with type-id.
		Consult google doc for type-id.

		It will update "type_id" field without remove other fields.
	*/
	public function updateBusinessMsgWithTypeId($chat_id, $type_id)
	{
		$this->businessMsgCollection->update(array("chat_id" => $chat_id), array('$set' => array("type_id" => $type_id, "update_at" => new MongoDate())), array("upsert" => true));
	}
}

?>