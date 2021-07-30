<?php

namespace App\Http\Controllers;

use App\User;
use Auth;
use FixometerFile;
use App\Helpers\Fixometer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AjaxController extends Controller
{
    /** this class exposes JSON objects  **/
    public function restarters_in_group()
    {
        if (isset($_GET['group']) && is_numeric($_GET['group'])) {
            $group = (int) $_GET['group'];

            $Users = new User;
            $restarters = $Users->inGroup($group);

            echo json_encode($restarters);
        }
    }

    public function restarters()
    {
        $Auth = new Auth($url);
        if (! $Auth->isLoggedIn()) {
            header('Location: /user/login');
        } else {
            $user = $Auth->getProfile();
            $this->user = $user;
            $this->set('user', $user);

            $Users = new User;
            $restarters = $Users->find(['idroles' => 4]);

            $response = '';
            foreach ($restarters as $c) {
                $response .= '<option value="'.$c->idusers.'">'.$c->name.'</option>';
            }

            echo $response;
        }
    }

    public function group_locations()
    {
        $Groups = new Group;
        $groups = $Groups->findAll();

        echo json_encode($groups);
    }

    public function party_data()
    {
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $party = $_GET['id'];
        } else {
            echo json_encode(['code'=>500, 'status'=>'danger', 'message' => 'Missing Parameter.']);

            return false;
        }

        $Party = new Party;
        $party = $Party->findOne($party);
        echo json_encode($party);

        return true;
    }

    public function category_list()
    {
        $Category = new Category;
        $categories = $Category->listed();

        $response = '';

        foreach ($categories as $cluster) {
            $response .= '<optgroup label="'.$cluster->name.'">';
            foreach ($cluster->categories as $c) {
                $response .= '<option value="'.$c->idcategories.'">'.$c->name.'</option>';
            }
            $response .= '</optgroup>';
        }

        echo $response;
    }

    public function delete_device_image()
    {
        $Auth = new Auth($url);
        if (! $Auth->isLoggedIn()) {
            header('Location: /user/login');
        } else {
            $user = $Auth->getProfile();
            $this->user = $user;
            $this->set('user', $user);
            if (isset($_POST['id']) && isset($_POST['file'])) {
                $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
                $filename = filter_var($_POST['file'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $File = new FixometerFile;
                $File->deleteImage($id, $filename);
                echo true;
            } else {
                echo false;
            }
        }
    }
}
