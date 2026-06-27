<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OverviewController extends Controller
{
    public function index(Request $request): Response
    {
        $role = $request->user()?->role ?? 'guest';
        return Inertia::render('Shared/Overview', [
            'role'      => $role,
            'keyTerms'  => $this->keyTermsFor($role),
            'whyCards'  => $this->whyCardsFor($role),
            'howSteps'  => $this->howStepsFor($role),
            'faqs'      => $this->faqsFor($role),
        ]);
    }

    private function keyTermsFor(string $role): array
    {
        return [
            ['term' => 'Continuity Plan', 'def' => 'Your documented preparation for a critical incident.'],
            ['term' => 'Continuity Steward', 'def' => 'The colleague who will care for your practice if you can\'t.'],
            ['term' => 'Support Steward', 'def' => 'A trusted person who reports a critical incident if needed.'],
            ['term' => 'Vault', 'def' => 'Encrypted storage of supplemental documents, opened only during a verified incident.'],
        ];
    }

    private function whyCardsFor(string $role): array
    {
        return [
            ['title' => 'Continuity of care', 'body' => 'Your clients are looked after if life interrupts.'],
            ['title' => 'Clarity for loved ones', 'body' => 'A signed plan removes guesswork during a crisis.'],
            ['title' => 'Ethical responsibility', 'body' => 'A documented plan satisfies professional duty of care.'],
        ];
    }

    private function howStepsFor(string $role): array
    {
        return [
            ['step' => 1, 'title' => 'Build your plan', 'body' => 'Configure incident types and document your wishes.'],
            ['step' => 2, 'title' => 'Designate stewards', 'body' => 'Invite trusted colleagues to your roster.'],
            ['step' => 3, 'title' => 'Sign and attest', 'body' => 'Activate your plan and confirm vault contents annually.'],
        ];
    }

    private function faqsFor(string $role): array
    {
        return [
            ['q' => 'What if I never activate my plan?', 'a' => 'That\'s the goal. Your plan is insurance — quietly ready, rarely used.'],
            ['q' => 'How is the Vault secured?', 'a' => 'AES-256-GCM encryption; access is gated by a verified critical incident.'],
        ];
    }
}
