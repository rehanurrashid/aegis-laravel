<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmailJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    public function about(): Response
    {
        return Inertia::render('Public/About');
    }

    public function pricing(): Response
    {
        return Inertia::render('Public/Pricing');
    }

    public function contact(): Response
    {
        return Inertia::render('Public/Contact');
    }

    public function sendContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'max:200'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        dispatch(new SendEmailJob(
            template:       'emails.support.contact-form',
            data:           [
                'sender_name'  => $validated['name'],
                'sender_email' => $validated['email'],
                'subject'      => $validated['subject'],
                'message_body' => $validated['message'],
                'submitted_at' => now()->format('F j, Y \a\t g:i a'),
            ],
            recipientEmail: 'support@maatpracticefirm.com',
        ));

        return back()->with('success', 'Your message has been sent. We\'ll be in touch shortly.');
    }
}
