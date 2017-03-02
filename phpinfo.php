<?php
	require 'mongodbHelper.php';
	$db = new mongodbHelper();
	
	echo $db->hasDocInStateCollection(1) . "\n";	
	echo $db->getDocInStateCollection(1)["state_id"] . "\n";
	$db->insertDocToStateCollection(1,2);
	$db->updateDocToStateCollection(1,3);
	echo $db->getStateIdFromDoc($db->getDocInStateCollection(1)) . "\n";	
	$db->updateBusinessMsgWithTypeId(1,2);
	$db->updateBusinessMsgWithProductDescription(1,"adfasdfasdfasdf");
	$db->updateBusinessMsgWithOfferText(1, "sfa offer text");
	$db->updateBusinessMsgWithProposerEmail(1, "afasfasdfasdf email");
	$db->updateBusinessMsgWithProposerFirstName(1, "name");
	$db->updateBusinessMsgWithStatus(1, "sdf");
	$db->getDocInBusinessMsgCollection(1);
	$db->getDocInFreelanceworkMsgCollection(1);
	$db->updateFreelanceWorkMsgWithTypeId(1, 1);
	$db->updateFreelanceworkMsgWithIdeaText(1, "idea text");
	$db->updateFreelanceworkMsgWithBudgetTypeId(1, 2);
	$db->updateFreelanceworkMsgWithTimeTypeId(1, 2);
	$db->updateFreelanceworkMsgWithProposerEmail(1, "email");
	$db->updateFreelanceworkMsgWithProposerFirstName(1, "frist name");
	$db->updateFreelanceworkMsgWithStatus(1, "s");

	echo "success\n";
?>
