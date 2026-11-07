<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomerResetPassword extends ResetPassword
{
    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = url(route('password.reset.form', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $brand = config('yuktione.name', 'YuktiOne');
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name') ?: $brand;

        $message = (new MailMessage)
            ->subject($brand.' Password Reset')
            ->view('emails.password-reset', [
                'brand' => $brand,
                'logoUrl' => asset('frontend/assets/Yuktione_logo.png'),
                'intro' => 'You are receiving this email because we received a password reset request for your '.$brand.' customer account.',
                'actionText' => 'Reset Password',
                'actionUrl' => $url,
                'expireText' => 'This password reset link will expire in '.config('auth.passwords.users.expire').' minutes.',
                'outro' => 'If you did not request a password reset, no further action is required.',
            ]);

        if (filled($fromAddress)) {
            $message->from($fromAddress, $fromName);
        }

        return $message;
    }
}
