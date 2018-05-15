<?php

namespace App\Http\Controllers;

use App\User;
use App\Category;

use Auth;

class CategoryController extends Controller
{
  // public function __construct($model, $controller, $action){
  //     parent::__construct($model, $controller, $action);
  //
  //     $Auth = new Auth($url);
  //     if(!$Auth->isLoggedIn()){
  //         header('Location: /user/login');
  //     }
  //     else {
  //
  //         $user = $Auth->getProfile();
  //         $this->user = $user;
  //         $this->set('user', $user);
  //         $this->set('header', true);
  //
  //         return view('category.index', [
  //           'user' => $user,
  //           'header' => true,
  //         ]);
  //     }
  // }

  public function index(){

      // $this->set('title', 'Categories');
      // $this->set('list', $this->Category->findAll());

      $Category = new Category;

      return view('category.index', [
        'title' => 'Categories',
        'list' => $Category->findAll(),
        'user' => Auth::user(),
      ]);
  }
}
