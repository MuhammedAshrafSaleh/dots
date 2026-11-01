<?php

// Credentials for the `google-drive` disk that gate any real download.
$credentialKeys = ['clientId', 'clientSecret', 'refreshToken'];

it('fails with a clear message when Google Drive credentials are empty', function () use ($credentialKeys) {
    foreach ($credentialKeys as $key) {
        config()->set("filesystems.disks.google-drive.{$key}", '');
    }

    $this->artisan('dots:backup-download')
        ->expectsOutputToContain('Google Drive credentials are not configured')
        ->assertExitCode(1);
});

it('downloads the most recent backup when Google Drive is configured', function () use ($credentialKeys) {
    foreach ($credentialKeys as $key) {
        if (blank(config("filesystems.disks.google-drive.{$key}"))) {
            $this->markTestSkipped('Google Drive credentials are not configured — skipping the live download.');
        }
    }

    $dest = sys_get_temp_dir().'/dots-backup-download-test-'.now()->format('Ymd-His');

    $this->artisan('dots:backup-download', ['--dest' => $dest])
        ->assertExitCode(0);

    expect(glob($dest.'/*.zip'))->not->toBeEmpty();

    // Clean up the downloaded archive — never leave backup data on disk.
    array_map('unlink', glob($dest.'/*') ?: []);
    @rmdir($dest);
});
