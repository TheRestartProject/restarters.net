<?php
namespace wouterNL\Drip\Interfaces;

interface DripInterface
{
    public function getCampaigns($params);
	public function fetchCampaign($params);
	public function getAccounts();
	public function deleteSubscriber($params);
	public function createOrUpdateSubscriber($params);
	public function fetchSubscriber($params);
	public function subscribeSubscriber($params);
	public function unsubscribeSubscriber($params);
	public function tagSubscriber($params);
	public function untagSubscriber($params);
	public function recordEvent($params);
    public function makeRequest($url, $params = array(), $req_method = self::GET);
	public function getRequestInfo();
	public function getErrorMessage();
    public function getErrorCode();
	public function _parseError($res);
}
