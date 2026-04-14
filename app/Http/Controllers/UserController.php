<?php

namespace App\Http\Controllers;

use App\AUni\Bean\Logger;
use App\AUni\Bean\Response;
use App\AUni\BO\UserBO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller {

    private $logger;

    public function __construct() {
        $this->logger = new Logger();
    }

    public function index(Request $request) {

        $userBO = new UserBO($this->logger);
        $users = $userBO->index();

        $response = ['status' => 'success', 'message' => '', 'users' => $users];
        return response()->json($response, 200);
    }

    public function getUsers(Request $request) {

        $userBO = new UserBO($this->logger);
        $users = $userBO->getUsers();

        $response = new Response();
        $response->status = 'success';
        $response->data = $users;

        return response()->json($response, 200);
    }

    public function saveUser(Request $request) {

        $this->logger->info('UserController->saveUser', [$request->all()]);

        $fields = (object) $request->all();
        $userBO = new UserBO($this->logger);
        $res = $userBO->saveUser($fields);

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = ['userId' => $res];
        } else {
            $response->status = 'error';
        }

        return response()->json($response, 200);
    }

    public function deleteUser(Request $request, $id) {

        $this->logger->info('UserController->deleteUser', ['id' => $id]);

        $userBO = new UserBO($this->logger);
        $res = $userBO->deleteUser($id);

        $response = new Response();
        if ($res !== false) {
            $response->status = 'success';
            $response->data = '';
        } else {
            $response->status = 'error';
        }

        return response()->json($response, 200);
    }

    public function store(Request $request) {

        $request->validate([
            'id' => 'required',
            'username' => 'required|max:255',
            'firstName' => 'required|max:255',
            'lastName' => 'required|max:255',
            'email' => 'required|email:rfc,dns',
        ]);

        $userBO = new UserBO($this->logger);
        $res = $userBO->store($request->id, $request->username, $request->firstName, $request->lastName, $request->email);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
        } else {
            $response->status = 'error';
        }

        return response()->json($response, 200);
    }

    public function destroy(Request $request, $id) {

        $userBO = new UserBO($this->logger);
        $res = $userBO->destroy($id);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
        } else {
            $response->status = 'error';
        }
        
        return response()->json($response, 200);
    }

    public function current(Request $request) {
        $userId = Auth::user()->user_id;

        $userBO = new UserBO($this->logger);
        $user = $userBO->show($userId);

        $response = new Response();
        if($user !== false) {
            $response->status = 'success';
            $response->data = $user;
        } else {
            $response->status = 'error';
        }

        return response()->json($response, 200);
    }

    public function getCurrentUserGroups(Request $request) {
        $userId = Auth::user()->user_id;

        $userBO = new UserBO($this->logger);
        $user = $userBO->getCurrentUserGroups($userId);

        $response = new Response();
        if($user !== false) {
            $response->status = 'success';
            $response->data = $user;
        } else {
            $response->status = 'error';
        }

        return response()->json($response, 200);
    }

    public function getMenus(Request $request) {

        $userId = Auth::user()->user_id;

        $userBO = new UserBO($this->logger);
        $menus = $userBO->getMenus($userId);

        $response = new Response();
        if($menus !== false) {
            $response->status = 'success';
            $response->data = $menus;
        } else {
            $response->status = 'error';
        }

        return response()->json($response, 200);
    }

    public function getBrokers() {
        $userBO = new UserBO($this->logger);
        $brokers = $userBO->getBrokers();
    
        $response = new Response();
        if (!empty($brokers)) {
            $response->status = 'success';
            $response->message = 'Brokers retrieved successfully';
            $response->data = $brokers; // Ensure brokers are under 'data'
        } else {
            $response->status = 'error';
            $response->message = 'No brokers found';
            $response->data = []; // Return empty array if no brokers
        }
    
        return response()->json($response, 200);
    }

    public function getUsersByGroups(Request $request) {

        $userBO = new UserBO($this->logger);

        $groupId = $request->groupId ?? null;
        $agents = $userBO->getUsersByGroups($groupId);
    
        $response = new Response();
        if (!empty($agents)) {
            $response->status = 'success';
            $response->message = 'Agents retrieved successfully';
            $response->data = $agents; 
        } else {
            $response->status = 'error';
            $response->message = 'No agents found';
            $response->data = [];
        }
    
        return response()->json($response, 200);
    }

    public function getAccountingUsers() {
        $userBO = new UserBO($this->logger);
        $accountingUsers = $userBO->getAccountingUsers();
    
        $response = new Response();
        if (!empty($accountingUsers)) {
            $response->status = 'success';
            $response->message = 'Accounting Users retrieved successfully';
            $response->data = $accountingUsers; // Ensure brokers are under 'data'
        } else {
            $response->status = 'error';
            $response->message = 'No accounting Users found';
            $response->data = []; // Return empty array if no brokers
        }
    
        return response()->json($response, 200);
    }

    public function getSupportUsers() {
        $userBO = new UserBO($this->logger);
        $supportUsers = $userBO->getSupportUsers(); // Call a method to retrieve support users
    
        $response = new Response();
        if (!empty($supportUsers)) {
            $response->status = 'success';
            $response->message = 'Support Users retrieved successfully';
            $response->data = $supportUsers; // Ensure support users are under 'data'
        } else {
            $response->status = 'error';
            $response->message = 'No support users found';
            $response->data = []; // Return empty array if no users
        }
    
        return response()->json($response, 200);
    }
    
}