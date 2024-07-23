<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegisterationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $maildata;
    /**
     * Create a new message instance.
     */
    public function __construct($maildata)
    {
        $this->maildata = $maildata;
    }


     /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Registeration')
                    ->view('frontend.email.registerMail'); // Replace with your email view file
    }
}
