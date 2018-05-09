<?php header('Content-Type: application/rss+xml; charset=ISO-8859-1'); ?><?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0">

    <channel>

        <title>The Restart Project: Restart Parties!</title>
        <link>http://www.therestartproject.org</link>
        <description></description>

        @foreach($parties as $party)
        <item>
            <title><?php echo $party->location; ?></title>
            <link><?php echo env('APP_URL') . '/party/' . $party->idevents; ?></link>
            <description>Hosted by <?php echo $party->group_name; ?> on <?php echo strftime('%a, %d %b %Y', $party->event_date); ?> - <?php echo $party->free_text; ?></description>
        </item>
        @endforeach

    </channel>

</rss>
