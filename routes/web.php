<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageForwardController;



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// For Church settings - Contacts & Message functionalities
Route::get('/', [UserController::class, 'home'])->name('home');
Route::get('/settings', [UserController::class, 'settings'])->name('church.settings');
Route::post('/settings', [UserController::class, 'saveChurchSettings'])->name('church.saveChurchSettings');
Route::post('/settings/template', [UserController::class, 'savetemplateSettings'])->name('church.savetemplateSettings');
Route::get('/listContacts', [UserController::class, 'listContacts'])->name('church.listContacts');
Route::get('/contactShow/{id}', [UserController::class, 'contactShow'])->name('church.contactShow');
Route::get('/sendmesage', [UserController::class, 'viewGlobalCustomMesage'])->name('church.viewGlobalCustomMesage');
Route::post('/sendmesage', [UserController::class, 'sendGlobalCustomMesage'])->name('church.sendGlobalCustomMesage');
Route::get('/approvemesage/{id}', [UserController::class, 'approveMessage'])->name('church.approveMessage');
Route::get('/disapprovemesage/{id}', [UserController::class, 'disapproveMessage'])->name('church.disapproveMessage');

//For Access Token - one time usage while setup
Route::get('/oauth', [UserController::class, 'initiateOAuth'])->name('church.initiateOAuth');
Route::get('/oauthred', [UserController::class, 'handleOAuthRedirect'])->name('church.handleOAuthRedirect');
Route::get('/get-token', [UserController::class, 'create_acc_token'])->name('church.createacctoken');


// For Automation
Route::get('/fetchcontacts', [UserController::class, 'fetchContacts'])->name('church.fetchContacts');
Route::get('/refresh-token', [UserController::class, 'refreshToken'])->name('church.refreshToken');
Route::get('/conversation', [ConversationController::class, 'message'])->name('conversation.conversation');

Route::get('/saveusers', [MessageForwardController::class, 'saveUserByRole'])->name('church.saveUserByRole');
Route::get('/countflagged', [MessageForwardController::class, 'countFlagged'])->name('church.countFlagged');
Route::get('/messageforwarding', [MessageForwardController::class, 'messageForwarding'])->name('church.messageForwarding');
Route::get('/prayercountmsg', [MessageForwardController::class, 'prayerCountMsg'])->name('church.prayerCountMsg');

// Route::get('/test', [UserController::class, 'testOpenAi'])->name('church.testOpenAi');