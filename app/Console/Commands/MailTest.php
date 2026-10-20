<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Mail;

class MailTest extends Command
{
    protected $signature = 'mail:test {email? : Recipient address (default: admin@dots.test)}';

    protected $description = 'Send a test email via the configured mailer to verify Mailpit (or any SMTP) is working';

    public function handle(): int
    {
        $to = $this->argument('email') ?? 'admin@dots.test';

        Mail::to($to)->send(new class extends Mailable
        {
            public function envelope(): Envelope
            {
                return new Envelope(subject: 'DOTS mail test — '.now()->toDateTimeString());
            }

            public function content(): Content
            {
                return new Content(htmlString: '<p style="font-family:sans-serif">Mail is working. Mailer: <strong>'.config('mail.default').'</strong></p>');
            }
        });

        $this->info("Test email sent to {$to} via ".config('mail.default').' mailer.');

        return self::SUCCESS;
    }
}
