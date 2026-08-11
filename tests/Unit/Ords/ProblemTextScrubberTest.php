<?php

namespace Tests\Unit\Ords;

use App\Services\Ords\ProblemTextScrubber;
use PHPUnit\Framework\TestCase;

class ProblemTextScrubberTest extends TestCase
{
    private ProblemTextScrubber $scrubber;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scrubber = new ProblemTextScrubber();
    }

    public function test_strips_html_tags(): void
    {
        // ORA's own published data contains <p> tags, and `devices.problem` has
        // no Purify mutator so anything a volunteer pastes lands raw.
        $this->assertEquals(
            'Screen flickers Replaced the inverter',
            $this->scrubber->scrub('<p>Screen flickers</p><p>Replaced the inverter</p>')
        );
    }

    public function test_strips_escaped_html_tags(): void
    {
        $this->assertEquals(
            'Broken hinge',
            $this->scrubber->scrub('&lt;b&gt;Broken hinge&lt;/b&gt;')
        );
    }

    /**
     * strip_tags() discards everything after an unterminated "<", and repair
     * notes routinely compare against a threshold, a price or a value.
     */
    public function test_keeps_text_containing_a_bare_less_than(): void
    {
        $this->assertEquals(
            'temp <100C and rising, unit dead',
            $this->scrubber->scrub('temp <100C and rising, unit dead')
        );

        $this->assertEquals(
            'price was <5 pounds',
            $this->scrubber->scrub('price was <5 pounds')
        );

        $this->assertEquals(
            'reads a<b on the meter',
            $this->scrubber->scrub('reads a<b on the meter')
        );
    }

    public function test_leaves_iso_dates_alone(): void
    {
        // An ISO date carries enough digits and separators to look like a phone
        // number, and dates are substance rather than personal data.
        $this->assertEquals(
            'serviced on 2024-06-15 by the owner',
            $this->scrubber->scrub('serviced on 2024-06-15 by the owner')
        );
        $this->assertEquals(
            'logged 2024-06-15 14:30 at the bench',
            $this->scrubber->scrub('logged 2024-06-15 14:30 at the bench')
        );
        $this->assertEquals(0, $this->scrubber->counts()[ProblemTextScrubber::PHONE]);
    }

    /**
     * Widening the phone pattern to catch "phone-555-123-4567" also made it
     * greedy enough to eat hyphenated numeric substance. The nine-digit floor
     * is what holds these apart, so both sides of it are pinned here.
     */
    public function test_leaves_hyphenated_numeric_substance_alone(): void
    {
        foreach ([
            'spins at 1000-2000 rpm now',
            'firmware 1.2.3-4567 installed',
            'part no. 12-345-678 ordered',
            'fault seen 2023-2024 repeatedly',
            'drop from 240-110 volts',
        ] as $input) {
            $this->scrubber->reset();
            $this->assertEquals($input, $this->scrubber->scrub($input));
            $this->assertEquals(0, $this->scrubber->counts()[ProblemTextScrubber::PHONE], $input);
        }
    }

    /**
     * The first two run onto a preceding word, which a word-character
     * lookbehind used to let through completely unredacted.
     */
    public function test_still_redacts_numbers_long_enough_to_dial(): void
    {
        foreach ([
            'phone-555-123-4567' => 'phone-[phone removed]',
            'mob-07700-900123' => 'mob-[phone removed]',
            'call 555-123-4567' => 'call [phone removed]',
            'ring +1 (555) 123-4567' => 'ring [phone removed]',
            'owner 020 7946 0958' => 'owner [phone removed]',
        ] as $input => $expected) {
            $this->scrubber->reset();
            $this->assertEquals($expected, $this->scrubber->scrub($input));
            $this->assertEquals(1, $this->scrubber->counts()[ProblemTextScrubber::PHONE]);
        }
    }

    public function test_redacts_non_ascii_and_homoglyph_email_addresses(): void
    {
        // An ASCII-only pattern left the first two untouched or, worse, redacted
        // only the ASCII tail and published the name fragment ahead of it.
        $this->assertEquals('[email removed]', $this->scrubber->scrub('john@münchen.de'));
        $this->assertEquals('[email removed]', $this->scrubber->scrub('josé.garcía@example.com'));
        // Fullwidth commercial at (U+FF20).
        $this->assertEquals('[email removed]', $this->scrubber->scrub("john\u{FF20}example.com"));

        $this->assertEquals(3, $this->scrubber->counts()[ProblemTextScrubber::EMAIL]);
    }

    public function test_redacts_email_addresses(): void
    {
        $result = $this->scrubber->scrub('Owner is jane.doe+repairs@example.co.uk, will follow up');

        $this->assertEquals('Owner is [email removed], will follow up', $result);
        $this->assertEquals(1, $this->scrubber->counts()[ProblemTextScrubber::EMAIL]);
    }

    /**
     * Regression: every one of these exported a reconstructible address. The
     * email pattern cannot cross whitespace, whitespace was only normalised
     * after redaction had already run, and stripHtml substituted a space for
     * every tag, which manufactured the break in the inline-tag case.
     *
     * @dataProvider brokenEmailProvider
     */
    public function test_redacts_email_addresses_broken_by_whitespace_or_markup(string $problem): void
    {
        $result = $this->scrubber->scrub($problem);

        $this->assertStringNotContainsString('john.smith', $result);
        $this->assertStringNotContainsString('example.com', $result);
        $this->assertStringContainsString('[email removed]', $result);
    }

    public static function brokenEmailProvider(): array
    {
        return [
            'newline in address' => ["contact john.smith@\nexample.com"],
            'non-breaking space' => ["contact john.smith@\u{00A0}example.com"],
            'plain space' => ['contact john.smith@ example.com'],
            'inline tag inside address' => ['contact john.smith@ex<b>ample</b>.com'],
            'tab before domain' => ["contact john.smith@\texample.com"],
            // A ">" inside a quoted attribute used to end the tag early, so the
            // rest of the attribute stayed as text and the address survived
            // whole: this exported as john.smith@">example.com.
            'gt inside a double-quoted attribute' => ['contact john.smith@<span title="x>">example.com'],
            'gt inside a single-quoted attribute' => ["contact john.smith@<span title='x>'>example.com"],
            'gt mid-attribute' => ['contact john.smith@<i title="a>b">example.com'],
        ];
    }

    /** The tolerant pass must not treat prices, measurements or citations as addresses. */
    public function test_does_not_redact_at_signs_that_are_not_addresses(): void
    {
        foreach (['cost 10 @ 2.50 each', '5 @ 3 . 2 volts', 'see p . 4', 'met @ the cafe . nice'] as $kept) {
            $this->assertEquals($kept, $this->scrubber->scrub($kept));
        }

        $this->assertEquals(0, $this->scrubber->counts()[ProblemTextScrubber::EMAIL]);
    }

    /** Inline tags close up; block tags still mark a word boundary. */
    public function test_block_tags_separate_words_but_inline_tags_do_not(): void
    {
        $this->assertEquals('word one word two', $this->scrubber->scrub('<p>word one</p><p>word two</p>'));
        $this->assertEquals('bold', $this->scrubber->scrub('<b>bo</b>ld'));
    }

    public function test_strips_url_query_strings_but_keeps_the_bare_url(): void
    {
        // Modelled on published record fixitclinic_584, which carries a full
        // affiliate URL with gclid and sfdr_ptcid tracking parameters.
        $result = $this->scrubber->scrub(
            'Part at https://www.example.com/parts/motor?gclid=ABC123xyz&sfdr_ptcid=99887766 ordered'
        );

        $this->assertEquals('Part at https://www.example.com/parts/motor ordered', $result);
        $this->assertEquals(1, $this->scrubber->counts()[ProblemTextScrubber::URL_QUERY]);
    }

    public function test_strips_url_fragments(): void
    {
        $this->assertEquals(
            'See https://example.com/guide',
            $this->scrubber->scrub('See https://example.com/guide#step-4-user-jane')
        );
    }

    public function test_redacts_long_digit_runs(): void
    {
        $result = $this->scrubber->scrub('IMEI 356938035643809 does not match the label');

        $this->assertEquals('IMEI [number removed] does not match the label', $result);
    }

    public function test_leaves_short_numbers_alone(): void
    {
        // Model numbers, years, capacities and measurements are the substance
        // of a repair note; over-redacting would gut the dataset.
        $this->assertEquals(
            'Bosch WAE24166GB from 2011, 1200 rpm, blew a 13 amp fuse',
            $this->scrubber->scrub('Bosch WAE24166GB from 2011, 1200 rpm, blew a 13 amp fuse')
        );
    }

    public function test_handles_multiple_redaction_types_in_one_string(): void
    {
        $result = $this->scrubber->scrub(
            '<p>Contact me@example.com or 555-123-4567.</p> Serial 123456789012. '
            .'Guide https://example.com/x?utm_source=email'
        );

        $this->assertStringNotContainsString('me@example.com', $result);
        $this->assertStringNotContainsString('555-123-4567', $result);
        $this->assertStringNotContainsString('123456789012', $result);
        $this->assertStringNotContainsString('utm_source', $result);
        $this->assertStringContainsString('https://example.com/x', $result);

        $counts = $this->scrubber->counts();
        $this->assertEquals(1, $counts[ProblemTextScrubber::EMAIL]);
        $this->assertEquals(1, $counts[ProblemTextScrubber::PHONE]);
        $this->assertEquals(1, $counts[ProblemTextScrubber::LONG_DIGITS]);
        $this->assertEquals(1, $counts[ProblemTextScrubber::URL_QUERY]);
        // Occurrences, like every other counter: the fixture opens and closes one <p>.
        $this->assertEquals(2, $counts[ProblemTextScrubber::HTML]);
        $this->assertEquals(6, $this->scrubber->totalRedactions());
    }

    public function test_counts_accumulate_across_records_until_reset(): void
    {
        $this->scrubber->scrub('a@example.com');
        $this->scrubber->scrub('b@example.com');

        $this->assertEquals(2, $this->scrubber->counts()[ProblemTextScrubber::EMAIL]);

        $this->scrubber->reset();

        $this->assertEquals(0, $this->scrubber->counts()[ProblemTextScrubber::EMAIL]);
        $this->assertEquals(0, $this->scrubber->totalRedactions());
    }

    public function test_handles_null_and_blank_input(): void
    {
        $this->assertEquals('', $this->scrubber->scrub(null));
        $this->assertEquals('', $this->scrubber->scrub(''));
        $this->assertEquals('', $this->scrubber->scrub('   '));
        $this->assertEquals(0, $this->scrubber->totalRedactions());
    }

    public function test_collapses_whitespace(): void
    {
        $this->assertEquals(
            'Fixed the switch',
            $this->scrubber->scrub("  Fixed   the\n\tswitch  ")
        );
    }
}
