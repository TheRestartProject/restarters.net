<?php

namespace App\Http\Controllers;

class RssController extends Controller
{
    public function parties()
    {
        $Parties = new Party;

        return $Parties->findAll();
    }

    public function groups()
    {
        $Groups = new Group;

        return $Groups->findAll();
    }
}
