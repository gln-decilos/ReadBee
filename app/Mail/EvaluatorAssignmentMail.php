<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EvaluatorAssignmentMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $fullName;
    public string $schoolYearLabel;
    public string $quarterLabel;
    public string $assessmentDate;
    public string $gradeLabel;
    public string $sectionName;
    public string $assignedByName;
    public string $confirmUrl;

    public function __construct(
        string $fullName,
        string $schoolYearLabel,
        string $quarterLabel,
        string $assessmentDate,
        string $gradeLabel,
        string $sectionName,
        string $assignedByName,
        string $confirmUrl
    ) {
        $this->fullName = $fullName;
        $this->schoolYearLabel = $schoolYearLabel;
        $this->quarterLabel = $quarterLabel;
        $this->assessmentDate = $assessmentDate;
        $this->gradeLabel = $gradeLabel;
        $this->sectionName = $sectionName;
        $this->assignedByName = $assignedByName;
        $this->confirmUrl = $confirmUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ReadBee Evaluator Assignment Confirmation',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.evaluator-assignment',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
