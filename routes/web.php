<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return $app->version();
});


$app->group(['prefix' => 'v1'], function() use ($app) {

    $app->get('publications', 'PublicationController@index');
    $app->get('publications/{id}', 'PublicationController@show');

    $app->get('sections', 'SectionController@index');
    $app->get('sections/{id}', 'SectionController@show');

    $app->get('publications/{id}/sections', 'SectionController@indexForPublication');
    $app->get('publications/{publication_id}/sections/{id}', 'SectionController@showForPublication');

});