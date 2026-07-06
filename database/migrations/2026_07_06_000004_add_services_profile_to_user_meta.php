<?php
// Adds service_bio, service_headline, service_specialties as user_meta keys.
// No schema change needed — user_meta is key-value. This migration seeds the
// default keys for p_sarah so the public profile services section is populated.
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void {
        $now = now();
        $rows = [
            ['p_sarah', 'service_bio',         'I offer clinical supervision, peer consultation, and specialized training to support therapists in building confidence and competence. My approach is collaborative, strengths-based, and rooted in evidence-based practice.'],
            ['p_sarah', 'service_headline',     'Board-Approved Clinical Supervisor | Trauma & DBT Specialist'],
            ['p_sarah', 'service_specialties',  json_encode(['Trauma', 'DBT', 'Complex PTSD', 'Personality Disorders'])],
            ['p_sarah', 'years_experience',     '14'],
        ];
        foreach ($rows as [$userId, $key, $val]) {
            $existing = DB::table('user_meta')->where('user_id', $userId)->where('meta_key', $key)->first();
            if (!$existing) {
                DB::table('user_meta')->insert([
                    'id'         => 'um_' . Str::lower(Str::random(12)),
                    'user_id'    => $userId,
                    'meta_key'   => $key,
                    'meta_value' => $val,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
    public function down(): void {
        DB::table('user_meta')
            ->where('user_id', 'p_sarah')
            ->whereIn('meta_key', ['service_bio','service_headline','service_specialties','years_experience'])
            ->delete();
    }
};
