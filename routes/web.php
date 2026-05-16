<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/docs');
});

Route::get('/docs', function () {
    return view('docs.swagger');
});

Route::get('/graphiql', function () {
    return view('docs.graphiql');
});

Route::get('/openapi.json', function () {
    return response()->json(json_decode(file_get_contents(public_path('openapi.json')), true));
});
