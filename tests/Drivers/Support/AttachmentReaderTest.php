<?php

use Nettsite\NettMail\Core\Drivers\Support\AttachmentReader;

it('reads the contents of an existing file', function () {
    $tmpFile = tempnam(sys_get_temp_dir(), 'nettmail');
    file_put_contents($tmpFile, 'attachment contents');

    expect(AttachmentReader::read($tmpFile))->toBe('attachment contents');

    unlink($tmpFile);
});

it('throws when the file does not exist', function () {
    AttachmentReader::read('/nonexistent/path/to/file.txt');
})->throws(RuntimeException::class);
