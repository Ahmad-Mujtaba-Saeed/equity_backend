<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EducationNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $frontendUrl;
    public $logoUrl;

    public function __construct($data)
    {
        $this->data = $data;
        $this->frontendUrl = config('app.frontend_url', 'https://equitycircle.techtrack.online');
        $this->logoUrl = asset('logo/Equity_Circle_full.png');
    }

    public function build()
    {
        return $this->subject('New Educational Content on Equity Circle')
                    ->view('emails.education-notification')
                    ->with([
                        'data' => $this->data,
                        'frontendUrl' => $this->frontendUrl,
                        'logoUrl' => $this->logoUrl
                    ]);
    }
}
