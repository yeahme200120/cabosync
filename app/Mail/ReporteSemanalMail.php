<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class ReporteSemanalMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $empresaNombre;
    public string $semanaTexto;
    public string $mensajePersonalizado;
    public string $pdfPath;
    public string $excelPath;
    public string $week;

    public function __construct(
        string $empresaNombre,
        string $semanaTexto,
        string $mensajePersonalizado,
        string $pdfPath,
        string $excelPath,
        string $week
    ) {
        $this->empresaNombre        = $empresaNombre;
        $this->semanaTexto          = $semanaTexto;
        $this->mensajePersonalizado = $mensajePersonalizado;
        $this->pdfPath              = $pdfPath;
        $this->excelPath            = $excelPath;
        $this->week                 = $week;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reporte Semanal de Asistencia - ' . $this->empresaNombre . ' - ' . $this->semanaTexto,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reporte-semanal',
            with: [
                'empresaNombre'        => $this->empresaNombre,
                'semanaTexto'          => $this->semanaTexto,
                'mensajePersonalizado' => $this->mensajePersonalizado,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('public', $this->pdfPath)
                ->as("Reporte-{$this->week}.pdf")
                ->withMime('application/pdf'),

            Attachment::fromStorageDisk('public', $this->excelPath)
                ->as("Reporte-{$this->week}.xlsx")
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}