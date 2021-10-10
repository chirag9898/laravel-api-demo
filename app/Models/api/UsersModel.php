<?php

namespace App\Models\api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Hash;
use File;
class UsersModel extends Model
{
    use HasFactory;
    protected $table = "users";

    // for api-login check 
    public function apilogin($request){
        $selectedUser = UsersModel::where('user_name',$request->input('user_name'))
                    ->where("user_role","U")
                    ->get()->toArray();
        if(!empty($selectedUser)){
            if(Hash::check($request->input('password'), $selectedUser[0]['password'])){
                $access_token = bin2hex(random_bytes(50));
                $objToken = UsersModel::find($selectedUser[0]['id']);
                $objToken->remember_token = $access_token;
                if($objToken->save()){
                    $selectedUser[0]['access_token'] = $access_token;
                    return $selectedUser;
                }else{
                    return "wrong";
                }
            }else{
                return "wrong_password";
            }
            
        }else{
            return "notregister";
        }
    }

    // for check header token
    public function checktoken($token,$user_id){

        $count = UsersModel::where("remember_token",$token)->where("id",$user_id)->count();
        if($count == 0){
            return false;
        }else{
            return true;
        }
    }

    // for update profile
    public function updateProfile($request){
        $currentUser = UsersModel::find($request->input("user_id"));

        // check avatar has file or empty
        if ($request->file('avatar') !="") {

            // unlink old file
            $existImage = public_path('/upload/avatar/').$currentUser->avatar;
            if (File::exists($existImage)) { 
                // unlink or remove previous company image from folder
                File::delete($existImage);
            }

            // upload new file
            $image = $request->file('avatar');
            $profileimage = time() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('/upload/avatar/');
            $image->move($destinationPath, $profileimage);

            // new file name for store database
            $currentUser->avatar = $profileimage;
        }

        $currentUser->name = $request->input("name");
        $currentUser->user_name = $request->input("user_name");
        $currentUser->password = Hash::make($request->input("password"));
        $currentUser->email = $request->input("email");
        $currentUser->updated_at = date("Y-m-d h:i:s");
        // echo "stringifiedi";die;
        if($currentUser->save()){
            // $selectedUser[0]['access_token'] = $access_token;
            return UsersModel::find($request->input("user_id"))->toArray();
        }else{
            return "wrong";
        }
    }
}
