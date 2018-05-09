<?php

namespace App\Http\Controllers;

class RssController extends Controller
{
  public function parties() {
      $Parties = new Party;
      // $this->set('parties', $Parties->findAll());
      
      return $Parties->findAll();
  }

  public function groups() {
      $Groups = new Group;
      // $this->set('groups', $Groups->findAll());

      return $Groups->findAll();
  }

}
