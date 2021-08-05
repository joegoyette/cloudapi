<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', function () {
    return view('welcome');
});

Route::get('/{region}/ec2/instance/bestfit/{cpu}/{memory}', [ApiController::class,'instancebestfit']);
Route::get('/{region}/ec2/ebs/cost', [ApiController::class,'ebscost']);
Route::get('/{region}/ec2/egress/cost', [ApiController::class,'egresscost']);


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

require __DIR__.'/auth.php';

