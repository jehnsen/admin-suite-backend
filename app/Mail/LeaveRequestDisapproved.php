<?php

namespace App\Mail;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveRequestDisapproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly LeaveRequest $leaveRequest
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Leave Request Disapproved – ' . $this->leaveRequest->leave_type,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.leave.disapproved',
        );
    }
}
