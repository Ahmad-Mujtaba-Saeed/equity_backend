<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PostNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $frontendUrl;
    public $logoUrl;

    public function __construct($data)
    {
        $this->data = $data;
        $this->frontendUrl = config('app.frontend_url', 'https://equitycircle.techtrack.online');
        // Use absolute URL for logo with public_path
        $this->logoUrl = asset('logo/Equity_Circle_full.png');
    }

    public function build()
    {
        $subject = match($this->data['type']) {
            'new_post' => 'New Post on Equity Circle',
            'like' => 'Someone liked your post on Equity Circle',
            'comment' => 'New comment on your post on Equity Circle',
            'reply' => 'New reply to your comment on Equity Circle',
            default => 'Notification from Equity Circle'
        };

        return $this->subject($subject)
                    ->view('emails.post-notification')
                    ->with([
                        'data' => $this->data,
                        'frontendUrl' => $this->frontendUrl,
                        'logoUrl' => $this->logoUrl
                    ]);
    }
}
