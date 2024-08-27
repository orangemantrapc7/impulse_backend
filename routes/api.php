<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/users', [UserController::class, 'store']);
Route::post('/send_request', [UserController::class, 'sendRequest']);
Route::post('/message/send', [UserController::class, 'sendMsgNotification']);
Route::post('/send_message/group', [UserController::class, 'sendgroupMsgNotification']);
Route::post('/create_group', [UserController::class, 'createGroup']);
Route::post('/add_member', [UserController::class, 'addMember']);
Route::post('/group_list', [UserController::class, 'groupList']);
Route::post('/user_group_list', [UserController::class, 'usergroupList']);
Route::post('/group_user_list', [UserController::class, 'groupuserList']);
