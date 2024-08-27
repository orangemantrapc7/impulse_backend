<?php

namespace App\Http\Controllers;

use App\Models\Notification as ModelsNotification;
use Illuminate\Http\Request;
use App\Models\User;
use Validator;
use Hash;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data, including mobile_uid, device_id, and device_token
        try {
            $validation = Validator::make($request->all(), [

                'device_token' => 'required',
                'mobile_uid' => 'required',
            ]);
            if ($validation->fails()) {
                $response = [
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validation->errors(),
                ];
                return response()->json($response, 200);
            }
            $chk_emil = User::where('email', $request->email)->first();
            if ($chk_emil) {
                $response = [
                    'status' => false,
                    'data' => null,
                    'message' => 'This email id already register',
                ];
                return response()->json($response, 200);
            }
            $data['name'] = $request->name;
            $data['mobile_uid'] = $request->mobile_uid;
            $data['password'] = $request->password;
            $data['email'] = $request->email;
            $data['device_token'] = $request->device_token;
            $data['device_id'] = $request->device_id;
            $user = User::create($data);
            $response = [
                'status' => true,
                'data' => $user,
                'message' => 'successfully',
            ];
            return response()->json($response, 200);
        } catch (RequestException $exception) {
            $response = [
                'status' => false,
                'message' => 'something went worng',
                'data' => null,
            ];
            return response()->json($response, 200);
        }
    }

    public function updateFcm(Request $request)
    {
        // Validate the request data, including mobile_uid, device_id, and device_token
        try {
            $validation = Validator::make($request->all(), [

                'device_token' => 'required',
                'mobile_uid' => 'required',
            ]);
            if ($validation->fails()) {
                $response = [
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validation->errors(),
                ];
                return response()->json($response, 200);
            }
            $chk_user = User::where('mobile_uid', $request->mobile_uid)->first();
           
            if (empty($chk_user)) {
              
                $response = [
                    'status' => false,
                    'data' => null,
                    'message' => 'This user id dose not exit.',
                ];
                return response()->json($response, 200);
            }    
            $mobile_uid = $request->mobile_uid;
            $device_token = $request->device_token;
           
            $user = User::where('mobile_uid',$mobile_uid)->update(['device_token'=>$device_token]);
            $response = [
                'status' => true,
                'data' => $user,
                'message' => 'successfully',
            ];
            return response()->json($response, 200);
        } catch (RequestException $exception) {
            $response = [
                'status' => false,
                'message' => 'something went worng',
                'data' => null,
            ];
            return response()->json($response, 200);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function sendRequest(Request $request)
    {
        $to_name = $request->to_name;
        $to_udi = $request->to_udi;
        $from_name = $request->from_name;
        $from_uid = $request->from_id;

        // Fetch FCM token
        $getfcm = User::where('mobile_uid', $to_udi)->first();

        if (!$getfcm || !$getfcm->device_token) {
            return response()->json([
                'status' => false,
                'data' => null,
                'message' => 'FCM token not found'
            ], 404);
        }

        $url = 'https://fcm.googleapis.com/fcm/send';

        $FcmToken[] = $getfcm->device_token;

        $serverKey = 'AAAAuHNlvpg:APA91bHvDHnC-aMHl_-PHhrQXzj1W76WZHawN23CQWCY47ls9CMAmrDLk0WFGjFjv0jliqTq88-Yp25NZlvut9wG-P8YaLNvqPyk9vr_EqyBn8DDruXvkBCi64YEdLa3ZeOyWxZbLIet';

        $data = [
            "registration_ids" => $FcmToken,
            "notification" => [
                "title" => 'New Friend Request',
                "body" => 'You have a new friend request',
                "type" => "request",
                "from_id" => $from_uid,
                "from_name" => $from_name,
                "to_name" => $to_name,
                "to_id" => $to_udi,
            ],
        ];
        $encodedData = json_encode($data);

        $headers = [
            'Authorization: key=' . $serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);
        $result = curl_exec($ch);
        // dd($result);die;
        if ($result === false) {
            $response = [
                'status' => false,
                'data' => null,
                'message' => 'Curl failed: ' . curl_error($ch),
            ];
            curl_close($ch);
            return response()->json($response, 500);
        } else {

            curl_close($ch);

            $data = [
                'to_id' => $to_udi,
                'to_name' => $to_name,
                'from_id' => $from_uid,
                'from_name' => $from_name,
                'type' => "request",
            ];

            try {
                DB::table('notifications')->insert($data);
                $response = [
                    'status' => true,
                    'data' => null,
                    'message' => 'Notification sent and saved successfully',
                ];
            } catch (\Exception $e) {
                $response = [
                    'status' => false,
                    'data' => null,
                    'message' => 'Database insert failed: ' . $e->getMessage(),
                ];
            }
            return response()->json($response, 200);
        }
    }


    public function sendMsgNotification(Request $request)
    {
        $to_name = $request->to_name;
        $to_uid = $request->to_uid;
        $from_name = $request->from_name;
        $from_uid = $request->from_id;
        // $msg = $request->msg;
        $activity = $request->activity;
        $img = $request->file('img');
        if($img != 'null'){
        
        $msg = "Just completed " . $activity . " activity and shared a photo.";
       }else{
        $msg = $request->msg;
       }
        // Fetch FCM token
        $getfcm = User::where('mobile_uid', $to_uid)->first();

        if (!$getfcm || !$getfcm->device_token) {
            return response()->json([
                'status' => false,
                'data' => null,
                'message' => 'FCM token not found'
            ], 404);
        }


        $url = 'https://fcm.googleapis.com/fcm/send';
        $FcmToken[] = $getfcm->device_token;
        $serverKey = 'AAAAuHNlvpg:APA91bHvDHnC-aMHl_-PHhrQXzj1W76WZHawN23CQWCY47ls9CMAmrDLk0WFGjFjv0jliqTq88-Yp25NZlvut9wG-P8YaLNvqPyk9vr_EqyBn8DDruXvkBCi64YEdLa3ZeOyWxZbLIet';

        $data = [
            "registration_ids" => $FcmToken,
            "notification" => [
                "title" => $from_name,
                "body" => $msg,
                "type" => "message",
                "from_id" => $from_uid,
                "from_name" => $from_name,
                "to_name" => $to_name,
                "to_id" => $to_uid,
                "activity" => $activity,
            ],
        ];

        $encodedData = json_encode($data);

        $headers = [
            'Authorization:key=' . $serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);
        $result = curl_exec($ch);
        // dd($result);
        if ($result === false) {
            $response = [
                'status' => false,
                'data' => null,
                'message' => 'Curl failed: ' . curl_error($ch),
            ];
            curl_close($ch);
            return response()->json($response, 500);
        } else {
            try {
                curl_close($ch);
                ModelsNotification::create([
                    'to_id' => $to_uid,
                    'to_name' => $to_name,
                    'from_id' => $from_uid,
                    'from_name' => $from_name,
                    'type' => 'message',
                    'message' => $msg

                ]);
                $response = [
                    'status' => true,
                    'data' => null,
                    'message' => 'Notification sent and saved successfully',
                ];
            } catch (\Throwable $th) {
                $response = [
                    'status' => false,
                    'data' => null,
                    'message' => 'Database insert failed: ' . $th->getMessage(),
                ];
            }
            return response()->json($response, 200);
        }
    }

    public function sendgroupMsgNotification(Request $request)
    {             
        $group_id = $request->group_id;
        $from_name = $request->from_name;        
        $from_uid = $request->from_id;
        // $msg = $request->msg;
        $activity = $request->activity;
        $img = $request->file('img');
        if($img != 'null'){
        
        $msg = "Just completed " . $activity . " activity and shared a photo.";
       }else{
        $msg = $request->msg;
       }
       $fetch_user_id = DB::table('group_members')->where('group_id', $group_id)->where('user_id','!=', $from_uid)->pluck('user_id');
       
       $getfcm = User::whereIn('mobile_uid', $fetch_user_id)->pluck('device_token');
      
        // if (!$getfcm || !$getfcm) {
        //     return response()->json([
        //         'status' => false,
        //         'data' => null,
        //         'message' => 'FCM token not found'
        //     ], 404);
        // }


        $url = 'https://fcm.googleapis.com/fcm/send';
        $FcmToken = $getfcm;
        
        $serverKey = 'AAAAuHNlvpg:APA91bHvDHnC-aMHl_-PHhrQXzj1W76WZHawN23CQWCY47ls9CMAmrDLk0WFGjFjv0jliqTq88-Yp25NZlvut9wG-P8YaLNvqPyk9vr_EqyBn8DDruXvkBCi64YEdLa3ZeOyWxZbLIet';

        $data = [
            "registration_ids" => $FcmToken,
            "notification" => [
                "title" => $from_name,
                "body" => $msg,
                "type" => "message",
                "from_id" => $from_uid,
                "from_name" => $from_name,
                "activity" => $activity,
            ],
        ];

        $encodedData = json_encode($data);

        $headers = [
            'Authorization:key=' . $serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);
        $result = curl_exec($ch);
        
        if ($result === false) {
            $response = [
                'status' => false,
                'data' => null,
                'message' => 'Curl failed: ' . curl_error($ch),
            ];
            curl_close($ch);
            return response()->json($response, 500);
        } else {
            try {
                curl_close($ch);
                ModelsNotification::create([                   
                    'from_id' => $from_uid,
                    'from_name' => $from_name,
                    'type' => 'message',
                    'message' => $msg

                ]);
                $response = [
                    'status' => true,
                    'data' => null,
                    'message' => 'Notification sent and saved successfully',
                ];
            } catch (\Throwable $th) {
                $response = [
                    'status' => false,
                    'data' => null,
                    'message' => 'Database insert failed: ' . $th->getMessage(),
                ];
            }
            return response()->json($response, 200);
        }
    }

    public function createGroup(Request $request)
    {
        // Validate the request data, including mobile_uid, device_id, and device_token
        try {
            $validation = Validator::make($request->all(), [

                'group_name' => 'required',
                'group_img' => 'required',
                'user_id' => 'required',
            ]);
            if ($validation->fails()) {
                $response = [
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validation->errors(),
                ];
                return response()->json($response, 200);
            }
            $img = $request->group_img;            
            
            $data['group_name'] = $request->group_name;
            $data['group_image'] = $img;
            
            $groups = DB::table('groups')->insertGetId($data);
            $data['group_id'] = $groups;

            $data1['group_id'] = $groups;
            $data1['user_id'] = $request->user_id;
            
            $groups_add = DB::table('group_members')->insertGetId($data1);

            $response = [
                'status' => true,
                'data' => $data,
                'message' => 'successfully',
            ];
            return response()->json($response, 200);
        } catch (RequestException $exception) {
            $response = [
                'status' => false,
                'message' => 'something went worng',
                'data' => null,
            ];
            return response()->json($response, 200);
        }
    }

    public function addMember(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [

                'group_id' => 'required',
                'user_id' => 'required',
            ]);
            if ($validation->fails()) {
                $response = [
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validation->errors(),
                ];
                return response()->json($response, 200);
            }
            $data['group_id'] = $request->group_id;
            $data['user_id'] = $request->user_id;
            
            
            
            $groups = DB::table('group_members')->insertGetId($data);
            $response = [
                'status' => true,
                'data' => $data,
                'message' => 'successfully',
            ];
            return response()->json($response, 200);
        } catch (RequestException $exception) {
            $response = [
                'status' => false,
                'message' => 'something went worng',
                'data' => null,
            ];
            return response()->json($response, 200);
        }
    }
    public function groupList(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
               
                'user_id' => 'required',
            ]);
            if ($validation->fails()) {
                $response = [
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validation->errors(),
                ];
                return response()->json($response, 200);
            }
            $user_id = $request->user_id;
            $get_group_ids = DB::table('group_members')->where('user_id', $user_id)->pluck('group_id');

            $groups = DB::table('groups')
                ->where('status', 1)
                ->whereNotIn('id', $get_group_ids)
                ->get();        
            $response = [
                'status' => true,
                'data' => $groups,
                'message' => 'successfully',
            ];
            return response()->json($response, 200);
        } catch (RequestException $exception) {
            $response = [
                'status' => false,
                'message' => 'data not found',
                'data' => null,
            ];
            return response()->json($response, 200);
        }
    }

    public function usergroupList(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
               
                'user_id' => 'required',
            ]);
            if ($validation->fails()) {
                $response = [
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validation->errors(),
                ];
                return response()->json($response, 200);
            }
            $user_id = $request->user_id;
            $get_group_ids = DB::table('group_members')->where('user_id', $user_id)->pluck('group_id');

            $groups = DB::table('groups')
                ->where('status', 1)
                ->whereIn('id', $get_group_ids)
                ->get();        
            $response = [
                'status' => true,
                'data' => $groups,
                'message' => 'successfully',
            ];
            return response()->json($response, 200);
        } catch (RequestException $exception) {
            $response = [
                'status' => false,
                'message' => 'data not found',
                'data' => null,
            ];
            return response()->json($response, 200);
        }
    }
    public function groupuserList(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
               
                'group_id' => 'required',
            ]);
            if ($validation->fails()) {
                $response = [
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validation->errors(),
                ];
                return response()->json($response, 200);
            }
            $group_id = $request->group_id;
            $get_user_ids = DB::table('group_members')->where('group_id', $group_id)->pluck('user_id');

            $users = DB::table('users')
                ->select('id','mobile_uid','name','email')              
                ->whereIn('mobile_uid', $get_user_ids)
                ->get(); 
            foreach($users as $key=>$val){
                $val->profile_img = 'null';
            }
            $response = [
                'status' => true,
                'data' => $users,
                'message' => 'successfully',
            ];
            return response()->json($response, 200);
        } catch (RequestException $exception) {
            $response = [
                'status' => false,
                'message' => 'data not found',
                'data' => null,
            ];
            return response()->json($response, 200);
        }
    }
}
