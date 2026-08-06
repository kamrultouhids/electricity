<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $bodyMessage; // <- rename to avoid conflict

    public function __construct(
        public string $subjectText,  // rename if you like
        string $bodyMessage         // rename this too
    ) {
        $this->bodyMessage = $bodyMessage;
    }


    public function build()
    {
            return $this->subject($this->subjectText)
                    ->view('email.contact-reply')
                    ->with('bodyMessage', $this->bodyMessage);
    }

    // /**
    //  * Create a new message instance.
    //  */
    // public function __construct(public $email, public $subject, public $message)
    // {
    //     $this->email = $email;
    //     $this->subject = $subject;
    //     $this->message = $message;
    // }

    // /**
    //  * Get the message envelope.
    //  */
    // public function envelope(): Envelope
    // {
    //     return new Envelope(
    //         subject: $this->subject,
    //     );
    // }

    // /**
    //  * Get the message content definition.
    //  */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'email.contact-reply',
    //         with: [
    //             'email' => $this->email,
    //             'subject' => $this->subject,
    //             'message' => $this->message,
    //         ],
    //     );
    // }

    // /**
    //  * Get the attachments for the message.
    //  *
    //  * @return array<int, \Illuminate\Mail\Mailables\Attachment>
    //  */
    // public function attachments(): array
    // {
    //     return [];
    // }
}
