<?php

// Credentials for the `google-drive` disk that gate the live round-trip.
$credentialKeys = ['clientId', 'clientSecret', 'refreshToken'];

it('fails with a clear message when Google Drive credentials are empty', function () use ($credentialKeys) {
    foreach ($credentialKeys as $key) {
        config()->set("filesystems.disks.google-drive.{$key}", '');
    }

    $this->artisan('dots:drive-check')
        ->expectsOutputToContain('Google Drive credentials are not configured')
        ->assertExitCode(1);
});

it('does a real write/list/read/delete round-trip against Google Drive', function () use ($credentialKeys) {
    foreach ($credentialKeys as $key) {
        if (blank(config("filesystems.disks.google-drive.{$key}"))) {
            $this->markTestSkipped('Google Drive credentials are not configured — skipping the live round-trip.');
        }
    }

    $this->artisan('dots:drive-check')->assertExitCode(0);
});
