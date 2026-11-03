<?php

namespace App\Http\Controllers;

use App\Models\ContactForm;
use Illuminate\Http\Request;

class ContactFormController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_contact' => 'required|string|max:255',
            'email_contact' => 'required|email|max:255',
            'phone_contact' => 'nullable|digits:10',
            'subject_contact' => 'nullable|string|max:255',
            'message_contact' => 'required|string|max:5000',
            'verify_contact' => 'required|in:4',
        ], [
            'verify_contact.in' => 'Please answer the verification question correctly.',
        ]);

        $message = trim($validated['message_contact']);
        if (filled($validated['subject_contact'] ?? null)) {
            $message = 'Subject: '.trim($validated['subject_contact'])."\n\n".$message;
        }

        ContactForm::create([
            'name' => $validated['name_contact'],
            'email' => $validated['email_contact'],
            'phone' => $validated['phone_contact'] ?: null,
            'message' => $message,
            'verification_answer' => $validated['verify_contact'],
        ]);

        return redirect()->back()->with('success', 'Thank you for your message. We will get back to you soon!');
    }
}
