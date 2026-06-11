<?php

use Nettsite\NettMail\Core\Drivers\Support\MultipartFormBuilder;

it('escapes quotes and backslashes in field names', function () {
    $form = new MultipartFormBuilder();
    $form->addField('weird "name"', 'value');

    expect($form->build())->toContain('name="weird \\"name\\""');
});

it('escapes quotes and backslashes in file names', function () {
    $form = new MultipartFormBuilder();
    $form->addFile('attachment', 'file "with" quotes.txt', 'content');

    expect($form->build())->toContain('filename="file \\"with\\" quotes.txt"');
});

it('escapes backslashes in field names and file names', function () {
    $form = new MultipartFormBuilder();
    $form->addFile('attachment', 'path\\to\\file.txt', 'content');

    expect($form->build())->toContain('filename="path\\\\to\\\\file.txt"');
});
