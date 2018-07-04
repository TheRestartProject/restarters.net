<?php

namespace App\Http\Controllers;

use Auth;
use App\User;
use App\Category;
use FixometerHelper;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

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

      $Category = new Category;

      return view('category.index', [
        'list' => $Category->findAll(),
        'categories'  => $Category->listed()
      ]);
      
  }

  public function getEditCategory($id) {
      if( !FixometerHelper::hasRole(Auth::user(), 'Administrator') )
        return redirect('/user/forbidden');

      $category = Category::find($id);

      $c = new Category;
      $categories = $c->listed();

      return view('category.edit', [
        'title' => 'Edit Category',
        'category'   => $category,
        'categories'  => $categories
      ]);
  }

  public function postEditCategory($id) {
      // if( !FixometerHelper::hasRole(Auth::user(), 'Administrator') )
      //   return redirect('/user/forbidden');
      //
      // $category = Category::find($id);
      //
      // $c = new Category;
      // $categories = $c->listed();
      //
      // return view('category.edit', [
      //   'title' => 'Edit Category',
      //   'category'   => $category,
      //   'categories'  => $categories
      // ]);
  }
}
