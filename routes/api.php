<?php

Route::get('/', function () {
    return redirect('/api/v1');
});

Route::group(['prefix' => 'v1'], function () {

    Route::get('publications', 'PublicationController@index');
    Route::get('publications/{id}', 'PublicationController@show');

    // For debugging plaintext. Must be declared before sections/{id}
    Route::get('sections/{id}.txt', 'SectionController@contentPlaintext');

    Route::get('sections', 'SectionController@index');
    Route::get('sections/{id}', 'SectionController@show');

    Route::get('publications/{id}/sections', 'SectionController@indexForPublication');
    Route::get('publications/{publication_id}/sections/{id}', 'SectionController@showForPublication');

});
