<?php header('Content-Type: application/rss+xml; charset=ISO-8859-1'); ?><?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0">

    <channel>

        <title>The Restart Project: Groups</title>
        <link>http://www.therestartproject.org</link>
        <description></description>

        @foreach($groups as $group)
        <item>
            <title><?php echo $group->name; ?></title>
            <link><?php echo env('APP_URL') . '/group/' . $group->idgroups; ?></link>
            <description></description>
        </item>
        @endforeach

    </channel>

</rss>
