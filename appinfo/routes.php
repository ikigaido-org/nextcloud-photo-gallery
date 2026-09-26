<?php
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only
return ['routes' => [
    ['name'=>'switch#start','url'=>'/account/start','verb'=>'POST'],
    ['name'=>'switch#finish','url'=>'/account/finish','verb'=>'POST'],
    ['name'=>'access#check','url'=>'/tools/access','verb'=>'GET'],
    ['name'=>'feed#rss','url'=>'/feed.rss','verb'=>'GET'],
    ['name'=>'feed#atom','url'=>'/feed.atom','verb'=>'GET'],
    ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
    ['name' => 'api#album', 'url' => '/api/v1/albums/{id}', 'verb' => 'GET', 'requirements' => ['id' => '\\d+']],
    ['name'=>'page#namedAlbum','url'=>'/a/{slug}','verb'=>'GET','requirements'=>['slug'=>'[a-z0-9-]+']],
    ['name'=>'page#album', 'url'=>'/album/{id}', 'verb'=>'GET', 'requirements'=>['id'=>'\\d+']],
    ['name'=>'media#preview', 'url'=>'/media/{id}/{fileId}/preview', 'verb'=>'GET', 'requirements'=>['id'=>'\\d+','fileId'=>'\\d+']],
    ['name'=>'media#stream', 'url'=>'/media/{id}/{fileId}/stream', 'verb'=>'GET', 'requirements'=>['id'=>'\\d+','fileId'=>'\\d+']],
    ['name'=>'media#metadata', 'url'=>'/media/{id}/{fileId}/metadata', 'verb'=>'GET', 'requirements'=>['id'=>'\\d+','fileId'=>'\\d+']],
    ['name' => 'admin#save', 'url' => '/settings', 'verb' => 'POST'],
]];
