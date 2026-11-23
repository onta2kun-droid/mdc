<?php

namespace Tests\Feature;

use App\Services\IcalParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian parser iCal defensif (risiko no. 1 pada LKM 9).
 */
class IcalParsingTest extends TestCase
{
    use RefreshDatabase;

    private const SAMPLE_ICS = <<<'ICS'
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Moodle//NonSBG//id
        CALSCALE:GREGORIAN
        X-WR-TIMEZONE:Asia/Makassar
        BEGIN:VEVENT
        UID:assignment-1234@moodle.kampus.ac.id
        SUMMARY:Tugas 1 - Membuat Dashboard
        DESCRIPTION:Kerjakan tugas berikut.\nCourse: Komputasi Awan\n
        DTSTART;TZID=Asia/Makassar:20261120T235900
        DTEND;TZID=Asia/Makassar:20261120T235900
        URL:https://moodle.kampus.ac.id/mod/assign/view.php?id=999
        END:VEVENT
        BEGIN:VEVENT
        UID:quiz-5678@moodle.kampus.ac.id
        SUMMARY:Quiz Basis Data
        CATEGORIES: Basis Data Lanjut
        DTSTART;TZID=Asia/Makassar:20261118T100000
        END:VEVENT
        BEGIN:VEVENT
        SUMMARY:Acara tanpa waktu mulai
        DTSTAMP:20261101T000000Z
        END:VEVENT
        BEGIN:VEVENT
        SUMMARY:Tenggat tanpa UID
        CATEGORIES:Statistika
        DTSTART;TZID=Asia/Makassar:20261125T120000
        END:VEVENT
        END:VCALENDAR
        ICS;

    public function test_events_terparsing_dengan_lengkap(): void
    {
        $events = app(IcalParserService::class)->parseIcs(self::SAMPLE_ICS);

        // Event tanpa DTSTART dilewati (3 dari 4).
        $this->assertCount(3, $events);

        $first = $events[0];
        $this->assertSame('assignment-1234@moodle.kampus.ac.id', $first['uid']);
        $this->assertSame('Tugas 1 - Membuat Dashboard', $first['summary']);
        $this->assertSame('tugas', $first['type']);
        $this->assertSame('Komputasi Awan', $first['course']);
        $this->assertSame('https://moodle.kampus.ac.id/mod/assign/view.php?id=999', $first['url']);
        $this->assertSame(2026, $first['starts_at']->year);
        $this->assertSame(23, $first['starts_at']->hour);
    }

    public function test_mata_kuliah_diambil_dari_categories(): void
    {
        $events = app(IcalParserService::class)->parseIcs(self::SAMPLE_ICS);

        $quiz = collect($events)->firstWhere('summary', 'Quiz Basis Data');
        $this->assertSame('kuis', $quiz['type']);
        $this->assertSame('Basis Data Lanjut', $quiz['course']);
    }

    public function test_uid_kosong_diganti_hash_tetap_unik(): void
    {
        $events = app(IcalParserService::class)->parseIcs(self::SAMPLE_ICS);

        $noUid = collect($events)->firstWhere('summary', 'Tenggat tanpa UID');
        $this->assertStringStartsWith('mdc-', $noUid['uid']);
        $this->assertSame('Statistika', $noUid['course']);
    }

    public function test_ics_rusak_ditolak_dengan_jelas(): void
    {
        $parser = app(IcalParserService::class);

        $this->expectException(\RuntimeException::class);
        $parser->parseIcs('ini bukan file ics yang valid');
    }
}
