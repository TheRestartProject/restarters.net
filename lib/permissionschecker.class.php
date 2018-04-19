<?php

class PermissionsChecker
{
    protected $user;
    protected $hostParties = array();

    public function __construct($user, $hostParties)
    {
        $this->user = $user;
        $this->hostParties = $hostParties;
    }

    public function userHasCreatePartyPermission()
    {
        return hasRole($this->user, 'Administrator') || hasRole($this->user, 'Host');
    }

    public function userHasEditPartyPermission($partyId)
    {
        return
            hasRole($this->user, 'Administrator') ||
            (hasRole($this->user, 'Host') && in_array($partyId, $this->hostParties));
    }
}