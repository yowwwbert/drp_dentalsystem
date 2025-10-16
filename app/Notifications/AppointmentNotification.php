<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Appointment\Appointment;
use Carbon\Carbon;

class AppointmentNotification extends Notification
{
    use Queueable;

    protected $appointment;
    protected $type;
    protected $reason;

    public function __construct(Appointment $appointment, string $type, ?string $reason = null)
    {
        $this->appointment = $appointment;
        $this->type = $type;
        $this->reason = $reason;
    }

    public function via($notifiable)
    {
        // For 'no show' or 'completed', only notify the patient or dentist
        if (in_array($this->type, ['no show', 'completed'])) {
            if (
                ($notifiable->user_type === 'Patient' && $notifiable->user_id === $this->appointment->patient_id) ||
                ($notifiable->user_type === 'Dentist' && $notifiable->user_id === $this->appointment->dentist_id)
            ) {
                return ['mail', 'database'];
            }
            return [];
        }

        // For other types, notify as usual
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $schedule = $this->appointment->schedule;
        $startTime = Carbon::parse($schedule->start_time, 'Asia/Manila')->format('h:i A');
        $date = Carbon::parse($schedule->date)->format('F j, Y');

        $mailMessage = (new MailMessage)->greeting('Hello ' . $notifiable->patient_last_name . ',');

        switch ($this->type) {
            case 'created':
                $mailMessage
                    ->subject('Your Appointment Has Been Scheduled')
                    ->line('Your appointment has been successfully scheduled.')
                    ->line('**Details:**')
                    ->line('Date: ' . $date)
                    ->line('Time: ' . $startTime)
                    ->line('Branch: ' . optional($this->appointment->branch)->branch_name ?? 'N/A')
                    ->line('Dentist: ' . optional($this->appointment->dentist)->dentist_last_name ?? 'N/A')
                    ->line('Treatments: ' . $this->appointment->treatments->pluck('name')->implode(', '))
                    ->action('View Appointment', url('/dashboard'))
                    ->line('Thank you for choosing our services!');
                break;

            case 'cancelled':
                $mailMessage
                    ->subject('Your Appointment Has Been Cancelled')
                    ->line('Your appointment has been cancelled.')
                    ->line('**Details:**')
                    ->line('Date: ' . $date)
                    ->line('Time: ' . $startTime)
                    ->line('Reason: ' . ($this->reason ?? 'No reason provided'))
                    ->action('Book a New Appointment', url('/appointment'))
                    ->line('We hope to serve you again soon!');
                break;

            case 'rescheduled':
                $mailMessage
                    ->subject('Your Appointment Has Been Rescheduled')
                    ->line('Your appointment has been rescheduled.')
                    ->line('**New Details:**')
                    ->line('Date: ' . $date)
                    ->line('Time: ' . $startTime)
                    ->line('Branch: ' . optional($this->appointment->branch)->branch_name ?? 'N/A')
                    ->line('Dentist: ' . optional($this->appointment->dentist)->dentist_last_name ?? 'N/A')
                    ->line('Treatments: ' . $this->appointment->treatments->pluck('name')->implode(', '))
                    ->line('Reason: ' . ($this->reason ?? 'No reason provided'))
                    ->action('View Appointment', url('/dashboard'))
                    ->line('Thank you for choosing our services!');
                break;

            case 'no show':
                $mailMessage
                    ->subject('Your Appointment Was Marked as No Show')
                    ->line('Your appointment was marked as a no show due to being 15 minutes late.')
                    ->line('**Details:**')
                    ->line('Date: ' . $date)
                    ->line('Time: ' . $startTime)
                    ->line('Branch: ' . optional($this->appointment->branch)->branch_name ?? 'N/A')
                    ->line('Dentist: ' . optional($this->appointment->dentist)->dentist_last_name ?? 'N/A')
                    ->line('Reason: ' . ($this->reason ?? 'No reason provided'))
                    ->action('Book a New Appointment', url('/appointment'))
                    ->line('We hope to serve you again soon!');
                break;

            case 'completed':
                $mailMessage
                    ->subject('Your Appointment Has Been Completed')
                    ->line('Your appointment has been successfully completed.')
                    ->line('**Details:**')
                    ->line('Date: ' . $date)
                    ->line('Time: ' . $startTime)
                    ->line('Branch: ' . optional($this->appointment->branch)->branch_name ?? 'N/A')
                    ->line('Dentist: ' . optional($this->appointment->dentist)->dentist_last_name ?? 'N/A')
                    ->line('Treatments: ' . $this->appointment->treatments->pluck('name')->implode(', '))
                    ->action('View Appointment', url('/dashboard'))
                    ->line('Thank you for choosing our services!');
                break;
        }

        return $mailMessage;
    }

    public function toArray($notifiable)
    {
        $schedule = $this->appointment->schedule;
        $startTime = Carbon::parse($schedule->start_time, 'Asia/Manila')->format('h:i A');
        $date = Carbon::parse($schedule->date)->format('F j, Y');

        switch ($this->type) {
            case 'created':
    return [
        'title' => 'Booking Confirmed',
        'appointment_id' => $this->appointment->appointment_id,
        'message' => 'Your appointment is scheduled for ' . $date . ' at ' . $startTime . '. We look forward to seeing you!',
        'type' => 'appointment.created',
    ];
case 'cancelled':
    return [
        'title' => 'Cancellation Notice',
        'appointment_id' => $this->appointment->appointment_id,
        'message' => 'Your appointment scheduled for ' . $date . ' at ' . $startTime . ' has been cancelled. Contact us to rebook.',
        'type' => 'appointment.cancelled',
    ];
case 'rescheduled':
    return [
        'title' => 'Schedule Update',
        'appointment_id' => $this->appointment->appointment_id,
        'message' => 'Your appointment has been moved to ' . $date . ' at ' . $startTime . '. See you at the new time!',
        'type' => 'appointment.rescheduled',
    ];
case 'no show':
    return [
        'title' => 'Missed Visit Alert',
        'appointment_id' => $this->appointment->appointment_id,
        'message' => 'You were marked as a no-show for your ' . $date . ' appointment at ' . $startTime . '. Please contact us to reschedule.',
        'type' => 'appointment.no_show',
    ];
case 'completed':
    return [
        'title' => 'Visit Completed',
        'appointment_id' => $this->appointment->appointment_id,
        'message' => 'Thank you for visiting us on ' . $date . '! Your appointment has been completed.',
        'type' => 'appointment.completed',
    ];
default:
    return [];
        }
    }
}