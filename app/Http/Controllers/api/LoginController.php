<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Config;
use Validator;
use App\Models\api\UsersModel;
use Hash;
use Illuminate\Validation\Rule;
class LoginController extends Controller
{   
    public function one_validation_message($validator){
        $validation_messages = $validator->getMessageBag()->toArray();
        $validation_messages1 = array_values($validation_messages);

           $new_validation_messages = [];
           for ($i = 0; $i < count($validation_messages1); $i++) {
               $inside_element = count($validation_messages1[$i]);
                for ($j=0; $j < $inside_element; $j++) {
                   array_push($new_validation_messages,$validation_messages1[$i]);
                }
           }
      return implode(' ',$new_validation_messages[0]);
    }
    
    public function login(Request $request){
        // validation message form config/apimessage.php    
        $messages = [
            'user_name.required'=> Config::get( 'apimessage.USERNAME_REQUIRED'),
            'password.required'=> Config::get( 'apimessage.PASSWORD_REQUIRED'),
        ];
        // check validation
        $validator = Validator::make($request->all(), [
            'user_name' => 'required',
            'password' => 'required',
        ],$messages);

        if ($validator->fails()) {
            $error = $validator->errors();
            $result['status'] = '400';
            $result['message'] = $this->one_validation_message($validator);
            $result['data'] = json_decode("{}");
        }else{

            // $getHeaders = apache_request_headers();
            // $lancode = $getHeaders['lancode'];
            
            $objUsers = new UsersModel();
            $res = $objUsers->apilogin($request);

            if($res == "notregister"){
                $result['status'] = '203';
                $result['message'] = Config::get('apimessage.NOT_REGISTER');
                $result['data'] = json_decode("{}");
            }else{
                if($res == "wrong_password"){
                    $result['status'] = '203';
                    $result['message'] = Config::get('apimessage.WRONG_PASSWORD');
                    $result['data'] = json_decode("{}");
                }else if($res == "wrong"){
                    $result['status'] = '500';
                    $result['message'] = Config::get('apimessage.WRONG');
                    $result['data'] = json_decode("{}");
                }else{
                    array_walk_recursive($res[0],function(&$item){$item=strval($item);});
                    $result['status'] = '200';
                    $result['message'] = Config::get('apimessage.LOGIN_SUCCESS');
                    $result['data'] = [
                        'id' => $res[0]['id'],
                        'user_name' => $res[0]['user_name'],
                        'registered_at' => date("d M, Y H:s:i" , strtotime($res[0]['registered_at'])),
                        'user_role' => $res[0]['user_role'] == "U" ? "User" : "Admin",
                    ];
                    
                    $result['access_token'] = $res[0]['access_token'];
                }
            }
        }

        echo json_encode($result);
        exit;
    }


    public function updateProfile(Request $request) {
        // validation message form config/apimessage.php
        $messages = [
            'user_id.required'=> Config::get( 'apimessage.USERID_REQUIRED'),
            'user_id.numeric'=> Config::get( 'apimessage.USERID_NUMERIC'),
            'user_id.min'=> Config::get( 'apimessage.USERID_MIN'),
            'name.required'=> Config::get( 'apimessage.NAME_REQUIRED'),
            'user_name.required'=> Config::get( 'apimessage.USERNAME_REQUIRED'),
            'user_name.min'=> Config::get( 'apimessage.USERNAME_MIN'),
            'user_name.max'=> Config::get( 'apimessage.USERNAME_MAX'),
            'password.required'=> Config::get( 'apimessage.PASSWORD_REQUIRED'),
            'avatar.required'=> Config::get( 'apimessage.AVATAR_REQUIRED'),
            'email.required'=> Config::get( 'apimessage.EMAIL_REQUIRED'),
            'email.email'=> Config::get( 'apimessage.EMAIL_VALID'),
        ];

        // check validation
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|min:1',
            'name' => 'required',
            'user_name' => 'required|min:4|max:20|'.Rule::unique("users")->ignore($request->input("user_id")),
            'password' => 'required',
            'avatar' => 'dimensions:width=256,height=256',
            'email' => 'required|email|'.Rule::unique("users")->ignore($request->input("user_id")),
        ],$messages);

        if ($validator->fails()) {
            $error = $validator->errors();
            $result['status'] = '400';
            $result['message'] = $this->one_validation_message($validator);
            $result['data'] = json_decode("{}");
        }else{
            //get passed header token
            $getHeaders = apache_request_headers();
            $token = $getHeaders['token'];

            //check header token
            $objUsertoken = new UsersModel();
            $checktToken  = $objUsertoken->checktoken($token,$request->input('user_id'));
            if($checktToken){
                // update profile if all well
                $objProfile = new UsersModel();
                $responce = $objProfile->updateProfile($request);
                if($responce == "wrong"){
                        $result['status'] = '500';
                        $result['message'] = Config::get('apimessage.WRONG');
                        $result['data'] = json_decode("{}");
                }else{
                    if($responce){
                        array_walk_recursive($responce,function(&$item){$item=strval($item);});
                        $result['status'] = '200';
                        $result['message'] = Config::get('apimessage.PROFILE_SUCCESS');
                        $result['data'] = [
                            'id' => $responce['id'],
                            'name' => $responce['name'],
                            'user_name' => $responce['user_name'],
                            'avatar' => url("public/upload/avatar/".$responce['avatar']),
                            'email' => $responce['email'],
                            'registered_at' => date("d M, Y H:s:i" , strtotime($responce['registered_at'])),
                            'user_role' => $responce['user_role'] == "U" ? "User" : "Admin",
                        ];
                    }else{
                        $result['status'] = '500';
                        $result['message'] = Config::get('apimessage.WRONG');
                        $result['data'] = json_decode("{}");
                    }
                }
            }else{
                $result['status'] = '401';
                $result['message'] = Config::get('apimessage.UNAUTHORIZED');
                $result['data'] = json_decode("{}");
            }
        }

        echo json_encode($result);
        exit;
    }
    public function createpassword(Request $request) {
        print_r(Hash::make('123'));
    }

}
